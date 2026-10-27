<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Models\UserExport;
use App\Models\WasteEntry;
use App\Support\WasteReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private readonly WasteReportBuilder $builder) {}

    public function index(Request $request): Response
    {
        $from = $request->query('date_from')
            ? Carbon::parse($request->query('date_from'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->query('date_to')
            ? Carbon::parse($request->query('date_to'))->endOfDay()
            : now()->endOfDay();

        $rows = WasteEntry::where('user_id', $request->user()->id)
            ->whereBetween('logged_at', [$from, $to])
            ->selectRaw('category, reason, SUM(weight_kg) as total_kg, COUNT(*) as entry_count, '.WasteReportBuilder::COST_SELECT)
            ->groupBy('category', 'reason')
            ->orderBy('category')
            ->get();

        $user = $request->user();

        $summary = $this->builder->buildSummary($rows, $user->waste_prices);
        $totals = $this->builder->totals($summary);

        return Inertia::render('waste/Report', [
            'summary' => $summary,
            'totalCost' => $totals['cost'],
            'totalCo2e' => $totals['co2e'],
            'currency' => $user->currency,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'grandTotal' => round($rows->sum('total_kg'), 2),
            'totalEntries' => $rows->sum('entry_count'),
            'quota' => [
                'exports_used' => $user->exportsUsedThisMonth(),
                'export_quota' => $user->exportQuota(),
            ],
        ]);
    }

    public function exportCsv(Request $request): HttpResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user->canExport()) {
            $quota = $user->exportQuota();

            return back()->withErrors(['quota' => "Monthly export limit reached ({$quota} exports). Upgrade to Pro for unlimited exports."]);
        }

        UserExport::create(['user_id' => $user->id, 'type' => 'csv']);

        $from = Carbon::parse($request->query('date_from', now()->startOfMonth()))->startOfDay();
        $to = Carbon::parse($request->query('date_to', now()))->endOfDay();

        $entries = WasteEntry::where('user_id', $user->id)
            ->whereBetween('logged_at', [$from, $to])
            ->orderBy('logged_at', 'desc')
            ->get();

        $prices = $user->waste_prices ?? [];
        $hasPrices = collect($prices)->contains(fn ($price) => $price !== null && $price !== '')
            || $entries->contains(fn ($entry) => $entry->cost !== null);

        $csv = 'Date,Category,FLW Group,EU Code,Item,Weight (kg),Reason,Notes,Source,CO2e (kg)'
            .($hasPrices ? ",Cost ({$user->currency})" : '')."\n";
        foreach ($entries as $entry) {
            $flw = WasteReportBuilder::FLW_MAPPING[$entry->category];
            $price = $prices[$entry->category] ?? null;
            $csv .= implode(',', array_filter([
                $entry->logged_at->toDateString(),
                $entry->category,
                '"'.$flw['group'].'"',
                $flw['eu_code'],
                '"'.str_replace('"', '""', $entry->item_name).'"',
                number_format((float) $entry->weight_kg, 4),
                $entry->reason,
                '"'.str_replace('"', '""', $entry->notes ?? '').'"',
                $entry->source,
                number_format((float) $entry->weight_kg * WasteReportBuilder::CO2E_FACTORS[$entry->category], 2, '.', ''),
                $hasPrices ? $this->entryCostCell($entry, $price) : null,
            ], fn ($value) => $value !== null))."\n";
        }

        $filename = 'food-waste-report-'.$from->toDateString().'-to-'.$to->toDateString().'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function entryCostCell(WasteEntry $entry, mixed $price): string
    {
        if ($entry->cost !== null) {
            return number_format((float) $entry->cost, 2, '.', '');
        }

        return $price !== null && $price !== ''
            ? number_format((float) $entry->weight_kg * (float) $price, 2, '.', '')
            : '';
    }

    public function exportPdf(Request $request): HttpResponse|RedirectResponse
    {
        $user = $request->user()->load('company');

        if (! $user->canExport()) {
            $quota = $user->exportQuota();

            return back()->withErrors(['quota' => "Monthly export limit reached ({$quota} exports). Upgrade to Pro for unlimited exports."]);
        }

        UserExport::create(['user_id' => $user->id, 'type' => 'pdf']);

        $from = Carbon::parse($request->query('date_from', now()->startOfMonth()))->startOfDay();
        $to = Carbon::parse($request->query('date_to', now()))->endOfDay();

        $rows = WasteEntry::where('user_id', $user->id)
            ->whereBetween('logged_at', [$from, $to])
            ->selectRaw('category, reason, SUM(weight_kg) as total_kg, COUNT(*) as entry_count, '.WasteReportBuilder::COST_SELECT)
            ->groupBy('category', 'reason')
            ->orderBy('category')
            ->get();

        $showIndividual = $from->diffInDays($to) <= 31;
        $individualEntries = $showIndividual
            ? WasteEntry::where('user_id', $user->id)
                ->whereBetween('logged_at', [$from, $to])
                ->orderBy('logged_at', 'desc')
                ->get()
            : collect();

        $summary = $this->builder->buildSummary($rows, $user->waste_prices);
        $totals = $this->builder->totals($summary);

        $locale = $user->resolveDocumentLocale($request->cookie('locale', 'en'));

        $pdf = Pdf::loadView('reports.waste-compliance', [
            'summary' => $summary,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'grandTotal' => round($rows->sum('total_kg'), 2),
            'totalEntries' => $rows->sum('entry_count'),
            'totalCost' => $totals['cost'],
            'totalCo2e' => $totals['co2e'],
            'currency' => $user->currency,
            'individualEntries' => $individualEntries,
            'flwMapping' => WasteReportBuilder::FLW_MAPPING,
            'generatedAt' => now()->toDateTimeString(),
            'user' => $user,
            'company' => $user->company,
            'tr' => $this->builder->translations($locale),
            'demo' => false,
        ]);

        $filename = 'eu-food-waste-report-'.$from->toDateString().'-to-'.$to->toDateString().'.pdf';

        return $pdf->download($filename);
    }
}
