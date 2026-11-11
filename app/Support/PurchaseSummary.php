<?php

namespace App\Support;

use App\Models\Receipt;
use App\Models\ReceiptLine;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Spend figures from a user's confirmed receipts. Dates are invoice dates and
 * either bound may be null (open ended).
 */
class PurchaseSummary
{
    public function __construct(private readonly User $user) {}

    /**
     * @return array{spend: float, vat: float, count: int}
     */
    public function totals(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $row = $this->receipts($from, $to)
            ->selectRaw('COALESCE(SUM(gross_total), 0) as spend, COALESCE(SUM(vat_total), 0) as vat, COUNT(*) as receipts')
            ->first();

        return [
            'spend' => round((float) $row->spend, 2),
            'vat' => round((float) $row->vat, 2),
            'count' => (int) $row->receipts,
        ];
    }

    /**
     * @return list<array{supplier: string|null, total: float, count: int}>
     */
    public function bySupplier(?CarbonInterface $from = null, ?CarbonInterface $to = null, int $limit = 10): array
    {
        return $this->receipts($from, $to)
            ->selectRaw('supplier_name, SUM(gross_total) as total, COUNT(*) as receipts')
            ->groupBy('supplier_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'supplier' => $row->supplier_name,
                'total' => round((float) $row->total, 2),
                'count' => (int) $row->receipts,
            ])
            ->all();
    }

    /**
     * Sum of line amounts per category.
     *
     * @return array<string, float>
     */
    public function byCategory(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        return $this->lines($from, $to)
            ->selectRaw('receipt_lines.category as category, SUM(receipt_lines.line_total) as total')
            ->groupBy('receipt_lines.category')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->category => round((float) $row->total, 2)])
            ->all();
    }

    /**
     * Spend on food (not drinks, packaging, ...), for comparing with waste.
     */
    public function foodSpend(?CarbonInterface $from = null, ?CarbonInterface $to = null): float
    {
        return round((float) $this->lines($from, $to)
            ->whereIn('receipt_lines.category', Receipt::FOOD_CATEGORIES)
            ->sum('receipt_lines.line_total'), 2);
    }

    /**
     * @return Builder<Receipt>
     */
    private function receipts(?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        return Receipt::query()
            ->where('user_id', $this->user->id)
            ->confirmed()
            ->when($from, fn (Builder $query) => $query->whereDate('invoice_date', '>=', $from->toDateString()))
            ->when($to, fn (Builder $query) => $query->whereDate('invoice_date', '<=', $to->toDateString()));
    }

    /**
     * @return Builder<ReceiptLine>
     */
    private function lines(?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        return ReceiptLine::query()
            ->join('receipts', 'receipts.id', '=', 'receipt_lines.receipt_id')
            ->where('receipts.user_id', $this->user->id)
            ->where('receipts.status', Receipt::STATUS_CONFIRMED)
            ->when($from, fn (Builder $query) => $query->whereDate('receipts.invoice_date', '>=', $from->toDateString()))
            ->when($to, fn (Builder $query) => $query->whereDate('receipts.invoice_date', '<=', $to->toDateString()));
    }
}
