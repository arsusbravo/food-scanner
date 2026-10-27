<?php

namespace App\Support;

use App\Models\User;
use App\Models\WasteEntry;

/**
 * Remembers the price per kg a user last paid for an item, based on their
 * previous entries that carry a cost. Used to pre-fill the cost field.
 */
class ItemPriceSuggester
{
    /**
     * @return array<string, float> lower-cased item name => cost per kg
     */
    public function forUser(User $user): array
    {
        $prices = [];

        WasteEntry::where('user_id', $user->id)
            ->whereNotNull('cost')
            ->where('weight_kg', '>', 0)
            ->orderByDesc('logged_at')
            ->limit(300)
            ->get(['item_name', 'cost', 'weight_kg'])
            ->each(function (WasteEntry $entry) use (&$prices): void {
                $key = mb_strtolower(trim($entry->item_name));

                $prices[$key] ??= round((float) $entry->cost / (float) $entry->weight_kg, 2);
            });

        return $prices;
    }
}
