<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Download, Receipt as ReceiptIcon } from 'lucide-vue-next';
import PeriodChips from '@/components/waste/PeriodChips.vue';
import ReportTabs from '@/components/waste/ReportTabs.vue';
import { formatMoney } from '@/lib/money';
import { purchases as purchasesRoute } from '@/routes/waste/report';
import { exportMethod, index as receiptsIndex } from '@/routes/waste/receipts';

const props = defineProps<{
    period: 'this_month' | 'last_month' | 'this_year' | 'all' | 'custom';
    dateFrom: string;
    dateTo: string;
    totals: { spend: number; vat: number; count: number };
    bySupplier: { supplier: string | null; total: number; count: number }[];
    byCategory: Record<string, number>;
    hasAnyReceipts: boolean;
    currency: string;
    exportQuota: { used: number; limit: number | null };
}>();

const dateFrom = ref(props.dateFrom);
const dateTo = ref(props.dateTo);

watch(() => [props.dateFrom, props.dateTo], ([from, to]) => {
    dateFrom.value = from;
    dateTo.value = to;
});
const exportMode = ref<'receipts' | 'lines'>('receipts');

const exportBlocked = props.exportQuota.limit !== null && props.exportQuota.used >= props.exportQuota.limit;

function choose(period: string) {
    router.get(purchasesRoute().url, { period }, { preserveScroll: true });
}

function applyCustom() {
    router.get(purchasesRoute().url, { period: 'custom', date_from: dateFrom.value || undefined, date_to: dateTo.value || undefined }, { preserveScroll: true });
}

function download(format: 'xlsx' | 'csv') {
    const query = new URLSearchParams({ mode: exportMode.value, format });

    if (props.dateFrom) {
        query.set('date_from', props.dateFrom);
    }

    if (props.dateTo) {
        query.set('date_to', props.dateTo);
    }

    window.location.href = `${exportMethod().url}?${query.toString()}`;
}

const maxCategory = () => Math.max(1, ...Object.values(props.byCategory).map((value) => Math.abs(value)));

const dateInput = 'mt-1 w-full h-11 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 [color-scheme:light] px-3 text-sm';
</script>

