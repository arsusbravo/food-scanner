<?php

namespace App\Support;

use App\Models\Receipt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Reads supplier receipts / invoices from photos and returns a normalised
 * draft (header + line items). Stateless: it never persists anything.
 */
class ReceiptScanner
{
    private const MAX_LINES = 150;

    private const MAX_DOCUMENTS = 10;

    private const SYSTEM_PROMPT = <<<'PROMPT'
You are a bookkeeping assistant for a restaurant. Read every supplier receipt or invoice in the image(s) and return ONLY a valid JSON object of the form {"receipts": [ ... ]}, where each element describes ONE document:
{
  "supplier_name": "string or null",
  "supplier_vat_id": "string or null",
  "invoice_number": "string or null",
  "invoice_date": "YYYY-MM-DD or null",
  "currency": "3-letter ISO code",
  "net_total": number or null,
  "vat_total": number or null,
  "gross_total": number or null,
  "confidence": "high"|"medium"|"low",
  "notes": "string or null",
  "lines": [
    { "description": "product text as printed", "amount": number, "quantity": number or null, "unit": "pc"|"kg"|"g"|"l"|"ml"|"cl"|null, "unit_price": number or null, "category": "protein"|"veg"|"dairy"|"prepared"|"beverage"|"dry_goods"|"non_food"|"other" }
  ]
}
Rules:
- Only copy what is printed. Never calculate or invent quantities, weights or unit prices.
- "quantity", "unit" and "unit_price" are filled ONLY when the receipt prints them for that row: a multiplier such as "2X" or "3 x" (quantity 2, unit "pc", unit_price = the printed price per item), or a weighed item with a printed weight and price per kg (quantity = weight, unit "kg" or "g", unit_price = printed price per kg). Most rows only have a description and an amount: then quantity, unit and unit_price MUST be null. A size inside the product name (for example "250 g") is part of the description, not a quantity.
- One line per printed product row: its description and the amount printed on that SAME row in the right-hand amount column. Read row by row and never move an amount to another row.
- Discounts, deposits and returns (for example "korting", "statiegeld", or any negative amount) are their own lines with the printed, negative amount.
- If a row has a multiplier such as "2X" followed by a unit price and a row total, the amount is the row total (the right-most number).
- gross_total is the amount due ("totaal"). Never use the cash handed over ("contant"), change ("wisselgeld"), rounding ("afronding") or a card payment line as the total. The line amounts should add up to it; check this before answering.
- Only include rows you can actually read. Never invent a document, a line or a number; use null for anything unreadable.
- Dates are day first (dd/mm/yyyy) unless clearly written otherwise. Output YYYY-MM-DD. Use the invoice or payment date.
- All numbers use a dot as decimal separator, no currency symbols or thousands separators.
- A single image may show several separate receipts or invoices (for example laid side by side on a table). Return one element per document, in reading order, and never merge two documents. Ignore bills that are cut off so much that no total is readable, and mention that in "notes" of the others.
- If several images are given they are consecutive pages of ONE document: return a single element.
- Keep descriptions as printed, do not translate. Use null when something is unreadable; never guess.
- Category guide: protein=meat/fish/eggs/legumes, veg=vegetables/fruit/herbs, dairy=milk/cheese/cream, prepared=ready-made food/sauces/bread/pastries/bars, beverage=drinks, dry_goods=flour/rice/pasta/oil/spices/canned/nuts/cereal, non_food=packaging/cleaning/equipment, other=anything else.
No text outside the JSON object.
PROMPT;

    public function __construct(private readonly AiVisionClient $client) {}

    /**
     * Reads all documents in the given image(s). Several images are treated as
     * pages of one document; a single image may contain several documents.
     *
     * @param  list<string>  $images  base64-encoded JPEG images
     * @return list<array<string, mixed>>
     *
     * @throws FoodWasteScanException
     */
    public function scan(array $images, string $logTag = 'ReceiptScan'): array
    {
        $drafts = $this->readOnce($images, 'Read every receipt in this image and return the JSON object.', $logTag);

        $problems = $this->mismatches($drafts);

        if ($problems === []) {
            return $drafts;
        }

        // Amounts are often shifted by a row on crumpled receipts. Ask once more,
        // telling the model what is wrong, and keep whichever answer is closer.
        Log::info("[{$logTag}] lines do not add up, retrying", ['documents' => array_keys($problems)]);

        try {
            $retry = $this->readOnce($images, sprintf(
                'Check your work. %s Read the receipts again row by row: every printed product row and every discount or deposit row must be a line, and each amount is the number on that same row in the right-hand column. Do not merge or drop documents. Return the corrected JSON object.',
                implode(' ', $problems),
            ), $logTag);
        } catch (FoodWasteScanException) {
            return $drafts;
        }

        $better = count($retry) >= count($drafts)
            && $this->totalDifference($retry) < $this->totalDifference($drafts);

        return $better ? $retry : $drafts;
    }

