<?php

namespace App\Support;

use App\Models\ProductLink;
use App\Models\Receipt;
use App\Models\ReceiptLine;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Price changes and supplier comparison for one user, from the price
 * observations on their confirmed receipts (see PriceBasis). Products are
 * identified by their cleaned name, optionally merged by ProductLink.
 */
class PriceWatch
{
    /** Smallest relative price change that is reported (10%). */
    public const CHANGE_THRESHOLD = 0.10;

    /** Changes older than this many days are not reported. */
    public const CHANGE_WINDOW_DAYS = 180;

    /** Only prices from the last N days take part in supplier comparison. */
    public const COMPARE_WINDOW_DAYS = 90;

    /** A cheaper supplier must save at least this share to be suggested. */
    public const MIN_SAVING = 0.05;

    private const MAX_SUGGESTIONS = 10;

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $cache = null;

    public function __construct(private readonly User $user) {}

    /**
     * Number of confirmed receipt lines that carry a price observation.
     */
    public function trackedLines(): int
    {
        return $this->observations()->count();
    }

    /**
     * @return list<array<string, mixed>> biggest rise first
     */
    public function changes(): array
    {
        $since = now()->subDays(self::CHANGE_WINDOW_DAYS)->startOfDay();
        $changes = [];

        $groups = $this->observations()->groupBy(fn (array $o) => implode('|', [$o['key'], $o['bucket'], $o['supplier'] ?? '', $o['unit']]));

        foreach ($groups as $group) {
            $latest = $group->last();
            $previous = $group->last(fn (array $o) => $o['date']->lt($latest['date']));

            if ($previous === null || $previous['price'] <= 0 || $latest['date']->lt($since)) {
                continue;
            }

            $change = $latest['price'] / $previous['price'] - 1;

            if (abs($change) < self::CHANGE_THRESHOLD) {
                continue;
            }

            $changes[] = [
                'product' => $latest['name'],
                'supplier' => $latest['supplier'],
                'unit' => $latest['unit'],
                'pack' => $this->packLabel($latest),
                'previous' => ['price' => $previous['price'], 'date' => $previous['date']->toDateString()],
                'latest' => ['price' => $latest['price'], 'date' => $latest['date']->toDateString()],
                'change_pct' => round($change * 100, 1),
                'impact' => round(($latest['price'] - $previous['price']) * $latest['quantity'], 2),
                'history' => $group->slice(-6)->map(fn (array $o) => ['date' => $o['date']->toDateString(), 'price' => $o['price']])->values()->all(),
            ];
        }

        usort($changes, fn (array $a, array $b) => $b['change_pct'] <=> $a['change_pct']);

        return $changes;
    }

    /**
     * Products bought from more than one supplier where another supplier is
     * cheaper than the one you buy most from.
     *
     * @return list<array<string, mixed>> biggest monthly saving first
     */
    public function comparisons(): array
    {
        $since = now()->subDays(self::COMPARE_WINDOW_DAYS)->startOfDay();
        $months = self::COMPARE_WINDOW_DAYS / 30;
        $comparisons = [];

        $groups = $this->observations()
            ->filter(fn (array $o) => $o['date']->gte($since))
            ->groupBy(fn (array $o) => $o['key'].'|'.$o['unit']);

        foreach ($groups as $group) {
            $bySupplier = $group->groupBy(fn (array $o) => $o['supplier'] ?? '');

            if ($bySupplier->count() < 2) {
                continue;
            }

            $latest = $bySupplier->map(fn (Collection $obs) => $obs->last());
            $unit = $group->first()['unit'];
            $buckets = $latest->pluck('bucket')->unique();

            if ($buckets->count() === 1) {
                $basis = $unit === 'pc' ? 'item' : $unit;
                $price = fn (array $o) => $o['price'];
                $quantity = fn (array $o) => $o['quantity'];
            } elseif ($unit === 'pc' && $latest->every(fn (array $o) => $o['pack_size'] !== null) && $latest->pluck('pack_unit')->unique()->count() === 1) {
                // Different pack sizes: compare per kg or litre instead of per item.
                $basis = $latest->first()['pack_unit'];
                $price = fn (array $o) => $o['price'] / $o['pack_size'];
                $quantity = fn (array $o) => $o['quantity'] * ($o['pack_size'] ?? 0);
            } else {
                continue;
            }

            $main = $bySupplier->map(fn (Collection $obs) => $obs->sum($quantity))->sortDesc()->keys()->first();
            $mainLatest = $latest[$main];
            $cheapest = $latest->sortBy($price)->first();
            $mainPrice = $price($mainLatest);

            if ($cheapest === $mainLatest || $mainPrice <= 0 || ($mainPrice - $price($cheapest)) / $mainPrice < self::MIN_SAVING) {
                continue;
            }

            $savingPerUnit = $mainPrice - $price($cheapest);
            $mainQuantity = $bySupplier[$main]->sum($quantity);

            $comparisons[] = [
                'product' => $mainLatest['name'],
                'basis' => $basis,
                'current' => ['supplier' => $mainLatest['supplier'], 'price' => round($mainPrice, 2), 'pack' => $this->packLabel($mainLatest), 'date' => $mainLatest['date']->toDateString()],
                'cheaper' => ['supplier' => $cheapest['supplier'], 'price' => round($price($cheapest), 2), 'pack' => $this->packLabel($cheapest), 'date' => $cheapest['date']->toDateString()],
                'saving_per_unit' => round($savingPerUnit, 2),
                'saving_pct' => round($savingPerUnit / $mainPrice * 100, 1),
                'monthly_saving' => round($savingPerUnit * ($mainQuantity / $months), 2),
            ];
        }

        usort($comparisons, fn (array $a, array $b) => $b['monthly_saving'] <=> $a['monthly_saving']);

        return $comparisons;
    }

