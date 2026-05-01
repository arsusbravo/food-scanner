<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Models\UserExport;
use App\Models\WasteEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    private const FLW_MAPPING = [
        'protein' => ['group' => 'Meat & Seafood / Pulses', 'eu_code' => '02 02 / 20 01 08'],
        'veg' => ['group' => 'Vegetables & Fruits', 'eu_code' => '02 01 / 20 01 08'],
        'dairy' => ['group' => 'Dairy & Eggs', 'eu_code' => '02 05 / 20 01 08'],
        'prepared' => ['group' => 'Prepared & Processed Foods', 'eu_code' => '20 01 08'],
    ];

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

        $summary = $this->buildSummary($rows);

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
            $flw = self::FLW_MAPPING[$entry->category];
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

        $summary = $this->buildSummary($rows);

        $locale = $user->document_locale ?? $request->cookie('locale', 'en');

        $pdf = Pdf::loadView('reports.waste-compliance', [
            'summary'            => $summary,
            'dateFrom'           => $from->toDateString(),
            'dateTo'             => $to->toDateString(),
            'grandTotal'         => round($rows->sum('total_kg'), 2),
            'totalEntries'       => $rows->sum('entry_count'),
            'individualEntries'  => $individualEntries,
            'flwMapping'         => self::FLW_MAPPING,
            'generatedAt'        => now()->toDateTimeString(),
            'user'               => $user,
            'company'            => $user->company,
            'tr'                 => $this->pdfTranslations($locale),
        ]);

        $filename = 'eu-food-waste-report-' . $from->toDateString() . '-to-' . $to->toDateString() . '.pdf';

        return $pdf->download($filename);
    }

    private function pdfTranslations(string $locale): array
    {
        $tr = [
            'en' => [
                'title'             => 'EU Food Waste Compliance Report',
                'reporting_period'  => 'Reporting Period',
                'establishment'     => 'Establishment',
                'generated_by'      => 'Generated By',
                'generated_on'      => 'Generated On',
                'regulatory_label'  => 'Regulatory Reference',
                'total_waste'       => 'Total Food Waste',
                'entries'           => 'entries',
                'summary_title'     => 'Summary by FLW Category',
                'col_category'      => 'Category',
                'col_flw_group'     => 'FLW Group',
                'col_eu_code'       => 'EU Waste Code',
                'col_total_kg'      => 'Total (kg)',
                'col_entries'       => 'Entries',
                'col_pct'           => '% of Total',
                'total'             => 'TOTAL',
                'breakdown_title'   => 'Breakdown by Waste Reason',
                'col_reason'        => 'Reason',
                'col_row_total'     => 'Row Total (kg)',
                'reasons'           => ['spoilage' => 'Spoilage', 'overproduction' => 'Overproduction', 'expiry' => 'Expiry', 'prep_waste' => 'Prep Waste', 'other' => 'Other'],
                'categories'        => ['protein' => 'Protein', 'veg' => 'Vegetables', 'dairy' => 'Dairy', 'prepared' => 'Prepared'],
                'individual_title'  => 'Individual Entries',
                'showing_entries'   => 'Showing all :count entries for the selected period.',
                'col_date'          => 'Date',
                'col_item'          => 'Item',
                'col_weight'        => 'Weight (kg)',
                'col_source'        => 'Source',
                'source_scan'       => 'Scan',
                'source_manual'     => 'Manual',
                'attestation_title' => 'Attestation',
                'attestation_text'  => 'I confirm that this report accurately reflects the food waste generated by :name during the period :from to :to, and that this data has been collected in accordance with the FLW Protocol measurement methodology required under EU Directive 2018/851.',
                'sig_signature'     => 'Signature',
                'sig_date'          => 'Date',
                'sig_title'         => 'Title / Role',
            ],
            'nl' => [
                'title'             => 'EU Nalevingsrapport Voedselverspilling',
                'reporting_period'  => 'Rapportageperiode',
                'establishment'     => 'Instelling',
                'generated_by'      => 'Gegenereerd door',
                'generated_on'      => 'Gegenereerd op',
                'regulatory_label'  => 'Regelgevingsreferentie',
                'total_waste'       => 'Totale voedselverspilling',
                'entries'           => 'invoeren',
                'summary_title'     => 'Overzicht per FLW-categorie',
                'col_category'      => 'Categorie',
                'col_flw_group'     => 'FLW-groep',
                'col_eu_code'       => 'EU-afvalcode',
                'col_total_kg'      => 'Totaal (kg)',
                'col_entries'       => 'Invoeren',
                'col_pct'           => '% van totaal',
                'total'             => 'TOTAAL',
                'breakdown_title'   => 'Uitsplitsing per afvalreden',
                'col_reason'        => 'Reden',
                'col_row_total'     => 'Rijtotaal (kg)',
                'reasons'           => ['spoilage' => 'Bederf', 'overproduction' => 'Overproductie', 'expiry' => 'Verlopen THT', 'prep_waste' => 'Bereidingsafval', 'other' => 'Overig'],
                'categories'        => ['protein' => 'Proteïne', 'veg' => 'Groenten', 'dairy' => 'Zuivel', 'prepared' => 'Bereid gerecht'],
                'individual_title'  => 'Individuele invoeren',
                'showing_entries'   => 'Alle :count invoeren voor de geselecteerde periode.',
                'col_date'          => 'Datum',
                'col_item'          => 'Product',
                'col_weight'        => 'Gewicht (kg)',
                'col_source'        => 'Bron',
                'source_scan'       => 'Scan',
                'source_manual'     => 'Handmatig',
                'attestation_title' => 'Attestatie',
                'attestation_text'  => 'Ik bevestig dat dit rapport de voedselverspilling van :name nauwkeurig weergeeft voor de periode :from tot :to, en dat deze gegevens zijn verzameld conform de FLW Protocol-meetmethodologie vereist onder EU-richtlijn 2018/851.',
                'sig_signature'     => 'Handtekening',
                'sig_date'          => 'Datum',
                'sig_title'         => 'Functie / Rol',
            ],
            'de' => [
                'title'             => 'EU-Konformitätsbericht Lebensmittelabfall',
                'reporting_period'  => 'Berichtszeitraum',
                'establishment'     => 'Betrieb',
                'generated_by'      => 'Erstellt von',
                'generated_on'      => 'Erstellt am',
                'regulatory_label'  => 'Rechtsgrundlage',
                'total_waste'       => 'Gesamter Lebensmittelabfall',
                'entries'           => 'Einträge',
                'summary_title'     => 'Übersicht nach FLW-Kategorie',
                'col_category'      => 'Kategorie',
                'col_flw_group'     => 'FLW-Gruppe',
                'col_eu_code'       => 'EU-Abfallcode',
                'col_total_kg'      => 'Gesamt (kg)',
                'col_entries'       => 'Einträge',
                'col_pct'           => '% des Gesamten',
                'total'             => 'GESAMT',
                'breakdown_title'   => 'Aufschlüsselung nach Abfallgrund',
                'col_reason'        => 'Grund',
                'col_row_total'     => 'Zeilensumme (kg)',
                'reasons'           => ['spoilage' => 'Verderb', 'overproduction' => 'Überproduktion', 'expiry' => 'Ablauf MHD', 'prep_waste' => 'Zubereitungsabfall', 'other' => 'Sonstiges'],
                'categories'        => ['protein' => 'Protein', 'veg' => 'Gemüse', 'dairy' => 'Milchprodukte', 'prepared' => 'Zubereitete Speisen'],
                'individual_title'  => 'Einzeleinträge',
                'showing_entries'   => 'Alle :count Einträge für den gewählten Zeitraum.',
                'col_date'          => 'Datum',
                'col_item'          => 'Bezeichnung',
                'col_weight'        => 'Gewicht (kg)',
                'col_source'        => 'Quelle',
                'source_scan'       => 'Scan',
                'source_manual'     => 'Manuell',
                'attestation_title' => 'Bestätigung',
                'attestation_text'  => 'Ich bestätige, dass dieser Bericht den Lebensmittelabfall von :name für den Zeitraum :from bis :to korrekt widerspiegelt und dass diese Daten gemäß der FLW-Protokoll-Messmethodik erhoben wurden, wie in der EU-Richtlinie 2018/851 gefordert.',
                'sig_signature'     => 'Unterschrift',
                'sig_date'          => 'Datum',
                'sig_title'         => 'Titel / Funktion',
            ],
            'fr' => [
                'title'             => 'Rapport de conformité UE — Déchets alimentaires',
                'reporting_period'  => 'Période de rapport',
                'establishment'     => 'Établissement',
                'generated_by'      => 'Généré par',
                'generated_on'      => 'Généré le',
                'regulatory_label'  => 'Référence réglementaire',
                'total_waste'       => 'Total des déchets alimentaires',
                'entries'           => 'entrées',
                'summary_title'     => 'Résumé par catégorie FLW',
                'col_category'      => 'Catégorie',
                'col_flw_group'     => 'Groupe FLW',
                'col_eu_code'       => 'Code déchet UE',
                'col_total_kg'      => 'Total (kg)',
                'col_entries'       => 'Entrées',
                'col_pct'           => '% du total',
                'total'             => 'TOTAL',
                'breakdown_title'   => 'Répartition par raison de gaspillage',
                'col_reason'        => 'Raison',
                'col_row_total'     => 'Total ligne (kg)',
                'reasons'           => ['spoilage' => 'Détérioration', 'overproduction' => 'Surproduction', 'expiry' => 'Date de péremption', 'prep_waste' => 'Déchets de préparation', 'other' => 'Autre'],
                'categories'        => ['protein' => 'Protéines', 'veg' => 'Légumes', 'dairy' => 'Produits laitiers', 'prepared' => 'Plats préparés'],
                'individual_title'  => 'Entrées individuelles',
                'showing_entries'   => 'Affichage des :count entrées pour la période sélectionnée.',
                'col_date'          => 'Date',
                'col_item'          => 'Aliment',
                'col_weight'        => 'Poids (kg)',
                'col_source'        => 'Source',
                'source_scan'       => 'Scan',
                'source_manual'     => 'Manuel',
                'attestation_title' => 'Attestation',
                'attestation_text'  => 'Je confirme que ce rapport reflète fidèlement les déchets alimentaires générés par :name durant la période du :from au :to, et que ces données ont été collectées conformément à la méthodologie de mesure du protocole FLW requise par la directive UE 2018/851.',
                'sig_signature'     => 'Signature',
                'sig_date'          => 'Date',
                'sig_title'         => 'Titre / Rôle',
            ],
            'es' => [
                'title'             => 'Informe de cumplimiento UE — Desperdicio alimentario',
                'reporting_period'  => 'Período de informe',
                'establishment'     => 'Establecimiento',
                'generated_by'      => 'Generado por',
                'generated_on'      => 'Generado el',
                'regulatory_label'  => 'Referencia normativa',
                'total_waste'       => 'Total de desperdicio alimentario',
                'entries'           => 'entradas',
                'summary_title'     => 'Resumen por categoría FLW',
                'col_category'      => 'Categoría',
                'col_flw_group'     => 'Grupo FLW',
                'col_eu_code'       => 'Código residuo UE',
                'col_total_kg'      => 'Total (kg)',
                'col_entries'       => 'Entradas',
                'col_pct'           => '% del total',
                'total'             => 'TOTAL',
                'breakdown_title'   => 'Desglose por motivo de desperdicio',
                'col_reason'        => 'Motivo',
                'col_row_total'     => 'Total fila (kg)',
                'reasons'           => ['spoilage' => 'Deterioro', 'overproduction' => 'Sobreproducción', 'expiry' => 'Caducidad', 'prep_waste' => 'Residuo de preparación', 'other' => 'Otro'],
                'categories'        => ['protein' => 'Proteína', 'veg' => 'Verduras', 'dairy' => 'Lácteos', 'prepared' => 'Preparados'],
                'individual_title'  => 'Entradas individuales',
                'showing_entries'   => 'Mostrando las :count entradas del período seleccionado.',
                'col_date'          => 'Fecha',
                'col_item'          => 'Alimento',
                'col_weight'        => 'Peso (kg)',
                'col_source'        => 'Fuente',
                'source_scan'       => 'Escáner',
                'source_manual'     => 'Manual',
                'attestation_title' => 'Atestación',
                'attestation_text'  => 'Confirmo que este informe refleja con exactitud el desperdicio alimentario generado por :name durante el período del :from al :to, y que estos datos se han recopilado conforme a la metodología de medición del protocolo FLW requerida por la Directiva UE 2018/851.',
                'sig_signature'     => 'Firma',
                'sig_date'          => 'Fecha',
                'sig_title'         => 'Cargo / Función',
            ],
        ];

        return $tr[$locale] ?? $tr['en'];
    }

    private function buildSummary($rows): array
    {
        $summary = [];

        foreach (array_keys(self::FLW_MAPPING) as $category) {
            $categoryRows = $rows->where('category', $category);
            $flw = self::FLW_MAPPING[$category];

            $byReason = [];
            foreach ($categoryRows as $row) {
                $byReason[$row->reason] = [
                    'total_kg' => round((float) $row->total_kg, 2),
                    'entry_count' => $row->entry_count,
                ];
            }

            $summary[$category] = [
                'category' => $category,
                'flw_group' => $flw['group'],
                'eu_code' => $flw['eu_code'],
                'total_kg' => round($categoryRows->sum('total_kg'), 2),
                'entry_count' => $categoryRows->sum('entry_count'),
                'by_reason' => $byReason,
            ];
        }

        return $summary;
    }
}
