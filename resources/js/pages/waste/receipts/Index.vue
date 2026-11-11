<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Camera, Download, Receipt as ReceiptIcon, Search } from 'lucide-vue-next';
import { formatMoney } from '@/lib/money';
import { create as createRoute, exportMethod, index as indexRoute, show as showRoute } from '@/routes/waste/receipts';

type ReceiptRow = {
    id: number;
    status: 'draft' | 'confirmed';
    supplier_name: string | null;
    invoice_number: string | null;
    invoice_date: string | null;
    currency: string;
    gross_total: string | null;
    lines_count: number;
};

const props = defineProps<{
    receipts: ReceiptRow[];
    drafts: ReceiptRow[];
    dateFrom: string;
    dateTo: string;
    search: string;
    totalSpend: number;
    confirmedCount: number;
    currency: string;
    quota: { used: number; limit: number | null };
    exportQuota: { used: number; limit: number | null };
}>();

const { t } = useI18n();

const dateFrom = ref(props.dateFrom);
const dateTo = ref(props.dateTo);
const search = ref(props.search);
const exportMode = ref<'receipts' | 'lines'>('receipts');

function applyFilters() {
    router.get(indexRoute().url, { date_from: dateFrom.value || undefined, date_to: dateTo.value || undefined, q: search.value || undefined }, { preserveState: true, preserveScroll: true });
}

function download(format: 'xlsx' | 'csv') {
    const query = new URLSearchParams({ mode: exportMode.value, format });

    if (dateFrom.value) {
        query.set('date_from', dateFrom.value);
    }

    if (dateTo.value) {
        query.set('date_to', dateTo.value);
    }

    window.location.href = `${exportMethod().url}?${query.toString()}`;
}

const exportBlocked = props.exportQuota.limit !== null && props.exportQuota.used >= props.exportQuota.limit;
</script>