    /**
     * Pairs of products that are probably the same thing under different names
     * at different suppliers. Never applied automatically.
     *
     * @return list<array<string, mixed>>
     */
    public function suggestions(): array
    {
        $since = now()->subDays(self::CHANGE_WINDOW_DAYS)->startOfDay();

        $products = $this->observations()
            ->filter(fn (array $o) => $o['date']->gte($since))
            ->groupBy('key')
            ->map(fn (Collection $obs, string $key) => [
                'key' => $key,
                'name' => $obs->last()['name'],
                'suppliers' => $obs->pluck('supplier')->filter()->unique()->values()->all(),
                'units' => $obs->pluck('unit')->unique()->values()->all(),
                'count' => $obs->count(),
            ])
            ->values()
            ->take(300);

        $pairs = [];

        foreach ($products as $i => $a) {
            foreach ($products->slice($i + 1) as $b) {
                if ($a['suppliers'] === [] || $b['suppliers'] === [] || array_intersect($a['suppliers'], $b['suppliers']) !== [] || array_intersect($a['units'], $b['units']) === []) {
                    continue;
                }

                $score = self::similarity($a['key'], $b['key']);

                if ($score < 0.6) {
                    continue;
                }

                // The less used name is merged into the more used one.
                [$from, $to] = $a['count'] <= $b['count'] ? [$a, $b] : [$b, $a];

                $pairs[] = [
                    'from_key' => $from['key'],
                    'to_key' => $to['key'],
                    'from_name' => $from['name'],
                    'to_name' => $to['name'],
                    'from_suppliers' => $from['suppliers'],
                    'to_suppliers' => $to['suppliers'],
                    'score' => round($score, 2),
                ];
            }
        }

        usort($pairs, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($pairs, 0, self::MAX_SUGGESTIONS);
    }

    /**
     * Existing manual links with readable names, so they can be undone.
     *
     * @return list<array{id: int, name: string, linked_to: string}>
     */
    public function links(): array
    {
        $links = ProductLink::where('user_id', $this->user->id)->orderBy('id')->get();

        if ($links->isEmpty()) {
            return [];
        }

        $names = $this->namesFor($links->pluck('item_key')->merge($links->pluck('canonical_key'))->unique()->all());

        return $links->map(fn (ProductLink $link) => [
            'id' => $link->id,
            'name' => $names[$link->item_key] ?? $link->item_key,
            'linked_to' => $names[$link->canonical_key] ?? $link->canonical_key,
        ])->all();
    }

    /**
     * Treat two cleaned names as the same product. Both must exist on the
     * user's receipt lines. Returns false when there is nothing to do.
     */
    public function link(string $fromKey, string $toKey): bool
    {
        $links = ProductLink::where('user_id', $this->user->id)->pluck('canonical_key', 'item_key');

        $from = $links[$fromKey] ?? $fromKey;
        $to = $links[$toKey] ?? $toKey;

        if ($from === $to || ! $this->hasLines([$fromKey, $toKey])) {
            return false;
        }

        // Anything that pointed at the old canonical key now points at the new one.
        ProductLink::where('user_id', $this->user->id)->where('canonical_key', $from)->update(['canonical_key' => $to]);
        ProductLink::updateOrCreate(['user_id' => $this->user->id, 'item_key' => $from], ['canonical_key' => $to]);

        $this->cache = null;

        return true;
    }

    public static function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        $tokensA = array_filter(explode(' ', $a));
        $tokensB = array_filter(explode(' ', $b));
        $union = count(array_unique(array_merge($tokensA, $tokensB)));
        $jaccard = $union > 0 ? count(array_intersect($tokensA, $tokensB)) / $union : 0.0;

        $contains = min(strlen($a), strlen($b)) >= 4 && (str_contains($a, $b) || str_contains($b, $a)) ? 0.8 : 0.0;

        similar_text($a, $b, $percent);
        $alike = $percent >= 75 ? $percent / 100 : 0.0;

        return max($jaccard, $contains, $alike);
    }

