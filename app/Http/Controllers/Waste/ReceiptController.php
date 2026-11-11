<?php

namespace App\Http\Controllers\Waste;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateReceiptRequest;
use App\Models\Receipt;
use App\Models\ReceiptImage;
use App\Models\UserExport;
use App\Support\FoodWasteScanException;
use App\Support\ReceiptScanner;
use App\Support\XlsxWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptController extends Controller
{
    private const MAX_PAGES = 4;

    public function index(Request $request): Response
    {
        $user = $request->user();
        [$from, $to] = $this->period($request);
        $search = trim((string) $request->query('q', ''));

        $query = $user->receipts()
            ->when($from, fn ($q) => $q->whereDate('invoice_date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('invoice_date', '<=', $to->toDateString()))
            ->when($search !== '', fn ($q) => $q->where('supplier_name', 'like', '%'.$search.'%'));

        // Drafts always show, whatever their invoice date: they still need a review.
        $drafts = $user->receipts()
            ->where('status', Receipt::STATUS_DRAFT)
            ->withCount('lines')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Receipt $receipt) => $this->summary($receipt));

        $receipts = (clone $query)
            ->confirmed()
            ->withCount('lines')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Receipt $receipt) => $this->summary($receipt));

        $confirmed = (clone $query)->confirmed();

        return Inertia::render('waste/receipts/Index', [
            'receipts' => $receipts,
            'drafts' => $drafts,
            'dateFrom' => $from?->toDateString() ?? '',
            'dateTo' => $to?->toDateString() ?? '',
            'search' => $search,
            'totalSpend' => round((float) (clone $confirmed)->sum('gross_total'), 2),
            'confirmedCount' => (clone $confirmed)->count(),
            'currency' => $user->currency,
            'quota' => [
                'used' => $user->receiptScansUsedThisMonth(),
                'limit' => $user->receiptQuota(),
            ],
            'exportQuota' => [
                'used' => $user->exportsUsedThisMonth(),
                'limit' => $user->exportQuota(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('waste/receipts/Scan', [
            'quota' => [
                'used' => $user->receiptScansUsedThisMonth(),
                'limit' => $user->receiptQuota(),
            ],
            'maxPages' => self::MAX_PAGES,
        ]);
    }

    /**
     * Scans the photo(s) of ONE receipt (several photos = several pages),
     * stores the originals and saves the result as a draft.
     */
    public function scan(Request $request, ReceiptScanner $scanner): JsonResponse
    {
        $user = $request->user();

        if (! $user->canScanReceipt()) {
            return response()->json([
                'error' => "Monthly receipt scan limit reached ({$user->receiptQuota()} receipts). Upgrade to Pro for more.",
            ], 403);
        }

        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:'.self::MAX_PAGES],
            'photos.*' => ['required', 'string', 'min:100', 'max:7000000'],
        ]);

        $images = [];

        foreach ($validated['photos'] as $photo) {
            $binary = base64_decode($photo, true);

            if ($binary === false || @getimagesizefromstring($binary) === false) {
                return response()->json(['error' => 'One of the files is not a valid image.'], 422);
            }

            $images[] = $binary;
        }

        try {
            $results = $scanner->scan($validated['photos']);
        } catch (FoodWasteScanException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // One photo can show several receipts. Save as many drafts as the monthly
        // allowance still permits and tell the client about the rest.
        $quota = $user->receiptQuota();
        $room = $quota === null ? count($results) : max(0, $quota - $user->receiptScansUsedThisMonth());
        $toSave = array_slice($results, 0, $room);

        $paths = [];

        foreach ($images as $binary) {
            $paths[] = "receipts/{$user->id}/".Str::uuid().'.jpg';
            Storage::put(end($paths), $binary);
        }

        $receipts = DB::transaction(function () use ($user, $toSave, $paths) {
            return collect($toSave)->map(function (array $result) use ($user, $paths) {
                $lines = $result['lines'];
                unset($result['lines']);

                $receipt = $user->receipts()->create([
                    ...$result,
                    'invoice_date' => $result['invoice_date'] ?? now()->toDateString(),
                    'status' => Receipt::STATUS_DRAFT,
                ]);

                $receipt->lines()->createMany($lines);

                foreach ($paths as $page => $path) {
                    $receipt->images()->create(['path' => $path, 'page' => $page + 1]);
                }

                return $receipt->setRelation('lines', $receipt->lines()->get());
            });
        });

        if ($receipts->isEmpty()) {
            collect($paths)->each(fn (string $path) => Storage::delete($path));
        }

        return response()->json([
            'receipts' => $receipts->map(fn (Receipt $receipt) => $this->editable($receipt->load('lines')))->values(),
            'skipped' => count($results) - $receipts->count(),
            'categories' => Receipt::LINE_CATEGORIES,
        ]);
    }

    public function show(Request $request, Receipt $receipt): Response
    {
        $this->authorizeOwner($request, $receipt);

        $receipt->load(['lines', 'images']);
        $duplicate = $receipt->findDuplicate();

        return Inertia::render('waste/receipts/Show', [
            'receipt' => [
                ...$this->editable($receipt),
                'images' => $receipt->images->map(fn (ReceiptImage $image) => [
                    'id' => $image->id,
                    'page' => $image->page,
                    'url' => route('waste.receipts.image', [$receipt, $image]),
                ])->values(),
            ],
            'duplicateId' => $duplicate?->id,
            'lineTotalMismatch' => $receipt->hasLineTotalMismatch(),
            'categories' => Receipt::LINE_CATEGORIES,
        ]);
    }

    public function update(UpdateReceiptRequest $request, Receipt $receipt): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $lines = collect($validated['lines'])->map(function (array $line) {
            return [
                ...$line,
                'normalized_item' => ReceiptScanner::normalizeItem($line['description']),
            ];
        });

        DB::transaction(function () use ($receipt, $validated, $lines, $request) {
            $receipt->update([
                ...collect($validated)->except(['lines', 'confirm'])->all(),
                'currency' => strtoupper($validated['currency']),
                'status' => $request->boolean('confirm') ? Receipt::STATUS_CONFIRMED : $receipt->status,
            ]);

            $receipt->lines()->delete();
            $receipt->lines()->createMany($lines->all());
        });

        // The scan screen confirms receipts in place and expects JSON instead of a redirect.
        if ($request->wantsJson()) {
            return response()->json(['id' => $receipt->id, 'status' => $receipt->fresh()->status]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $request->boolean('confirm') ? 'Receipt saved.' : 'Changes saved.']);

        return $request->boolean('confirm')
            ? to_route('waste.receipts.index')
            : back();
    }

    public function destroy(Request $request, Receipt $receipt): RedirectResponse|JsonResponse
    {
        $this->authorizeOwner($request, $receipt);

        $receipt->delete();

        if ($request->wantsJson()) {
            return response()->json(['deleted' => true]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Receipt deleted.']);

        return to_route('waste.receipts.index');
    }

    public function image(Request $request, Receipt $receipt, ReceiptImage $image): StreamedResponse
    {
        $this->authorizeOwner($request, $receipt);
        abort_unless($image->receipt_id === $receipt->id && Storage::exists($image->path), 404);

        return Storage::response($image->path, null, ['Content-Type' => 'image/jpeg']);
    }

    /**
     * Export of confirmed receipts as a real Excel file (`format=xlsx`, default)
     * or as plain comma-separated CSV (`format=csv`) for other tools.
     */
    public function exportCsv(Request $request, XlsxWriter $xlsx): StreamedResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user->canExport()) {
            return back()->withErrors(['quota' => "Monthly export limit reached ({$user->exportQuota()} exports). Upgrade to Pro for unlimited exports."]);
        }

        $validated = $request->validate([
            'mode' => ['nullable', 'in:receipts,lines'],
            'format' => ['nullable', 'in:xlsx,csv'],
        ]);

        $mode = $validated['mode'] ?? 'receipts';
        $asExcel = ($validated['format'] ?? 'xlsx') === 'xlsx' && XlsxWriter::isAvailable();
        [$from, $to] = $this->period($request);

        UserExport::create(['user_id' => $user->id, 'type' => 'csv']);

        $receipts = $user->receipts()
            ->confirmed()
            ->with('lines')
            ->when($from, fn ($q) => $q->whereDate('invoice_date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('invoice_date', '<=', $to->toDateString()))
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        [$header, $types, $rows] = $mode === 'lines' ? $this->lineRows($receipts) : $this->receiptRows($receipts);

        $range = $from || $to ? ($from?->toDateString() ?? 'start').'-to-'.($to?->toDateString() ?? 'today') : 'all';
        $filename = "receipts-{$mode}-{$range}.".($asExcel ? 'xlsx' : 'csv');

        if ($asExcel) {
            $contents = $xlsx->build($header, $types, $rows, $mode === 'lines' ? 'Lines' : 'Receipts');

            return response()->streamDownload(function () use ($contents) {
                echo $contents;
            }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        }

        return response()->streamDownload(function () use ($header, $types, $rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, $header, ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($value, $type) => match (true) {
                    $value === null => '',
                    $type === XlsxWriter::MONEY => number_format((float) $value, 2, '.', ''),
                    default => (string) $value,
                }, $row, $types), ',', '"', '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: list<string>, 1: list<string>, 2: list<list<mixed>>} header, column types, rows
     */
    private function receiptRows($receipts): array
    {
        $header = ['Date', 'Supplier', 'Supplier VAT ID', 'Invoice number', 'Currency', 'Net', 'VAT', 'Gross', 'Lines', 'Notes'];
        $types = [XlsxWriter::DATE, XlsxWriter::TEXT, XlsxWriter::TEXT, XlsxWriter::TEXT, XlsxWriter::TEXT, XlsxWriter::MONEY, XlsxWriter::MONEY, XlsxWriter::MONEY, XlsxWriter::INTEGER, XlsxWriter::TEXT];
        $rows = [];

        foreach ($receipts as $receipt) {
            $rows[] = [
                $receipt->invoice_date?->toDateString(),
                $receipt->supplier_name,
                $receipt->supplier_vat_id,
                $receipt->invoice_number,
                $receipt->currency,
                $receipt->net_total !== null ? (float) $receipt->net_total : null,
                $receipt->vat_total !== null ? (float) $receipt->vat_total : null,
                $receipt->gross_total !== null ? (float) $receipt->gross_total : null,
                $receipt->lines->count(),
                $receipt->notes,
            ];
        }

        return [$header, $types, $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<string>, 2: list<list<mixed>>} header, column types, rows
     */
    private function lineRows($receipts): array
    {
        $header = ['Date', 'Supplier', 'Invoice number', 'Description', 'Category', 'Amount', 'Currency'];
        $types = [XlsxWriter::DATE, XlsxWriter::TEXT, XlsxWriter::TEXT, XlsxWriter::TEXT, XlsxWriter::TEXT, XlsxWriter::MONEY, XlsxWriter::TEXT];
        $rows = [];

        foreach ($receipts as $receipt) {
            foreach ($receipt->lines as $line) {
                $rows[] = [
                    $receipt->invoice_date?->toDateString(),
                    $receipt->supplier_name,
                    $receipt->invoice_number,
                    $line->description,
                    $line->category,
                    $line->line_total !== null ? (float) $line->line_total : null,
                    $receipt->currency,
                ];
            }
        }

        return [$header, $types, $rows];
    }

    /**
     * Everything the review form needs for one receipt (lines must be loaded).
     *
     * @return array<string, mixed>
     */
    private function editable(Receipt $receipt): array
    {
        return [
            'id' => $receipt->id,
            'url' => route('waste.receipts.show', $receipt),
            'status' => $receipt->status,
            'supplier_name' => $receipt->supplier_name,
            'supplier_vat_id' => $receipt->supplier_vat_id,
            'invoice_number' => $receipt->invoice_number,
            'invoice_date' => $receipt->invoice_date?->toDateString(),
            'currency' => $receipt->currency,
            'net_total' => $receipt->net_total,
            'vat_total' => $receipt->vat_total,
            'gross_total' => $receipt->gross_total,
            'confidence' => $receipt->confidence,
            'notes' => $receipt->notes,
            'duplicate_id' => $receipt->findDuplicate()?->id,
            'lines' => $receipt->lines->map(fn ($line) => [
                'description' => $line->description,
                'line_total' => $line->line_total,
                'category' => $line->category,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Receipt $receipt): array
    {
        return [
            'id' => $receipt->id,
            'status' => $receipt->status,
            'supplier_name' => $receipt->supplier_name,
            'invoice_number' => $receipt->invoice_number,
            'invoice_date' => $receipt->invoice_date?->toDateString(),
            'currency' => $receipt->currency,
            'gross_total' => $receipt->gross_total,
            'lines_count' => $receipt->lines_count,
        ];
    }

    /**
     * Optional date range. Receipts are dated by the date printed on them, which
     * is often older than the scan, so there is no default range: everything
     * is listed until the user narrows it down.
     *
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function period(Request $request): array
    {
        $from = $request->query('date_from') ? Carbon::parse($request->query('date_from'))->startOfDay() : null;
        $to = $request->query('date_to') ? Carbon::parse($request->query('date_to'))->endOfDay() : null;

        return [$from, $to];
    }

    private function authorizeOwner(Request $request, Receipt $receipt): void
    {
        abort_unless($receipt->user_id === $request->user()->id, 404);
    }
}
