<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoEvent;
use App\Models\DemoUsage;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DemoStatsController extends Controller
{
    /** Window for time-bounded metrics (daily chart, top-X, funnel). */
    private const WINDOW_DAYS = 30;

    public function index(): Response
    {
        $since = now()->subDays(self::WINDOW_DAYS);

        return Inertia::render('admin/DemoStats', [
            'stats'         => $this->stats(),
            'funnel'        => $this->funnel($since),
            'dropoff'       => $this->dropoffDetail($since),
            'daily'         => $this->daily($since),
            'top_countries' => $this->topDistinctDevices('country', $since),
            'top_locales'   => $this->topDistinctDevices('locale', $since),
            'top_referers'  => $this->topReferers($since),
            'recent'        => $this->recent(),
            'window_days'   => self::WINDOW_DAYS,
        ]);
    }

    /**
     * Lifetime stat cards. Conversion is signups-from-demo / devices that
     * actually scanned (the meaningful denominator — drive-by visits with
     * zero interaction shouldn't dilute the rate).
     */
    private function stats(): array
    {
        $signups = User::whereNotNull('demo_device_id')->count();
        $scanningDevices = DemoUsage::where('scans', '>', 0)->count();

        return [
            'total_devices'      => DemoUsage::count(),
            'devices_7d'         => DemoUsage::where('updated_at', '>=', now()->subDays(7))->count(),
            'total_scans'        => (int) DemoUsage::sum('scans'),
            'total_reports'      => (int) DemoUsage::sum('reports'),
            'signups_from_demo'  => $signups,
            'conversion_pct'     => $scanningDevices > 0
                ? round($signups / $scanningDevices * 100, 1)
                : 0.0,
        ];
    }

    /**
     * Visit → scan → report → signup funnel, last 30 days. Each step counts
     * DISTINCT device_id so a chatty visitor (5 scans) still counts once.
     * Signup step counts users registered in the window whose device_id is
     * known — they may have visited the demo earlier than 30 days ago, but
     * the conversion EVENT is the signup itself, which we date here.
     */
    private function funnel(CarbonInterface $since): array
    {
        $devicesByType = DemoEvent::selectRaw('type, count(distinct device_id) as devices')
            ->where('created_at', '>=', $since)
            ->groupBy('type')
            ->pluck('devices', 'type');

        return [
            'visits'   => (int) ($devicesByType['visit']  ?? 0),
            'scanned'  => (int) ($devicesByType['scan']   ?? 0),
            'reported' => (int) ($devicesByType['report'] ?? 0),
            'signups'  => User::whereNotNull('demo_device_id')
                ->where('created_at', '>=', $since)
                ->count(),
        ];
    }

    /**
     * Granular drop-off across all 9 funnel stages so we can SEE the
     * specific step where visitors disengage. Each step shows distinct
     * device counts; "Engaged with upload area" merges file_selected and
     * sample_clicked because both signal real intent.
     *
     * Returns: list of {key, label, devices, pct_visits, drop_pct}
     * where drop_pct is how many of the *previous step's* devices made it
     * to this one (so a step with drop_pct=20 means 80% bailed there).
     *
     * @return list<array{key:string, label:string, devices:int, pct_visits:?int, drop_pct:?int}>
     */
    private function dropoffDetail(CarbonInterface $since): array
    {
        // Single query that counts distinct devices per event type so we can
        // look up each step in O(1) below.
        $byType = DemoEvent::selectRaw('type, count(distinct device_id) as devices')
            ->where('created_at', '>=', $since)
            ->groupBy('type')
            ->pluck('devices', 'type');

        $countOf = fn (string $type): int => (int) ($byType[$type] ?? 0);

        // "Engaged" = either selected a file OR clicked a sample. We count
        // the UNION of device_ids — not the sum — so a visitor who did both
        // counts once.
        $engaged = DemoEvent::whereIn('type', ['file_selected', 'sample_clicked'])
            ->where('created_at', '>=', $since)
            ->distinct('device_id')
            ->count('device_id');

        $signups = User::whereNotNull('demo_device_id')
            ->where('created_at', '>=', $since)
            ->count();

        $rawSteps = [
            ['key' => 'visit',          'label' => 'Visit',              'devices' => $countOf('visit')],
            ['key' => 'captcha_ready',  'label' => 'Captcha ready',      'devices' => $countOf('captcha_ready')],
            ['key' => 'engaged',        'label' => 'Engaged (upload or sample)', 'devices' => $engaged],
            ['key' => 'scan_clicked',   'label' => 'Clicked Analyse',    'devices' => $countOf('scan_clicked')],
            ['key' => 'scan',           'label' => 'Scan succeeded',     'devices' => $countOf('scan')],
            ['key' => 'entry_added',    'label' => 'Added to demo list', 'devices' => $countOf('entry_added')],
            ['key' => 'report_clicked', 'label' => 'Generated report',   'devices' => $countOf('report_clicked')],
            ['key' => 'pdf_clicked',    'label' => 'Downloaded PDF',     'devices' => $countOf('pdf_clicked')],
            ['key' => 'signups',        'label' => 'Signed up',          'devices' => $signups],
        ];

        $visits = $rawSteps[0]['devices'];
        $prev   = null;

        return array_map(function (array $step) use ($visits, &$prev) {
            // drop_pct = how many of the *previous step's* devices reached this one.
            // The funnel can branch (sample click bypasses captcha_ready, file path
            // requires it) so this ratio can technically exceed 100% — when it
            // does we cap at 100 so the "kept" badge stays meaningful as a
            // drop-off indicator (and never visually claims to "gain" devices).
            $rawDrop = $prev !== null && $prev > 0
                ? (int) round($step['devices'] / $prev * 100)
                : null;

            $row = [
                'key'         => $step['key'],
                'label'       => $step['label'],
                'devices'     => $step['devices'],
                'pct_visits'  => $visits > 0 ? (int) round($step['devices'] / $visits * 100) : null,
                'drop_pct'    => $rawDrop === null ? null : min(100, $rawDrop),
            ];
            $prev = $step['devices'];
            return $row;
        }, $rawSteps);
    }

    /**
     * Per-day event counts split by type, for the inline SVG bar chart.
     * Returns a dense array (one entry per day in the window) so the Vue
     * side doesn't have to fill gaps.
     *
     * @return list<array{date:string, visit:int, scan:int, report:int}>
     */
    private function daily(CarbonInterface $since): array
    {
        $rows = DB::table('demo_events')
            ->selectRaw('date(created_at) as d, type, count(*) as c')
            ->where('created_at', '>=', $since)
            ->groupBy('d', 'type')
            ->get();

        // Index by date for O(1) lookups while filling the dense series.
        $byDate = [];
        foreach ($rows as $r) {
            $byDate[$r->d][$r->type] = (int) $r->c;
        }

        $series = [];
        for ($i = self::WINDOW_DAYS - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $series[] = [
                'date'   => $d,
                'visit'  => $byDate[$d]['visit']  ?? 0,
                'scan'   => $byDate[$d]['scan']   ?? 0,
                'report' => $byDate[$d]['report'] ?? 0,
            ];
        }

        return $series;
    }

    /**
     * Top values of a single column, ranked by distinct devices (last 30d).
     * Used for country and locale breakdowns.
     *
     * @return list<array{label:string, devices:int}>
     */
    private function topDistinctDevices(string $column, CarbonInterface $since): array
    {
        return DemoEvent::selectRaw("$column as label, count(distinct device_id) as devices")
            ->where('created_at', '>=', $since)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->orderByDesc('devices')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['label' => (string) $r->label, 'devices' => (int) $r->devices])
            ->all();
    }

    /**
     * Top referrers, normalised to host (so kitchenlog.eu, www.kitchenlog.eu,
     * and kitchenlog.eu/something all group together). Grouping happens in
     * PHP since the host-extraction would need MariaDB-specific functions.
     *
     * @return list<array{label:string, devices:int}>
     */
    private function topReferers(CarbonInterface $since): array
    {
        $pairs = DemoEvent::selectRaw('referer, device_id')
            ->where('created_at', '>=', $since)
            ->whereNotNull('referer')
            ->where('referer', '!=', '')
            ->distinct()
            ->get();

        $devicesByHost = [];
        foreach ($pairs as $p) {
            $host = parse_url($p->referer, PHP_URL_HOST) ?: $p->referer;
            $devicesByHost[$host][$p->device_id] = true;
        }

        $rows = [];
        foreach ($devicesByHost as $host => $devices) {
            $rows[] = ['label' => $host, 'devices' => count($devices)];
        }
        usort($rows, fn ($a, $b) => $b['devices'] <=> $a['devices']);

        return array_slice($rows, 0, 10);
    }

    /**
     * Last 50 raw events for the activity feed. Device id is truncated for
     * display so the UUID doesn't dominate the column.
     *
     * @return list<array{id:int, when:string, type:string, device:string, ip:?string, country:?string, locale:?string, referer:?string}>
     */
    private function recent(): array
    {
        return DemoEvent::orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (DemoEvent $e) => [
                'id'      => $e->id,
                'when'    => $e->created_at->toIso8601String(),
                'type'    => $e->type,
                'device'  => substr((string) $e->device_id, 0, 8),
                'ip'      => $e->ip,
                'country' => $e->country,
                'locale'  => $e->locale,
                'referer' => $e->referer ? (parse_url($e->referer, PHP_URL_HOST) ?: $e->referer) : null,
            ])
            ->all();
    }
}
