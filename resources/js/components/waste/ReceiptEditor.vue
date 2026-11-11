<script setup lang="ts">
import { computed } from 'vue';
import { AlertTriangle, Plus, Trash2 } from 'lucide-vue-next';
import { formatMoney } from '@/lib/money';
import { show as showRoute } from '@/routes/waste/receipts';

export type EditorLine = {
    description: string;
    line_total: string | number | null;
    category: string;
};

export type EditorDraft = {
    supplier_name: string | null;
    supplier_vat_id: string | null;
    invoice_number: string | null;
    invoice_date: string | null;
    currency: string;
    net_total: string | number | null;
    vat_total: string | number | null;
    gross_total: string | number | null;
    notes: string | null;
    lines: EditorLine[];
};

defineProps<{
    errors: Record<string, string | undefined>;
    categories: string[];
    confidence?: 'high' | 'medium' | 'low';
    duplicateId?: number | null;
}>();

const draft = defineModel<EditorDraft>({ required: true });

const lineSum = computed(() => draft.value.lines.reduce((sum, line) => sum + (line.line_total === null || line.line_total === '' ? 0 : Number(line.line_total)), 0));

const hasLineTotals = computed(() => draft.value.lines.some((line) => line.line_total !== null && line.line_total !== ''));

const mismatch = computed(() => {
    if (!hasLineTotals.value) {
        return false;
    }

    const totals = [draft.value.gross_total, draft.value.net_total].filter((total) => total !== '' && total !== null).map(Number);

    return totals.length > 0 && !totals.some((total) => Math.abs(lineSum.value - total) <= Math.max(0.05, 0.01 * total));
});

function addLine() {
    draft.value.lines.push({ description: '', line_total: null, category: 'other' });
}

function removeLine(index: number) {
    draft.value.lines.splice(index, 1);
}

const inputClass = 'w-full h-11 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder:text-slate-400 [color-scheme:light] px-3 text-sm';
const smallInput = 'w-full h-10 rounded-lg border border-slate-200 bg-white px-2 text-sm text-slate-900 placeholder:text-slate-400 [color-scheme:light]';
</script>

<template>
    <div class="space-y-5">
    <!-- Warnings -->
    <div v-if="confidence === 'low'" class="flex gap-2 rounded-xl px-4 py-3 text-sm" style="background: #fefce8; color: #a16207; border: 1px solid #fde68a;">
        <AlertTriangle class="shrink-0 mt-0.5" style="width: 16px; height: 16px;" /> {{ $t('receipts.low_confidence') }}
    </div>
    <div v-if="duplicateId" class="flex gap-2 rounded-xl px-4 py-3 text-sm" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">
        <AlertTriangle class="shrink-0 mt-0.5" style="width: 16px; height: 16px;" />
        <span>{{ $t('receipts.duplicate_warning') }} <a :href="showRoute(duplicateId).url" target="_blank" rel="noopener" class="font-bold underline">{{ $t('receipts.open_other') }}</a></span>
    </div>
    <div v-if="mismatch" class="flex gap-2 rounded-xl px-4 py-3 text-sm" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
        <AlertTriangle class="shrink-0 mt-0.5" style="width: 16px; height: 16px;" />
        {{ $t('receipts.mismatch_warning', { sum: formatMoney(lineSum, draft.currency), total: formatMoney(Number(draft.gross_total || 0), draft.currency) }) }}
    </div>


    <!-- Header fields -->
    <div class="bg-white rounded-2xl border border-slate-100 p-4 space-y-3" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.supplier') }}</label>
            <input v-model="draft.supplier_name" type="text" :class="inputClass" />
            <p v-if="errors.supplier_name" class="text-xs mt-1" style="color:#dc2626;">{{ errors.supplier_name }}</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.invoice_date') }}</label>
                <input v-model="draft.invoice_date" type="date" :class="inputClass" />
                <p v-if="errors.invoice_date" class="text-xs mt-1" style="color:#dc2626;">{{ errors.invoice_date }}</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.invoice_number') }}</label>
                <input v-model="draft.invoice_number" type="text" :class="inputClass" />
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.vat_id') }}</label>
                <input v-model="draft.supplier_vat_id" type="text" :class="inputClass" />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.currency') }}</label>
                <input v-model="draft.currency" type="text" maxlength="3" :class="inputClass" style="text-transform: uppercase;" />
                <p v-if="errors.currency" class="text-xs mt-1" style="color:#dc2626;">{{ errors.currency }}</p>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.net') }}</label>
                <input v-model="draft.net_total" type="number" step="0.01" inputmode="decimal" :class="inputClass" />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.vat') }}</label>
                <input v-model="draft.vat_total" type="number" step="0.01" inputmode="decimal" :class="inputClass" />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.gross') }}</label>
                <input v-model="draft.gross_total" type="number" step="0.01" inputmode="decimal" :class="inputClass" />
                <p v-if="errors.gross_total" class="text-xs mt-1" style="color:#dc2626;">{{ errors.gross_total }}</p>
            </div>
        </div>
    </div>

    <!-- Lines -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest">{{ $t('receipts.lines_title') }}</h2>
            <p v-if="hasLineTotals" class="text-xs text-slate-400 tabular-nums">{{ $t('receipts.lines_sum') }}: {{ formatMoney(lineSum, draft.currency) }}</p>
        </div>

        <div v-for="(line, i) in draft.lines" :key="i" class="bg-white rounded-xl border border-slate-100 p-3 space-y-2" style="box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
            <div class="flex items-center gap-2">
                <input v-model="line.description" type="text" :placeholder="$t('receipts.description')" :class="smallInput" />
                <input v-model="line.line_total" type="number" step="0.01" inputmode="decimal" :placeholder="$t('receipts.amount')" :class="smallInput" style="width: 7rem; flex-shrink: 0;" />
                <button type="button" class="shrink-0 text-slate-300 hover:text-red-400" :aria-label="$t('receipts.remove')" @click="removeLine(i)">
                    <Trash2 style="width: 16px; height: 16px;" />
                </button>
            </div>
            <p v-if="errors[`lines.${i}.description`]" class="text-xs" style="color:#dc2626;">{{ errors[`lines.${i}.description`] }}</p>
            <select v-model="line.category" :class="smallInput">
                <option v-for="category in categories" :key="category" :value="category">{{ $t(`receipts.categories.${category}`) }}</option>
            </select>
        </div>

        <button type="button" class="w-full h-11 rounded-xl border-2 border-dashed text-sm font-semibold inline-flex items-center justify-center gap-2" style="border-color: #a7f3d0; color: #059669;" @click="addLine">
            <Plus style="width: 16px; height: 16px;" /> {{ $t('receipts.add_line') }}
        </button>
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.notes') }}</label>
        <textarea v-model="draft.notes" rows="2" class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder:text-slate-400 [color-scheme:light] px-3 py-2 text-sm" />
    </div>

    </div>
</template>
