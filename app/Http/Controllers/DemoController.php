<?php

namespace App\Http\Controllers;

use App\Support\DemoQuota;
use App\Support\DemoQuotaException;
use App\Support\FoodWasteScanException;
use App\Support\FoodWasteScanner;
use App\Support\Turnstile;
use App\Support\WasteReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Public, no-signup demo: real AI scan + compliance report, metered per device
 * (see DemoQuota), Turnstile-gated, never persisted to waste_entries.
 */
class DemoController extends Controller
{
    public function index(Request $request, DemoQuota $quota): InertiaResponse
    {
        return Inertia::render('demo/Demo', [
            'turnstileSiteKey' => Turnstile::siteKey(),
            'remaining'        => $quota->remaining(),
        ]);
    }

    public function scan(Request $request, DemoQuota $quota, FoodWasteScanner $scanner): JsonResponse
    {
        if (! Turnstile::verify($request->input('cf-turnstile-response'), $request->ip())) {
            return response()->json([
                'error' => 'CAPTCHA verification failed. Please try again.',
            ], 422);
        }

        try {
            $quota->ensureCanScan();
        } catch (DemoQuotaException $e) {
            return response()->json([
                'error'     => $e->getMessage(),
                'remaining' => $quota->remaining(),
                'exhausted' => true,
            ], 429);
        }

        $validated = $request->validate([
            'photo' => ['required', 'string', 'min:100'],
        ]);

        $locale = $request->cookie('locale', 'en');

        try {
            $result = $scanner->scan($validated['photo'], $locale, 'DemoScan');
        } catch (FoodWasteScanException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $quota->recordScan();

        return response()->json([
            ...$result,
            'remaining' => $quota->remaining(),
        ]);
    }

    public function report(Request $request, WasteReportBuilder $builder): JsonResponse
    {
        $entries = $this->validateEntries($request);
        $rows    = $this->aggregate($entries);

        return response()->json([
            'summary'      => $builder->buildSummary($rows),
            'grandTotal'   => round($rows->sum('total_kg'), 2),
            'totalEntries' => (int) $rows->sum('entry_count'),
        ]);
    }

    public function reportPdf(Request $request, DemoQuota $quota, WasteReportBuilder $builder)
    {
        if (! Turnstile::verify($request->input('cf-turnstile-response'), $request->ip())) {
            return response()->json([
                'error' => 'CAPTCHA verification failed. Please try again.',
            ], 422);
        }

        try {
            $quota->ensureCanReport();
        } catch (DemoQuotaException $e) {
            return response()->json([
                'error'     => $e->getMessage(),
                'remaining' => $quota->remaining(),
                'exhausted' => true,
            ], 429);
        }

        $entries = $this->validateEntries($request);
        $rows    = $this->aggregate($entries);

        $individualEntries = collect($entries)->map(fn (array $e) => (object) [
            'logged_at'  => now(),
            'item_name'  => $e['item_name'],
            'category'   => $e['category'],
            'weight_kg'  => $e['weight_kg'],
            'reason'     => $e['reason'],
            'source'     => 'ai_scan',
        ]);

        $locale = $request->cookie('locale', 'en');

        $pdf = Pdf::loadView('reports.waste-compliance', [
            'summary'           => $builder->buildSummary($rows),
            'dateFrom'          => now()->startOfMonth()->toDateString(),
            'dateTo'            => now()->toDateString(),
            'grandTotal'        => round($rows->sum('total_kg'), 2),
            'totalEntries'      => (int) $rows->sum('entry_count'),
            'individualEntries' => $individualEntries,
            'flwMapping'        => WasteReportBuilder::FLW_MAPPING,
            'generatedAt'       => now()->toDateTimeString(),
            'user'              => (object) ['name' => 'Sample Kitchen (Demo)'],
            'company'           => null,
            'tr'                => $builder->translations($locale),
            'demo'              => true,
        ]);

        $quota->recordReport();

        return $pdf->download('eu-food-waste-report-SAMPLE.pdf');
    }

    /**
     * @return array<int, array{category:string,item_name:string,weight_kg:float,reason:string}>
     */
    private function validateEntries(Request $request): array
    {
        $validated = $request->validate([
            'entries'              => ['required', 'array', 'min:1', 'max:50'],
            'entries.*.category'   => ['required', 'in:protein,veg,dairy,prepared'],
            'entries.*.item_name'  => ['required', 'string', 'max:255'],
            'entries.*.weight_kg'  => ['required', 'numeric', 'min:0', 'max:10000'],
            'entries.*.reason'     => ['required', 'in:spoilage,overproduction,expiry,prep_waste,other'],
        ]);

        return $validated['entries'];
    }

    /**
     * Collapse demo entries into the category/reason aggregate shape that
     * WasteReportBuilder::buildSummary() expects (mirrors the SQL GROUP BY).
     */
    private function aggregate(array $entries): Collection
    {
        return collect($entries)
            ->groupBy(fn (array $e) => $e['category'] . '|' . $e['reason'])
            ->map(fn (Collection $group) => (object) [
                'category'    => $group->first()['category'],
                'reason'      => $group->first()['reason'],
                'total_kg'    => $group->sum(fn (array $e) => (float) $e['weight_kg']),
                'entry_count' => $group->count(),
            ])
            ->values();
    }
}