    /**
     * @param  array<string, mixed>  $observation
     */
    private function packLabel(array $observation): ?string
    {
        if ($observation['pack_size'] === null || $observation['pack_unit'] === null) {
            return null;
        }

        return $observation['pack_unit'] === 'kg'
            ? ($observation['pack_size'] < 1 ? round($observation['pack_size'] * 1000).' g' : $observation['pack_size'].' kg')
            : ($observation['pack_size'] < 1 ? round($observation['pack_size'] * 1000).' ml' : $observation['pack_size'].' l');
    }

    /**
     * @param  list<string>  $keys
     */
    private function hasLines(array $keys): bool
    {
        return $this->lines()->whereIn('receipt_lines.normalized_item', $keys)->distinct()->count('receipt_lines.normalized_item') === count(array_unique($keys));
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string> cleaned key => latest description
     */
    private function namesFor(array $keys): array
    {
        return $this->lines()
            ->whereIn('receipt_lines.normalized_item', $keys)
            ->orderBy('receipts.invoice_date')
            ->orderBy('receipt_lines.id')
            ->get(['receipt_lines.normalized_item', 'receipt_lines.description'])
            ->mapWithKeys(fn ($row) => [$row->normalized_item => $row->description])
            ->all();
    }

    private function lines()
    {
        return ReceiptLine::query()
            ->join('receipts', 'receipts.id', '=', 'receipt_lines.receipt_id')
            ->where('receipts.user_id', $this->user->id)
            ->where('receipts.status', Receipt::STATUS_CONFIRMED);
    }

    /**
     * All price observations, oldest first, with product keys resolved through links.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function observations(): Collection
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $links = ProductLink::where('user_id', $this->user->id)->pluck('canonical_key', 'item_key');

        return $this->cache = $this->lines()
            ->whereNotNull('receipt_lines.base_price')
            ->whereNotNull('receipt_lines.base_unit')
            ->whereNotNull('receipt_lines.normalized_item')
            ->where('receipt_lines.normalized_item', '!=', '')
            ->orderBy('receipts.invoice_date')
            ->orderBy('receipt_lines.id')
            ->get([
                'receipt_lines.normalized_item', 'receipt_lines.description', 'receipt_lines.base_unit', 'receipt_lines.base_price',
                'receipt_lines.base_quantity', 'receipt_lines.pack_size', 'receipt_lines.pack_unit',
                'receipts.supplier_name', 'receipts.invoice_date',
            ])
            ->map(fn ($row) => [
                'key' => $links[$row->normalized_item] ?? $row->normalized_item,
                'name' => $row->description,
                'bucket' => PriceBasis::bucket($row->pack_size !== null ? (float) $row->pack_size : null, $row->pack_unit),
                'supplier' => $row->supplier_name ?: null,
                'date' => Carbon::parse($row->invoice_date)->startOfDay(),
                'unit' => $row->base_unit,
                'price' => (float) $row->base_price,
                'quantity' => (float) ($row->base_quantity ?? 1),
                'pack_size' => $row->pack_size !== null ? (float) $row->pack_size : null,
                'pack_unit' => $row->pack_unit,
            ])
            ->values();
    }
}
