<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Support\PurchaseSummary;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $summary = new PurchaseSummary($user);

        $period = $request->query('period', 'this_month');
        [$from, $to] = ReportPeriod::resolve($period, $request->query('date_from'), $request->query('date_to'));

        return Inertia::render('waste/ReportPurchases', [
            'period' => $period,
            'dateFrom' => $from?->toDateString() ?? '',
            'dateTo' => $to?->toDateString() ?? '',
            'totals' => $summary->totals($from, $to),
            'bySupplier' => $summary->bySupplier($from, $to, 15),
            'byCategory' => $summary->byCategory($from, $to),
            'hasAnyReceipts' => $user->receipts()->confirmed()->exists(),
            'currency' => $user->currency,
            'exportQuota' => [
                'used' => $user->exportsUsedThisMonth(),
                'limit' => $user->exportQuota(),
            ],
        ]);
    }
}
