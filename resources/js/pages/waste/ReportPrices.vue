<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { TrendingDown, TrendingUp } from 'lucide-vue-next';
import ReportTabs from '@/components/waste/ReportTabs.vue';
import { formatMoney } from '@/lib/money';
import { link as linkRoute, unlink as unlinkRoute } from '@/routes/waste/report/prices';

type History = { date: string; price: number }[];

defineProps<{
    currency: string;
    changes: {
        product: string;
        supplier: string | null;
        unit: string;
        pack: string | null;
        previous: { price: number; date: string };
        latest: { price: number; date: string };
        change_pct: number;
        impact: number;
        history: History;
    }[];
    comparisons: {
        product: string;
        basis: string;
        current: { supplier: string | null; price: number; pack: string | null; date: string };
        cheaper: { supplier: string | null; price: number; pack: string | null; date: string };
        saving_per_unit: number;
        saving_pct: number;
        monthly_saving: number;
    }[];
    potentialSaving: number;
    suggestions: {
        from_key: string;
        to_key: string;
        from_name: string;
        to_name: string;
        from_suppliers: string[];
        to_suppliers: string[];
        score: number;
    }[];
    links: { id: number; name: string; linked_to: string }[];
    trackedLines: number;
    threshold: number;
}>();

const basisKey = (unit: string) => (unit === 'pc' || unit === 'item' ? 'per_item' : unit === 'kg' ? 'per_kg' : 'per_l');

function linkProducts(fromKey: string, toKey: string) {
    router.post(linkRoute().url, { from_key: fromKey, to_key: toKey }, { preserveScroll: true });
}

function unlink(id: number) {
    router.delete(unlinkRoute(id).url, { preserveScroll: true });
}

const card = 'bg-white rounded-2xl border border-slate-100';
</script>