    /**
     * @param  list<string>  $images
     * @return list<array<string, mixed>>
     */
    private function readOnce(array $images, string $instruction, string $logTag): array
    {
        $raw = $this->client->send(self::SYSTEM_PROMPT, $images, $instruction, maxTokens: 8192, timeout: 90, logTag: $logTag, model: $this->model());

        $documents = $this->decode($raw);

        if ($documents === null) {
            Log::error("[{$logTag}] parse failed", ['raw' => substr($raw, 0, 1000)]);
            throw new FoodWasteScanException('Could not read this receipt. Please retake the photo with the whole page in view.');
        }

        return array_map(fn (array $document) => $this->normalise($document), array_slice($documents, 0, self::MAX_DOCUMENTS));
    }

    /**
     * Human-readable descriptions of documents whose lines do not add up.
     *
     * @param  list<array<string, mixed>>  $drafts
     * @return array<int, string>
     */
    private function mismatches(array $drafts): array
    {
        $problems = [];

        foreach ($drafts as $index => $draft) {
            if ($this->linesAddUp($draft)) {
                continue;
            }

            $problems[$index] = sprintf(
                'Document %d (%s): the line amounts add up to %s but its total is %s.',
                $index + 1,
                $draft['supplier_name'] ?? 'unknown supplier',
                number_format($this->lineSum($draft), 2, '.', ''),
                number_format((float) ($draft['gross_total'] ?? $draft['net_total'] ?? 0), 2, '.', ''),
            );
        }

        return $problems;
    }

