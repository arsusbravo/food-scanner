<?php

namespace App\Console\Commands;

use App\Models\ReceiptLine;
use App\Support\PriceBasis;
use Illuminate\Console\Command;

class BackfillReceiptPrices extends Command
{
    protected $signature = 'receipts:backfill-prices';

    protected $description = 'Compute price tracking data for receipt lines saved before price tracking existed';

    public function handle(): int
    {
        $updated = 0;

        ReceiptLine::whereNull('base_unit')
            ->whereNotNull('line_total')
            ->where('line_total', '>', 0)
            ->chunkById(500, function ($lines) use (&$updated): void {
                foreach ($lines as $line) {
                    $basis = PriceBasis::compute(
                        $line->description,
                        (float) $line->line_total,
                        $line->quantity !== null ? (float) $line->quantity : null,
                        $line->unit,
                        $line->unit_price !== null ? (float) $line->unit_price : null,
                    );

                    if ($basis['base_unit'] !== null) {
                        $line->update($basis);
                        $updated++;
                    }
                }
            });

        $this->info("Updated {$updated} receipt lines.");

        return self::SUCCESS;
    }
}