<template>
    <Head :title="$t('receipts.title')" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-8 space-y-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $t('receipts.title') }}</h1>
                <p class="text-xs text-slate-400 mt-1">{{ $t('receipts.subtitle') }}</p>
            </div>
            <Link
                :href="createRoute().url"
                class="shrink-0 inline-flex items-center gap-1.5 rounded-xl px-4 h-11 text-sm font-semibold text-white"
                style="background: linear-gradient(135deg, #059669, #047857);"
            >
                <Camera style="width: 16px; height: 16px;" /> {{ $t('receipts.scan_new') }}
            </Link>
        </div>

        <!-- Filters -->
        <form class="bg-white rounded-2xl border border-slate-100 p-4 space-y-3" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);" @submit.prevent="applyFilters">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.from') }}</label>
                    <input v-model="dateFrom" type="date" class="mt-1 w-full h-11 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder:text-slate-400 [color-scheme:light] px-3 text-sm" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">{{ $t('receipts.to') }}</label>
                    <input v-model="dateTo" type="date" class="mt-1 w-full h-11 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder:text-slate-400 [color-scheme:light] px-3 text-sm" />
                </div>
            </div>
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" style="width: 16px; height: 16px;" />
                    <input v-model="search" type="search" :placeholder="$t('receipts.search')" class="w-full h-11 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder:text-slate-400 [color-scheme:light] pl-9 pr-3 text-sm" />
                </div>
                <button type="submit" class="h-11 rounded-xl px-4 text-sm font-semibold text-white" style="background: #059669;">{{ $t('receipts.apply') }}</button>
            </div>
        </form>

        <!-- Summary -->
        <div class="rounded-2xl p-5 text-white" style="background: linear-gradient(135deg, #065f46, #059669, #0d9488); box-shadow: 0 4px 20px rgba(5,150,105,0.3);">
            <p class="text-sm font-medium" style="color: rgba(167,243,208,0.85);">{{ $t('receipts.spend') }}</p>
            <p class="text-4xl font-bold tabular-nums mt-1">{{ formatMoney(totalSpend, currency) }}</p>
            <p class="text-xs mt-1" style="color: rgba(167,243,208,0.75);">{{ $t('receipts.confirmed_count', { n: confirmedCount }) }}</p>
        </div>

        <!-- Drafts waiting for review: listed here whatever their date -->
        <div v-if="drafts.length > 0" class="rounded-2xl p-4 space-y-3" style="background: #fefce8; border: 1px solid #fde68a;">
            <p class="text-sm font-semibold" style="color: #a16207;">{{ $t('receipts.drafts_notice', { n: drafts.length }) }}</p>
            <ul class="space-y-2">
                <li v-for="receipt in drafts" :key="receipt.id">
                    <Link
                        :href="showRoute(receipt.id).url"
                        class="bg-white rounded-xl border border-amber-100 px-4 py-3 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 truncate text-sm">{{ receipt.supplier_name || $t('receipts.unknown_supplier') }}</p>
                            <p class="text-xs text-slate-400 mt-0.5 truncate">
                                {{ receipt.invoice_date }} · {{ $t('receipts.lines_count', { n: receipt.lines_count }) }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="font-bold text-sm tabular-nums text-slate-900">{{ receipt.gross_total !== null ? formatMoney(Number(receipt.gross_total), receipt.currency) : '—' }}</p>
                            <span class="text-[10px] font-bold" style="color: #a16207;">{{ $t('receipts.review') }} →</span>
                        </div>
                    </Link>
                </li>
            </ul>
        </div>

        <!-- List -->
        <div v-if="receipts.length === 0" class="bg-white rounded-2xl border border-slate-100 p-10 text-center text-sm text-slate-400" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <ReceiptIcon class="mx-auto mb-3 size-10 text-slate-200" />
            <p class="font-semibold text-slate-600">{{ $t('receipts.empty_title') }}</p>
            <p class="mt-1">{{ $t('receipts.empty_desc') }}</p>
        </div>

        <ul v-else class="space-y-2">
            <li v-for="receipt in receipts" :key="receipt.id">
                <Link
                    :href="showRoute(receipt.id).url"
                    class="bg-white rounded-xl border border-slate-100 px-4 py-3 flex items-center justify-between gap-3"
                    style="box-shadow: 0 1px 4px rgba(0,0,0,0.04);"
                >
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 truncate text-sm">{{ receipt.supplier_name || $t('receipts.unknown_supplier') }}</p>
                        <p class="text-xs text-slate-400 mt-0.5 truncate">
                            {{ receipt.invoice_date }}<template v-if="receipt.invoice_number"> · {{ receipt.invoice_number }}</template> · {{ $t('receipts.lines_count', { n: receipt.lines_count }) }}
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="font-bold text-sm tabular-nums text-slate-900">{{ receipt.gross_total !== null ? formatMoney(Number(receipt.gross_total), receipt.currency) : '—' }}</p>
                        <span
                            class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                            :style="receipt.status === 'confirmed' ? 'background:#ecfdf5;color:#059669;' : 'background:#fefce8;color:#a16207;'"
                        >{{ $t(`receipts.status_${receipt.status}`) }}</span>
                    </div>
                </Link>
            </li>
        </ul>

        <!-- Export -->
        <div v-if="confirmedCount > 0" class="bg-white rounded-2xl border border-slate-100 p-4 space-y-3" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <p class="text-sm font-bold text-slate-800">{{ $t('receipts.export_title') }}</p>
            <select v-model="exportMode" class="w-full h-11 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder:text-slate-400 [color-scheme:light] px-3 text-sm">
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
            <p v-if="exportBlocked" class="text-xs font-semibold" style="color: #dc2626;">{{ t('receipts.export_blocked') }}</p>
            <p v-else class="text-xs text-slate-400">{{ $t('receipts.export_hint') }}</p>
        </div>

        <p v-if="quota.limit !== null" class="text-center text-xs text-slate-400">{{ $t('receipts.quota_used', { used: quota.used, limit: quota.limit }) }}</p>
    </div>
</template>