    /**
     * @param  list<array<string, mixed>>  $drafts
     */
    private function totalDifference(array $drafts): float
    {
        return (float) array_sum(array_map(fn (array $draft) => $this->difference($draft), $drafts));
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    private function lineSum(array $draft): float
    {
        return (float) array_sum(array_map(fn (array $line) => $line['line_total'] ?? 0, $draft['lines']));
    }

    /**
     * Distance between the line amounts and the closest of the gross/net totals;
     * 0 when nothing can be compared.
     *
     * @param  array<string, mixed>  $draft
     */
    private function difference(array $draft): float
    {
        $totals = array_filter([$draft['gross_total'] ?? null, $draft['net_total'] ?? null], fn ($total) => $total !== null);

        if ($totals === [] || $draft['lines'] === []) {
            return 0.0;
        }

        $sum = $this->lineSum($draft);

        return min(array_map(fn ($total) => abs($sum - $total), $totals));
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    private function linesAddUp(array $draft): bool
    {
        $totals = array_filter([$draft['gross_total'] ?? null, $draft['net_total'] ?? null], fn ($total) => $total !== null);

        if ($totals === [] || $draft['lines'] === []) {
            return true;
        }

        $sum = $this->lineSum($draft);

        foreach ($totals as $total) {
            if (abs($sum - $total) <= max(0.05, 0.01 * $total)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Receipts use their own (stronger) model than the general photo scan.
     */
    private function model(): string
    {
        return (string) (config('services.ai.provider', 'anthropic') === 'openrouter'
            ? config('services.openrouter.receipt_model')
            : config('services.anthropic.receipt_model'));
    }

    /**
     * Accepts {"receipts": [...]} or, for older answers, a single receipt object.
     *
     * @return list<array<string, mixed>>|null
     */
    public function decode(string $raw): ?array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $cleaned = preg_replace('/\s*```$/i', '', trim((string) $cleaned));

        $data = json_decode(trim((string) $cleaned), true);

        if (! is_array($data)) {
            return null;
        }

        $documents = isset($data['receipts']) && is_array($data['receipts']) ? $data['receipts'] : [$data];

        $documents = array_values(array_filter($documents, fn ($document) => is_array($document) && array_key_exists('lines', $document)));

        return $documents === [] ? null : $documents;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalise(array $data): array
    {
        $lines = [];

        foreach (array_slice((array) ($data['lines'] ?? []), 0, self::MAX_LINES) as $line) {
            if (! is_array($line) || trim((string) ($line['description'] ?? '')) === '') {
                continue;
            }

            $lines[] = $this->normaliseLine($line);
        }

        return [
            'supplier_name' => $this->text($data['supplier_name'] ?? null, 150),
            'supplier_vat_id' => $this->text($data['supplier_vat_id'] ?? null, 50),
            'invoice_number' => $this->text($data['invoice_number'] ?? null, 100),
            'invoice_date' => $this->date($data['invoice_date'] ?? null),
            'currency' => $this->currency($data['currency'] ?? null),
            'net_total' => $this->number($data['net_total'] ?? null),
            'vat_total' => $this->number($data['vat_total'] ?? null),
            'gross_total' => $this->number($data['gross_total'] ?? null),
            'confidence' => in_array($data['confidence'] ?? null, ['high', 'medium', 'low'], true) ? $data['confidence'] : 'medium',
            'notes' => $this->text($data['notes'] ?? null, 1000),
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function normaliseLine(array $line): array
    {
        $description = (string) $this->text($line['description'], 255);
        $category = in_array($line['category'] ?? null, Receipt::LINE_CATEGORIES, true) ? $line['category'] : 'other';
        $lineTotal = $this->number($line['amount'] ?? $line['line_total'] ?? null);
        $quantity = $this->number($line['quantity'] ?? null);
        $unit = $this->unit($line['unit'] ?? null);
        $unitPrice = $this->number($line['unit_price'] ?? null);

        return [
            'description' => $description,
            'normalized_item' => self::normalizeItem($description),
            'line_total' => $lineTotal,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'category' => $category,
            ...PriceBasis::compute($description, $lineTotal, $quantity, $unit, $unitPrice),
        ];
    }

    /**
     * Lower-cased item name without quantities, units and punctuation, so the
     * same product on different receipts matches.
     */
    public static function normalizeItem(string $description): string
    {
        $text = mb_strtolower($description);
        $text = preg_replace('/\d+([.,]\d+)?\s*(kg|gr?|l|cl|ml|pcs?|st|x)?\b/u', ' ', $text);
        $text = preg_replace('/[^\p{L}\s]/u', ' ', (string) $text);

        return trim((string) preg_replace('/\s+/u', ' ', (string) $text));
    }

    /**
     * Parses numbers like 12.5, "12,50", "1.234,56" or "€ 12.50".
     */
    public function number(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = preg_replace('/[^\d.,\-]/', '', (string) $value);

        if ($text === '' || $text === '-') {
            return null;
        }

        $lastComma = strrpos($text, ',');
        $lastDot = strrpos($text, '.');

        if ($lastComma !== false && $lastDot !== false) {
            // The later separator is the decimal one.
            $text = $lastComma > $lastDot
                ? str_replace(',', '.', str_replace('.', '', $text))
                : str_replace(',', '', $text);
        } elseif ($lastComma !== false) {
            $text = str_replace(',', '.', $text);
        }

        return is_numeric($text) ? (float) $text : null;
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function currency(mixed $value): string
    {
        $code = strtoupper(trim((string) $value));

        return preg_match('/^[A-Z]{3}$/', $code) ? $code : 'EUR';
    }

    private function unit(mixed $value): ?string
    {
        $map = [
            'kg' => 'kg', 'kilo' => 'kg', 'kilogram' => 'kg',
            'g' => 'g', 'gr' => 'g', 'gram' => 'g',
            'l' => 'l', 'lt' => 'l', 'liter' => 'l', 'litre' => 'l',
            'ml' => 'ml', 'cl' => 'cl',
            'pc' => 'pc', 'pcs' => 'pc', 'st' => 'pc', 'stuk' => 'pc', 'stuks' => 'pc', 'x' => 'pc', 'piece' => 'pc',
        ];

        return $map[strtolower(trim((string) $value))] ?? null;
    }

    private function text(mixed $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : mb_substr($text, 0, $max);
    }
}
