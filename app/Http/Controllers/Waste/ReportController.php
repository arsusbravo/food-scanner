<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Models\UserExport;
use App\Models\WasteEntry;
use App\Support\WasteReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
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
            ->selectRaw('category, reason, SUM(weight_kg) as total_kg, COUNT(*) as entry_count')
            ->groupBy('category', 'reason')
            ->orderBy('category')
            ->get();

        $summary = $this->builder->buildSummary($rows);

        $user = $request->user();

        return Inertia::render('waste/Report', [
            'summary'      => $summary,
            'dateFrom'     => $from->toDateString(),
            'dateTo'       => $to->toDateString(),
            'grandTotal'   => round($rows->sum('total_kg'), 2),
            'totalEntries' => $rows->sum('entry_count'),
            'quota'        => [
                'exports_used'   => $user->exportsUsedThisMonth(),
                'export_quota'   => $user->exportQuota(),
            ],
        ]);
    }

    public function exportCsv(Request $request): HttpResponse|\Illuminate\Http\RedirectResponse
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

        $csv = "Date,Category,FLW Group,EU Code,Item,Weight (kg),Reason,Notes,Source\n";
        foreach ($entries as $entry) {
            $flw = WasteReportBuilder::FLW_MAPPING[$entry->category];
            $csv .= implode(',', [
                $entry->logged_at->toDateString(),
                $entry->category,
                '"' . $flw['group'] . '"',
                $flw['eu_code'],
                '"' . str_replace('"', '""', $entry->item_name) . '"',
                number_format((float) $entry->weight_kg, 4),
                $entry->reason,
                '"' . str_replace('"', '""', $entry->notes ?? '') . '"',
                $entry->source,
            ]) . "\n";
        }

        $filename = 'food-waste-report-' . $from->toDateString() . '-to-' . $to->toDateString() . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportPdf(Request $request): HttpResponse|\Illuminate\Http\RedirectResponse
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
            ->selectRaw('category, reason, SUM(weight_kg) as total_kg, COUNT(*) as entry_count')
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

        $summary = $this->builder->buildSummary($rows);

        $locale = $user->resolveDocumentLocale($request->cookie('locale', 'en'));

        $pdf = Pdf::loadView('reports.waste-compliance', [
            'summary'            => $summary,
            'dateFrom'           => $from->toDateString(),
            'dateTo'             => $to->toDateString(),
            'grandTotal'         => round($rows->sum('total_kg'), 2),
            'totalEntries'       => $rows->sum('entry_count'),
            'individualEntries'  => $individualEntries,
            'flwMapping'         => WasteReportBuilder::FLW_MAPPING,
            'generatedAt'        => now()->toDateTimeString(),
            'user'               => $user,
            'company'            => $user->company,
            'tr'                 => $this->builder->translations($locale),
            'demo'               => false,
        ]);

        $filename = 'eu-food-waste-report-' . $from->toDateString() . '-to-' . $to->toDateString() . '.pdf';

        return $pdf->download($filename);
    }
}