<template>
    <Head :title="$t('purchases.tab_purchases')" />

    <div class="max-w-2xl mx-auto px-4 pt-5 pb-8 space-y-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 mb-3">{{ $t('purchases.reports_title') }}</h1>
            <ReportTabs active="purchases" />
        </div>

        <!-- Period -->
        <div class="bg-white rounded-2xl border border-slate-100 p-4 space-y-3" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <PeriodChips :period="period" @choose="choose" />
            <form class="grid grid-cols-5 gap-2 items-end" @submit.prevent="applyCustom">
                <div class="col-span-2">
                    <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.from') }}</label>
                    <input v-model="dateFrom" type="date" :class="dateInput" />
                </div>
                <div class="col-span-2">
                    <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.to') }}</label>
                    <input v-model="dateTo" type="date" :class="dateInput" />
                </div>
                <button type="submit" class="h-11 rounded-xl text-sm font-semibold text-white" style="background: #059669;">{{ $t('receipts.apply') }}</button>
            </form>
        </div>

        <!-- Nothing at all yet -->
        <div v-if="!hasAnyReceipts" class="bg-white rounded-2xl border border-slate-100 p-10 text-center text-sm text-slate-400" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <ReceiptIcon class="mx-auto mb-3 size-10 text-slate-200" />
            <p class="font-semibold text-slate-600">{{ $t('receipts.empty_title') }}</p>
            <p class="mt-1">{{ $t('receipts.empty_desc') }}</p>
            <Link :href="receiptsIndex().url" class="mt-4 inline-block font-bold" style="color: #059669;">{{ $t('receipts.title') }} →</Link>
        </div>

        <!-- Nothing in this period -->
        <div v-else-if="totals.count === 0" class="bg-white rounded-2xl border border-slate-100 p-8 text-center text-sm text-slate-500" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <p>{{ $t('purchases.empty_period') }}</p>
            <button type="button" class="mt-3 font-bold" style="color: #059669;" @click="choose('all')">{{ $t('purchases.empty_try_all') }}</button>
        </div>

        <template v-else>
            <!-- Summary -->
            <div class="rounded-2xl p-5 text-white" style="background: linear-gradient(135deg, #065f46, #059669, #0d9488); box-shadow: 0 4px 20px rgba(5,150,105,0.3);">
                <p class="text-sm font-medium" style="color: rgba(167,243,208,0.85);">{{ $t('purchases.total_spend') }}</p>
                <p class="text-4xl font-bold tabular-nums mt-1">{{ formatMoney(totals.spend, currency) }}</p>
                <p class="text-xs mt-1" style="color: rgba(167,243,208,0.75);">
                    {{ $t('receipts.confirmed_count', { n: totals.count }) }}
                    <template v-if="totals.vat > 0"> · {{ $t('purchases.vat_paid') }} {{ formatMoney(totals.vat, currency) }}</template>
                </p>
            </div>

            <!-- By supplier -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <p class="px-4 py-3 text-sm font-bold text-slate-800 border-b border-slate-100">{{ $t('purchases.by_supplier') }}</p>
                <ul>
                    <li v-for="(row, i) in bySupplier" :key="i" class="flex items-center justify-between gap-3 px-4 py-3" :class="i > 0 ? 'border-t border-slate-50' : ''">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ row.supplier || $t('receipts.unknown_supplier') }}</p>
                            <p class="text-xs text-slate-400">{{ $t('purchases.receipts_count', { n: row.count }) }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-bold tabular-nums text-slate-900">{{ formatMoney(row.total, currency) }}</p>
                    </li>
                </ul>
            </div>

            <!-- By category -->
            <div v-if="Object.keys(byCategory).length" class="bg-white rounded-2xl border border-slate-100 p-4 space-y-3" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <p class="text-sm font-bold text-slate-800">{{ $t('purchases.by_category') }}</p>
                <div v-for="(total, category) in byCategory" :key="category" class="flex items-center gap-3">
                    <span class="w-24 shrink-0 text-xs font-semibold text-slate-600 truncate">{{ $t(`receipts.categories.${category}`) }}</span>
                    <div class="h-2 flex-1 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full" style="background: #059669;" :style="`width: ${Math.max(0, Math.round((total / maxCategory()) * 100))}%;`" />
                    </div>
                    <span class="w-20 shrink-0 text-right text-xs font-bold tabular-nums text-slate-700">{{ formatMoney(total, currency) }}</span>
                </div>
            </div>

            <!-- Export -->
            <div class="bg-white rounded-2xl border border-slate-100 p-4 space-y-3" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <p class="text-sm font-bold text-slate-800">{{ $t('receipts.export_title') }}</p>
                <select v-model="exportMode" class="w-full h-11 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 [color-scheme:light] px-3 text-sm">
                    <option value="receipts">{{ $t('receipts.export_receipts') }}</option>
                    <option value="lines">{{ $t('receipts.export_lines') }}</option>
                </select>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" :disabled="exportBlocked" class="h-11 rounded-xl text-sm font-semibold text-white inline-flex items-center justify-center gap-2 disabled:opacity-50" style="background: #059669;" @click="download('xlsx')">
                        <Download style="width: 15px; height: 15px;" /> Excel (.xlsx)
                    </button>
                    <button type="button" :disabled="exportBlocked" class="h-11 rounded-xl text-sm font-semibold inline-flex items-center justify-center gap-2 disabled:opacity-50" style="background: #ecfdf5; color: #047857;" @click="download('csv')">
                        <Download style="width: 15px; height: 15px;" /> CSV
                    </button>
                </div>
                <p v-if="exportBlocked" class="text-xs font-semibold" style="color: #dc2626;">{{ $t('receipts.export_blocked') }}</p>
                <p v-else class="text-xs text-slate-400">{{ $t('receipts.export_hint') }}</p>
            </div>
        </template>
    </div>
</template>
