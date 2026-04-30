<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tr['title'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        .page { padding: 30px 35px; }
        h1 { font-size: 18px; font-weight: bold; color: #111; }
        h2 { font-size: 13px; font-weight: bold; margin-bottom: 8px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        h3 { font-size: 11px; font-weight: bold; color: #555; margin-bottom: 4px; }

        .header { margin-bottom: 20px; border-bottom: 2px solid #111; padding-bottom: 14px; }
        .header-meta { display: flex; justify-content: space-between; margin-top: 6px; }
        .meta-block { }
        .meta-label { font-size: 9px; color: #777; text-transform: uppercase; letter-spacing: 0.05em; }
        .meta-value { font-size: 11px; font-weight: bold; }

        .regulatory { background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; padding: 8px 12px; margin-bottom: 18px; font-size: 10px; color: #555; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        th { background: #f0f0f0; font-weight: bold; padding: 7px 10px; text-align: left; border: 1px solid #ccc; font-size: 10px; }
        th.num { text-align: right; }
        td { padding: 6px 10px; border: 1px solid #ddd; font-size: 10px; vertical-align: top; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        tr:nth-child(even) td { background: #fafafa; }
        tfoot td { font-weight: bold; background: #eee !important; border-top: 2px solid #999; }

        .grand-total { background: #111; color: #fff; border-radius: 6px; padding: 12px 16px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; }
        .grand-total .label { font-size: 11px; opacity: 0.75; }
        .grand-total .value { font-size: 22px; font-weight: bold; }
        .grand-total .right { text-align: right; }

        .badge { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: 9px; font-weight: bold; }
        .badge-protein { background: #fee2e2; color: #b91c1c; }
        .badge-veg { background: #dcfce7; color: #15803d; }
        .badge-dairy { background: #fef9c3; color: #a16207; }
        .badge-prepared { background: #ffedd5; color: #c2410c; }

        .section { margin-bottom: 22px; }

        .attestation { border: 1px solid #ccc; border-radius: 4px; padding: 12px 16px; margin-top: 20px; }
        .signature-line { border-bottom: 1px solid #999; width: 200px; display: inline-block; margin-top: 20px; }
        .signature-row { display: flex; gap: 40px; margin-top: 8px; }

        .entries-note { font-size: 9px; color: #888; margin-bottom: 6px; }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header">
        <h1>{{ $tr['title'] }}</h1>
        <div class="header-meta">
            <div class="meta-block">
                <div class="meta-label">{{ $tr['reporting_period'] }}</div>
                <div class="meta-value">{{ $dateFrom }} – {{ $dateTo }}</div>
            </div>
            <div class="meta-block">
                <div class="meta-label">{{ $tr['establishment'] }}</div>
                <div class="meta-value">{{ config('app.name') }}</div>
            </div>
            <div class="meta-block">
                <div class="meta-label">{{ $tr['generated_by'] }}</div>
                <div class="meta-value">{{ $user->name }}</div>
            </div>
            <div class="meta-block">
                <div class="meta-label">{{ $tr['generated_on'] }}</div>
                <div class="meta-value">{{ $generatedAt }}</div>
            </div>
        </div>
    </div>

    <!-- Regulatory reference -->
    <div class="regulatory">
        <strong>{{ $tr['regulatory_label'] }}:</strong> EU Directive 2018/851 (amending 2008/98/EC) · FLW Protocol (Food Loss &amp; Waste Accounting) · Supply-chain stage: Food Service (HORECA)
    </div>

    <!-- Grand total -->
    <div class="grand-total">
        <div>
            <div class="label">{{ $tr['total_waste'] }}</div>
            <div class="value">{{ number_format($grandTotal, 2) }} kg</div>
        </div>
        <div class="right">
            <div class="label">{{ $totalEntries }} {{ $tr['entries'] }}</div>
            <div class="label">{{ $dateFrom }} – {{ $dateTo }}</div>
        </div>
    </div>

    <!-- Summary by FLW Category -->
    <div class="section">
        <h2>{{ $tr['summary_title'] }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ $tr['col_category'] }}</th>
                    <th>{{ $tr['col_flw_group'] }}</th>
                    <th>{{ $tr['col_eu_code'] }}</th>
                    <th class="num">{{ $tr['col_total_kg'] }}</th>
                    <th class="num">{{ $tr['col_entries'] }}</th>
                    <th class="num">{{ $tr['col_pct'] }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary as $cat => $row)
                <tr>
                    <td><span class="badge badge-{{ $cat }}">{{ $tr['categories'][$cat] ?? ucfirst($cat) }}</span></td>
                    <td>{{ $row['flw_group'] }}</td>
                    <td>{{ $row['eu_code'] }}</td>
                    <td class="num">{{ number_format($row['total_kg'], 2) }}</td>
                    <td class="num">{{ $row['entry_count'] }}</td>
                    <td class="num">
                        @if($grandTotal > 0)
                            {{ number_format(($row['total_kg'] / $grandTotal) * 100, 1) }}%
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>{{ $tr['total'] }}</strong></td>
                    <td class="num">{{ number_format($grandTotal, 2) }}</td>
                    <td class="num">{{ $totalEntries }}</td>
                    <td class="num">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Breakdown by Waste Reason × Category cross-tab -->
    <div class="section">
        <h2>{{ $tr['breakdown_title'] }}</h2>
        @php
            $reasons    = ['spoilage', 'overproduction', 'expiry', 'prep_waste', 'other'];
            $categories = ['protein', 'veg', 'dairy', 'prepared'];
        @endphp
        <table>
            <thead>
                <tr>
                    <th>{{ $tr['col_reason'] }}</th>
                    @foreach ($categories as $cat)
                    <th class="num">{{ $tr['categories'][$cat] ?? ucfirst($cat) }} (kg)</th>
                    @endforeach
                    <th class="num">{{ $tr['col_row_total'] }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reasons as $reasonKey)
                @php
                    $rowTotal = 0;
                    foreach ($categories as $cat) {
                        $rowTotal += $summary[$cat]['by_reason'][$reasonKey]['total_kg'] ?? 0;
                    }
                @endphp
                @if ($rowTotal > 0)
                <tr>
                    <td>{{ $tr['reasons'][$reasonKey] ?? $reasonKey }}</td>
                    @foreach ($categories as $cat)
                    <td class="num">
                        @if(isset($summary[$cat]['by_reason'][$reasonKey]))
                            {{ number_format($summary[$cat]['by_reason'][$reasonKey]['total_kg'], 2) }}
                        @else
                            —
                        @endif
                    </td>
                    @endforeach
                    <td class="num">{{ number_format($rowTotal, 2) }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>{{ $tr['col_total_kg'] }}</td>
                    @foreach ($categories as $cat)
                    <td class="num">{{ number_format($summary[$cat]['total_kg'] ?? 0, 2) }}</td>
                    @endforeach
                    <td class="num">{{ number_format($grandTotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Individual entries (only for periods ≤ 31 days) -->
    @if ($individualEntries->isNotEmpty())
    <div class="section">
        <h2>{{ $tr['individual_title'] }}</h2>
        <p class="entries-note">{{ str_replace(':count', $individualEntries->count(), $tr['showing_entries']) }}</p>
        <table>
            <thead>
                <tr>
                    <th>{{ $tr['col_date'] }}</th>
                    <th>{{ $tr['col_item'] }}</th>
                    <th>{{ $tr['col_category'] }}</th>
                    <th class="num">{{ $tr['col_weight'] }}</th>
                    <th>{{ $tr['col_reason'] }}</th>
                    <th>{{ $tr['col_source'] }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($individualEntries as $entry)
                <tr>
                    <td>{{ $entry->logged_at->toDateString() }}</td>
                    <td>{{ $entry->item_name }}</td>
                    <td><span class="badge badge-{{ $entry->category }}">{{ $tr['categories'][$entry->category] ?? ucfirst($entry->category) }}</span></td>
                    <td class="num">{{ number_format((float) $entry->weight_kg, 3) }}</td>
                    <td>{{ $tr['reasons'][$entry->reason] ?? $entry->reason }}</td>
                    <td>{{ $entry->source === 'ai_scan' ? $tr['source_scan'] : $tr['source_manual'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Attestation block -->
    <div class="attestation">
        <h3>{{ $tr['attestation_title'] }}</h3>
        <p style="margin-top: 6px; font-size: 10px; color: #555;">
            {{ str_replace([':name', ':from', ':to'], [config('app.name'), $dateFrom, $dateTo], $tr['attestation_text']) }}
        </p>
        <div class="signature-row">
            <div>
                <div class="signature-line"></div>
                <div style="margin-top: 4px; font-size: 9px; color: #777;">{{ $tr['sig_signature'] }}</div>
            </div>
            <div>
                <div class="signature-line"></div>
                <div style="margin-top: 4px; font-size: 9px; color: #777;">{{ $tr['sig_date'] }}</div>
            </div>
            <div>
                <div class="signature-line"></div>
                <div style="margin-top: 4px; font-size: 9px; color: #777;">{{ $tr['sig_title'] }}</div>
            </div>
        </div>
    </div>

</div>
</body>
</html>
