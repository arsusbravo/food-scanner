export type WasteCategory = 'protein' | 'veg' | 'dairy' | 'prepared';

export type WasteReason = 'spoilage' | 'overproduction' | 'expiry' | 'prep_waste' | 'other';

export type WasteSource = 'manual' | 'ai_scan';

export type WasteEntry = {
    id: number;
    user_id: number;
    category: WasteCategory;
    item_name: string;
    weight_kg: string;
    reason: WasteReason;
    notes: string | null;
    logged_at: string;
    source: WasteSource;
    photo_path: string | null;
    ai_raw_response: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type WasteReportRow = {
    category: WasteCategory;
    flw_group: string;
    eu_code: string;
    total_kg: number;
    entry_count: number;
    by_reason: Partial<Record<WasteReason, { total_kg: number; entry_count: number }>>;
};

export const CATEGORY_LABELS: Record<WasteCategory, string> = {
    protein: 'Protein',
    veg: 'Vegetables',
    dairy: 'Dairy',
    prepared: 'Prepared',
};

export const CATEGORY_EMOJIS: Record<WasteCategory, string> = {
    protein: '🥩',
    veg: '🥬',
    dairy: '🧀',
    prepared: '🍲',
};

export const CATEGORY_COLORS: Record<WasteCategory, string> = {
    protein: 'border-red-400 bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300',
    veg: 'border-green-400 bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300',
    dairy: 'border-yellow-400 bg-yellow-50 text-yellow-700 dark:bg-yellow-950/30 dark:text-yellow-300',
    prepared: 'border-orange-400 bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-300',
};

export const REASON_LABELS: Record<WasteReason, string> = {
    spoilage: 'Spoilage',
    overproduction: 'Overproduction',
    expiry: 'Expiry',
    prep_waste: 'Prep Waste',
    other: 'Other',
};