<template>
    <Head :title="$t('purchases.tab_prices')" />

    <div class="max-w-2xl mx-auto px-4 pt-5 pb-8 space-y-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 mb-3">{{ $t('purchases.reports_title') }}</h1>
            <ReportTabs active="prices" />
        </div>

        <!-- Nothing tracked yet -->
        <div v-if="trackedLines === 0" :class="card + ' p-8 text-center text-sm text-slate-500'" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <p class="font-semibold text-slate-700">{{ $t('prices.empty_title') }}</p>
            <p class="mt-2">{{ $t('prices.empty_desc') }}</p>
        </div>

        <template v-else>
            <!-- Summary -->
            <div class="rounded-2xl p-5 text-white" style="background: linear-gradient(135deg, #065f46, #059669, #0d9488); box-shadow: 0 4px 20px rgba(5,150,105,0.3);">
                <p class="text-sm font-medium" style="color: rgba(167,243,208,0.85);">{{ $t('prices.saving_title') }}</p>
                <p v-if="potentialSaving > 0" class="text-4xl font-bold tabular-nums mt-1">{{ formatMoney(potentialSaving, currency) }}</p>
                <p v-else class="text-lg font-semibold mt-1">{{ $t('prices.saving_none') }}</p>
                <p class="text-xs mt-1" style="color: rgba(167,243,208,0.75);">
                    {{ $t('prices.changes_count', { n: changes.length, pct: threshold }) }} · {{ $t('prices.tracked', { n: trackedLines }) }}
                </p>
            </div>

            <!-- Price changes -->
            <div class="space-y-2">
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest">{{ $t('prices.changes_title') }}</h2>
                <div v-if="changes.length === 0" :class="card + ' p-5 text-sm text-slate-500'">{{ $t('prices.changes_empty', { pct: threshold }) }}</div>
                <div v-for="(row, i) in changes" :key="i" :class="card + ' p-4'" style="box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900">{{ row.product }}</p>
                            <p class="text-xs text-slate-400 truncate">
                                {{ row.supplier || $t('receipts.unknown_supplier') }} · {{ $t(`prices.${basisKey(row.unit)}`) }}<template v-if="row.pack"> · {{ row.pack }}</template>
                            </p>
                        </div>
                        <span
                            class="shrink-0 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold"
                            :style="row.change_pct > 0 ? 'background:#fef2f2;color:#dc2626;' : 'background:#ecfdf5;color:#059669;'"
                        >
                            <component :is="row.change_pct > 0 ? TrendingUp : TrendingDown" style="width: 13px; height: 13px;" />
                            {{ row.change_pct > 0 ? '+' : '' }}{{ row.change_pct }}%
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600 tabular-nums">
                        {{ formatMoney(row.previous.price, currency) }} <span class="text-xs text-slate-400">({{ row.previous.date }})</span>
                        → <strong class="text-slate-900">{{ formatMoney(row.latest.price, currency) }}</strong> <span class="text-xs text-slate-400">({{ row.latest.date }})</span>
                    </p>
                    <p v-if="row.impact !== 0" class="text-xs text-slate-500 mt-1">{{ $t('prices.impact', { amount: formatMoney(Math.abs(row.impact), currency), dir: row.impact > 0 ? $t('prices.more') : $t('prices.less') }) }}</p>
                    <details v-if="row.history.length > 2" class="mt-2 text-xs text-slate-500">
                        <summary class="cursor-pointer font-semibold">{{ $t('prices.history') }}</summary>
                        <ul class="mt-1 space-y-0.5 tabular-nums">
                            <li v-for="point in row.history" :key="point.date">{{ point.date }} · {{ formatMoney(point.price, currency) }}</li>
                        </ul>
                    </details>
                </div>
            </div>

            <!-- Cheaper elsewhere -->
            <div class="space-y-2">
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest">{{ $t('prices.compare_title') }}</h2>
                <div v-if="comparisons.length === 0" :class="card + ' p-5 text-sm text-slate-500'">{{ $t('prices.compare_empty') }}</div>
                <div v-for="(row, i) in comparisons" :key="i" :class="card + ' p-4'" style="box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 truncate text-sm font-bold text-slate-900">{{ row.product }}</p>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold" style="background:#ecfdf5;color:#059669;">
                            {{ $t('prices.saving_month', { amount: formatMoney(row.monthly_saving, currency) }) }}
                        </span>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-[11px] font-semibold text-slate-400 uppercase">{{ $t('prices.now_at') }}</p>
                            <p class="truncate font-semibold text-slate-800">{{ row.current.supplier || $t('receipts.unknown_supplier') }}</p>
                            <p class="tabular-nums text-slate-600">{{ formatMoney(row.current.price, currency) }} <span class="text-xs text-slate-400">{{ $t(`prices.${basisKey(row.basis)}`) }}</span></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-slate-400 uppercase">{{ $t('prices.cheaper_at') }}</p>
                            <p class="truncate font-semibold" style="color:#059669;">{{ row.cheaper.supplier || $t('receipts.unknown_supplier') }}</p>
                            <p class="tabular-nums text-slate-600">{{ formatMoney(row.cheaper.price, currency) }} <span class="text-xs text-slate-400">{{ $t(`prices.${basisKey(row.basis)}`) }}</span></p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">
                        {{ $t('prices.saving_unit', { amount: formatMoney(row.saving_per_unit, currency), basis: $t(`prices.${basisKey(row.basis)}`), pct: row.saving_pct }) }}
                    </p>
                </div>
            </div>

            <!-- Suggested matches -->
            <div v-if="suggestions.length" class="space-y-2">
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest">{{ $t('prices.suggest_title') }}</h2>
                <p class="text-xs text-slate-500">{{ $t('prices.suggest_hint') }}</p>
                <div v-for="pair in suggestions" :key="pair.from_key + pair.to_key" :class="card + ' p-4 flex items-center justify-between gap-3'" style="box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                    <div class="min-w-0 text-sm">
                        <p class="truncate font-semibold text-slate-900">{{ pair.from_name }} <span class="text-xs font-normal text-slate-400">· {{ pair.from_suppliers.join(', ') }}</span></p>
                        <p class="truncate font-semibold text-slate-900">{{ pair.to_name }} <span class="text-xs font-normal text-slate-400">· {{ pair.to_suppliers.join(', ') }}</span></p>
                    </div>
                    <button type="button" class="shrink-0 rounded-xl px-3 h-10 text-xs font-bold text-white" style="background:#059669;" @click="linkProducts(pair.from_key, pair.to_key)">
                        {{ $t('prices.link_button') }}
                    </button>
                </div>
            </div>

            <!-- Existing links -->
            <div v-if="links.length" class="space-y-2">
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest">{{ $t('prices.links_title') }}</h2>
                <div v-for="item in links" :key="item.id" :class="card + ' px-4 py-3 flex items-center justify-between gap-3'">
                    <p class="min-w-0 truncate text-sm text-slate-700">{{ item.name }} <span class="text-slate-400">=</span> {{ item.linked_to }}</p>
                    <button type="button" class="shrink-0 text-xs font-bold" style="color:#dc2626;" @click="unlink(item.id)">{{ $t('prices.unlink') }}</button>
                </div>
            </div>

            <p class="text-xs text-slate-400">{{ $t('prices.disclaimer') }}</p>
        </template>
    </div>
</template>
