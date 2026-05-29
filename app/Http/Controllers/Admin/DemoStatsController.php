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
