<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Models\WasteEntry;
use App\Support\WasteReportBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InsightsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $uid = $user->id;
        $prices = $user->waste_prices ?? [];

        $thisMonth = WasteEntry::where('user_id', $uid)
            ->whereBetween('logged_at', [now()->startOfMonth(), now()->endOfDay()])
            ->selectRaw('category, reason, SUM(weight_kg) as kg, '.WasteReportBuilder::COST_SELECT)
            ->groupBy('category', 'reason')
            ->get();

        $lastMonth = WasteEntry::where('user_id', $uid)
            ->whereBetween('logged_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->selectRaw('category, reason, SUM(weight_kg) as kg, '.WasteReportBuilder::COST_SELECT)
            ->groupBy('category', 'reason')
            ->get();

        $thisWeekKg = (float) WasteEntry::where('user_id', $uid)
            ->where('logged_at', '>=', now()->startOfWeek())
            ->sum('weight_kg');

        $lastWeekKg = (float) WasteEntry::where('user_id', $uid)
            ->whereBetween('logged_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->sum('weight_kg');

        $weeklyTrend = collect(range(7, 0))->map(function (int $weeksAgo) use ($uid) {
            $start = now()->subWeeks($weeksAgo)->startOfWeek();
            $end = now()->subWeeks($weeksAgo)->endOfWeek();

            return [
                'label' => $start->format('d M'),
                'kg' => round((float) WasteEntry::where('user_id', $uid)
                    ->whereBetween('logged_at', [$start, $end])
                    ->sum('weight_kg'), 2),
            ];
        });

        $categoryCost = fn ($rows, string $category): float => WasteReportBuilder::categoryCost(
            (float) $rows->sum('entry_cost'),
            (float) $rows->sum('unpriced_kg'),
            (int) $rows->sum('priced_count'),
            $prices[$category] ?? null,
        ) ?? 0.0;

        $costOf = fn ($rows) => round((float) $rows->groupBy('category')->sum(
            fn ($categoryRows, $category) => $categoryCost($categoryRows, $category)
        ), 2);

        $co2eOf = fn ($rows) => round((float) $rows->groupBy('category')->sum(
            fn ($categoryRows, $category) => (float) $categoryRows->sum('kg') * WasteReportBuilder::CO2E_FACTORS[$category]
        ), 2);

        return Inertia::render('waste/Insights', [
            'hasCostData' => collect($prices)->contains(fn ($price) => $price !== null && $price !== '')
                || $thisMonth->sum('priced_count') > 0,
            'currency' => $user->currency,
            'thisMonthCost' => $costOf($thisMonth),
            'lastMonthCost' => $costOf($lastMonth),
            'thisMonthCo2e' => $co2eOf($thisMonth),
            'costByCategory' => $thisMonth->groupBy('category')
                ->map(fn ($rows, $category) => round($categoryCost($rows, $category), 2)),
            'thisMonthKg' => round((float) $thisMonth->sum('kg'), 2),
            'lastMonthKg' => round((float) $lastMonth->sum('kg'), 2),
            'thisWeekKg' => round($thisWeekKg, 2),
            'lastWeekKg' => round($lastWeekKg, 2),
            'byCategory' => $thisMonth->groupBy('category')
                ->map(fn ($rows) => round((float) $rows->sum('kg'), 2)),
            'byReason' => $thisMonth->groupBy('reason')
                ->map(fn ($rows) => round((float) $rows->sum('kg'), 2)),
            'weeklyTrend' => $weeklyTrend->values(),
            'hasData' => $thisMonth->isNotEmpty(),
        ]);
    }
}
