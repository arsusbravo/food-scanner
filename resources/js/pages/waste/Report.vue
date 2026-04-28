<script setup lang="ts">
import { router, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Download, FileText } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as reportIndex } from '@/routes/waste/report';
import { CATEGORY_LABELS, REASON_LABELS, type WasteCategory, type WasteReason, type WasteReportRow } from '@/types/waste';

type Props = {
    summary: Record<WasteCategory, WasteReportRow>;
    dateFrom: string;
    dateTo: string;
    grandTotal: number;
    totalEntries: number;
};

const props = defineProps<Props>();

const dateFrom = ref(props.dateFrom);
const dateTo = ref(props.dateTo);

const categories: WasteCategory[] = ['protein', 'veg', 'dairy', 'prepared'];
const reasons: WasteReason[] = ['spoilage', 'overproduction', 'expiry', 'prep_waste', 'other'];

function generate() {
    router.get(reportIndex().url, { date_from: dateFrom.value, date_to: dateTo.value });
}

function exportCsv() {
    window.location.href = `/waste/report/csv?date_from=${dateFrom.value}&date_to=${dateTo.value}`;
}

function exportPdf() {
    window.location.href = `/waste/report/pdf?date_from=${dateFrom.value}&date_to=${dateTo.value}`;
}

const CATEGORY_BADGE: Record<WasteCategory, string> = {
    protein:  'background: #fef2f2; color: #dc2626;',
    veg:      'background: #f0fdf4; color: #16a34a;',
    dairy:    'background: #fefce8; color: #ca8a04;',
    prepared: 'background: #fff7ed; color: #ea580c;',
};
</script>

<template>
    <Head title="EU Compliance Report" />

    <div class="max-w-2xl mx-auto px-4 pt-5 pb-4 space-y-5">

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900">EU Compliance Report</h1>
            <p class="text-xs text-slate-400 mt-1">EU Directive 2018/851 · FLW Protocol · Food Service (HORECA)</p>
        </div>

        <!-- Date range filter -->
        <div class="bg-white rounded-2xl border border-slate-100 p-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-32 space-y-1.5">
                    <Label for="date_from" class="text-slate-700 font-semibold text-sm">From</Label>
                    <Input id="date_from" v-model="dateFrom" type="date" class="border-slate-200 bg-slate-50 text-slate-900" />
                </div>
                <div class="flex-1 min-w-32 space-y-1.5">
                    <Label for="date_to" class="text-slate-700 font-semibold text-sm">To</Label>
                    <Input id="date_to" v-model="dateTo" type="date" class="border-slate-200 bg-slate-50 text-slate-900" />
                </div>
                <button
                    class="h-10 px-5 rounded-xl font-semibold text-sm text-white transition-opacity"
                    style="background: linear-gradient(135deg, #059669, #047857);"
                    @click="generate"
                >
                    Generate
                </button>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-if="grandTotal === 0"
            class="bg-white rounded-2xl border border-slate-100 p-10 text-center text-sm text-slate-400"
        >
            No waste entries found for the selected period.
        </div>

        <template v-else>

            <!-- Grand total banner -->
            <div
                class="rounded-2xl p-5 flex items-center justify-between text-white"
                style="background: linear-gradient(135deg, #065f46, #059669, #0d9488); box-shadow: 0 4px 20px rgba(5,150,105,0.3);"
            >
                <div>
                    <p class="text-sm font-medium" style="color: rgba(167,243,208,0.85);">Total Food Waste</p>
                    <p class="text-4xl font-bold tabular-nums mt-1">{{ grandTotal.toFixed(2) }} kg</p>
                </div>
                <div class="text-right">
                    <p class="text-sm" style="color: rgba(167,243,208,0.85);">{{ totalEntries }} entries</p>
                    <p class="text-xs mt-1" style="color: rgba(167,243,208,0.6);">{{ dateFrom }} – {{ dateTo }}</p>
                </div>
            </div>

            <!-- Category summary cards -->
            <div class="grid grid-cols-2 gap-3">
                <div
                    v-for="cat in categories"
                    :key="cat"
                    class="bg-white rounded-2xl border border-slate-100 p-4"
                    style="box-shadow: 0 2px 8px rgba(0,0,0,0.04);"
                >
                    <span
                        class="inline-block rounded-full px-2.5 py-0.5 text-xs font-bold mb-3"
                        :style="CATEGORY_BADGE[cat]"
                    >
                        {{ CATEGORY_LABELS[cat] }}
                    </span>
                    <p class="text-2xl font-bold text-slate-900 tabular-nums">
                        {{ summary[cat]?.total_kg?.toFixed(2) ?? '0.00' }}
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">kg · {{ summary[cat]?.entry_count ?? 0 }} entries</p>
                    <p class="mt-1 text-xs font-mono" style="color: #059669;">{{ summary[cat]?.eu_code }}</p>
                </div>
            </div>

            <!-- Cross-tab table -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <div class="px-4 py-3 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800 text-sm">Waste by Reason × Category (kg)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Reason</th>
                                <th v-for="cat in categories" :key="cat" class="px-3 py-3 text-right font-semibold text-slate-600">
                                    {{ CATEGORY_LABELS[cat] }}
                                </th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="reason in reasons"
                                :key="reason"
                                class="border-t border-slate-50"
                            >
                                <td class="px-4 py-3 font-medium text-slate-700">{{ REASON_LABELS[reason] }}</td>
                                <td v-for="cat in categories" :key="cat" class="px-3 py-3 text-right tabular-nums text-slate-400">
                                    {{ summary[cat]?.by_reason[reason]?.total_kg?.toFixed(2) ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-700">
                                    {{
                                        categories
                                            .reduce((sum, cat) => sum + (summary[cat]?.by_reason[reason]?.total_kg ?? 0), 0)
                                            .toFixed(2)
                                    }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f0fdf4; border-top: 2px solid #d1fae5;">
                                <td class="px-4 py-3 font-bold text-slate-700">Total (kg)</td>
                                <td v-for="cat in categories" :key="cat" class="px-3 py-3 text-right tabular-nums font-bold" style="color: #059669;">
                                    {{ summary[cat]?.total_kg?.toFixed(2) ?? '0.00' }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold" style="color: #059669;">
                                    {{ grandTotal.toFixed(2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- FLW mapping -->
            <div class="bg-white rounded-2xl border border-slate-100 p-4" style="box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">FLW Protocol Mapping</h3>
                <div class="space-y-2.5">
                    <div v-for="cat in categories" :key="cat" class="flex items-start gap-3 text-sm">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold shrink-0 mt-0.5" :style="CATEGORY_BADGE[cat]">
                            {{ CATEGORY_LABELS[cat] }}
                        </span>
                        <div>
                            <span class="font-semibold text-slate-700">{{ summary[cat]?.flw_group }}</span>
                            <span class="ml-2 text-xs text-slate-400 font-mono">{{ summary[cat]?.eu_code }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export buttons -->
            <div class="flex gap-3">
                <button
                    class="flex-1 h-11 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 border-2 transition-colors"
                    style="border-color: #6ee7b7; color: #059669; background: #f0fdf4;"
                    @click="exportCsv"
                >
                    <Download style="width: 16px; height: 16px;" />
                    Export CSV
                </button>
                <button
                    class="flex-1 h-11 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 border-2 transition-colors"
                    style="border-color: #6ee7b7; color: #059669; background: #f0fdf4;"
                    @click="exportPdf"
                >
                    <FileText style="width: 16px; height: 16px;" />
                    Export PDF
                </button>
            </div>

        </template>
    </div>
</template>
