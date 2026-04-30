<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Models\WasteEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InsightsController extends Controller
{
    public function index(Request $request): Response
    {
        $uid = $request->user()->id;

        $thisMonth = WasteEntry::where('user_id', $uid)
            ->whereBetween('logged_at', [now()->startOfMonth(), now()->endOfDay()])
            ->selectRaw('category, reason, SUM(weight_kg) as kg')
            ->groupBy('category', 'reason')
            ->get();

        $lastMonth = WasteEntry::where('user_id', $uid)
            ->whereBetween('logged_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->selectRaw('category, reason, SUM(weight_kg) as kg')
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
            $end   = now()->subWeeks($weeksAgo)->endOfWeek();

            return [
                'label' => $start->format('d M'),
                'kg'    => round((float) WasteEntry::where('user_id', $uid)
                    ->whereBetween('logged_at', [$start, $end])
                    ->sum('weight_kg'), 2),
            ];
        });

        return Inertia::render('waste/Insights', [
            'thisMonthKg' => round((float) $thisMonth->sum('kg'), 2),
            'lastMonthKg' => round((float) $lastMonth->sum('kg'), 2),
            'thisWeekKg'  => round($thisWeekKg, 2),
            'lastWeekKg'  => round($lastWeekKg, 2),
            'byCategory'  => $thisMonth->groupBy('category')
                ->map(fn ($rows) => round((float) $rows->sum('kg'), 2)),
            'byReason'    => $thisMonth->groupBy('reason')
                ->map(fn ($rows) => round((float) $rows->sum('kg'), 2)),
            'weeklyTrend' => $weeklyTrend->values(),
            'hasData'     => $thisMonth->isNotEmpty(),
        ]);
    }
}
