<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Turns a period choice (this month, last month, this year, all time or custom
 * dates) into a date range. A null bound means open ended.
 */
class ReportPeriod
{
    public const OPTIONS = ['this_month', 'last_month', 'this_year', 'all'];

    /**
     * @return array{0: CarbonInterface|null, 1: CarbonInterface|null}
     */
    public static function resolve(string $period, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return match ($period) {
            'all' => [null, null],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [
                $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null,
                $dateTo ? Carbon::parse($dateTo)->endOfDay() : null,
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
