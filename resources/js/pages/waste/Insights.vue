<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import { formatMoney } from '@/lib/money';
import { TrendingUp, TrendingDown, AlertTriangle, CheckCircle2, Info, Lightbulb } from 'lucide-vue-next';

const props = defineProps<{
    thisMonthKg: number;
    lastMonthKg: number;
    thisWeekKg: number;
    lastWeekKg: number;
    byCategory: Record<string, number>;
    byReason: Record<string, number>;
    weeklyTrend: { label: string; kg: number }[];
    hasData: boolean;
    purchases: {
        spendThisMonth: number;
        receiptsThisMonth: number;
        spendLastMonth: number;
        bySupplier: { supplier: string | null; total: number; count: number }[];
        byCategory: Record<string, number>;
        foodSpend: number;
        wasteCost: number;
        binnedPercent: number | null;
    };
    hasCostData: boolean;
    currency: string;
    thisMonthCost: number;
    lastMonthCost: number;
    thisMonthCo2e: number;
    costByCategory: Record<string, number>;
}>();

const { t } = useI18n();

// ── Helpers ───────────────────────────────────────────────────

function pctChange(current: number, previous: number): number | null {
    if (previous === 0) return null;
    return Math.round(((current - previous) / previous) * 100);
}

function topKey(obj: Record<string, number>): string | null {
    const entries = Object.entries(obj);
    if (!entries.length) return null;
    return entries.reduce((a, b) => (b[1] > a[1] ? b : a))[0];
}

const REASON_LABELS: Record<string, string> = {
    spoilage: 'Spoilage', overproduction: 'Overproduction',
    expiry: 'Expiry', prep_waste: 'Prep Waste', other: 'Other',
};

const CATEGORY_LABELS: Record<string, string> = {
    protein: 'Protein', veg: 'Vegetables', dairy: 'Dairy', prepared: 'Prepared',
};

const CATEGORY_COLORS: Record<string, string> = {
    protein:  '#dc2626',
    veg:      '#16a34a',
    dairy:    '#ca8a04',
    prepared: '#ea580c',
};

// ── Computed insights ─────────────────────────────────────────

type InsightType = 'good' | 'warn' | 'bad' | 'info';
type Insight = { type: InsightType; title: string; detail: string };

const spendChange = computed(() => pctChange(props.purchases.spendThisMonth, props.purchases.spendLastMonth));
const maxSpendCategory = computed(() => Math.max(1, ...Object.values(props.purchases.byCategory).map((value) => Math.abs(value))));
const monthChange = computed(() => pctChange(props.thisMonthKg, props.lastMonthKg));
const weekChange  = computed(() => pctChange(props.thisWeekKg, props.lastWeekKg));
const costChange  = computed(() => pctChange(props.thisMonthCost, props.lastMonthCost));
const topCostCategory = computed(() => topKey(props.costByCategory));
const topReason   = computed(() => topKey(props.byReason));
const topCategory = computed(() => topKey(props.byCategory));

const topReasonPct = computed(() => {
    if (!topReason.value || !props.thisMonthKg) return 0;
    return Math.round((props.byReason[topReason.value] / props.thisMonthKg) * 100);
});

const topCategoryPct = computed(() => {
    if (!topCategory.value || !props.thisMonthKg) return 0;
    return Math.round((props.byCategory[topCategory.value] / props.thisMonthKg) * 100);
});

const insights = computed((): Insight[] => {
    if (!props.hasData) return [];
    const list: Insight[] = [];

    // 1. Top waste reason
    if (topReason.value) {
        const label = REASON_LABELS[topReason.value] ?? topReason.value;
        const kg    = props.byReason[topReason.value].toFixed(1);
        const preventable = ['spoilage', 'expiry'].includes(topReason.value);
        list.push({
            type:   preventable ? 'warn' : 'info',
            title:  `${label} is your #1 waste reason`,
            detail: `${kg} kg this month · ${topReasonPct.value}% of total waste`,
        });
    }

    // 2. Week-over-week trend
    if (weekChange.value !== null) {
        const abs = Math.abs(weekChange.value);
        if (weekChange.value < 0) {
            list.push({
                type:   'good',
                title:  `Waste down ${abs}% this week`,
                detail: `${props.thisWeekKg} kg vs ${props.lastWeekKg} kg last week — keep it up!`,
            });
        } else if (weekChange.value > 0) {
            list.push({
                type:   weekChange.value > 15 ? 'bad' : 'info',
                title:  `Waste up ${abs}% this week`,
                detail: `${props.thisWeekKg} kg vs ${props.lastWeekKg} kg last week`,
            });
        }
    }

    // 3. Top category
    if (topCategory.value) {
        const label = CATEGORY_LABELS[topCategory.value] ?? topCategory.value;
        const kg    = props.byCategory[topCategory.value].toFixed(1);
        list.push({
            type:   'info',
            title:  `${label} accounts for ${topCategoryPct.value}% of waste`,
            detail: `${kg} kg this month`,
        });
    }

    // 4. Month-over-month trend
    if (monthChange.value !== null) {
        const abs = Math.abs(monthChange.value);
        if (monthChange.value < 0) {
            list.push({
                type:   'good',
                title:  `Overall waste down ${abs}% vs last month`,
                detail: `${props.thisMonthKg} kg this month vs ${props.lastMonthKg} kg last month`,
            });
        } else if (monthChange.value > 0) {
            list.push({
                type:   monthChange.value > 20 ? 'bad' : 'info',
                title:  `Overall waste up ${abs}% vs last month`,
                detail: `${props.thisMonthKg} kg this month vs ${props.lastMonthKg} kg last month`,
            });
        }
    }

    return list;
});

// ── Tips ──────────────────────────────────────────────────────

const TIPS: Record<string, [string, string]> = {
    spoilage:       [t('insights.tips.spoilage_1'),       t('insights.tips.spoilage_2')],
    overproduction: [t('insights.tips.overproduction_1'), t('insights.tips.overproduction_2')],
    expiry:         [t('insights.tips.expiry_1'),         t('insights.tips.expiry_2')],
    prep_waste:     [t('insights.tips.prep_waste_1'),     t('insights.tips.prep_waste_2')],
    other:          [t('insights.tips.other_1'),          t('insights.tips.other_2')],
};

const activeTips = computed(() => {
    const key = topReason.value ?? 'other';
    return TIPS[key] ?? TIPS['other'];
});

const tipsReasonLabel = computed(() => REASON_LABELS[topReason.value ?? 'other'] ?? 'Waste');

// ── 8-week bar chart ──────────────────────────────────────────

const maxWeekKg = computed(() => Math.max(...props.weeklyTrend.map(w => w.kg), 0.1));

function barHeight(kg: number): string {
    return `${Math.round((kg / maxWeekKg.value) * 100)}%`;
}

// ── Insight card styles ───────────────────────────────────────

const TYPE_STYLES: Record<InsightType, { bg: string; border: string; color: string }> = {
    good: { bg: '#f0fdf4', border: '#bbf7d0', color: '#16a34a' },
    warn: { bg: '#fefce8', border: '#fde68a', color: '#d97706' },
    bad:  { bg: '#fef2f2', border: '#fecaca', color: '#dc2626' },
    info: { bg: '#f0f9ff', border: '#bae6fd', color: '#0369a1' },
};

function cardStyle(type: InsightType): string {
    const s = TYPE_STYLES[type];
    return `background:${s.bg};border:1px solid ${s.border};`;
}

function iconColor(type: InsightType): string {
    return TYPE_STYLES[type].color;
}

function insightIcon(insight: Insight) {
    if (insight.type === 'good') return CheckCircle2;
    if (insight.type === 'bad')  return TrendingUp;
    if (insight.type === 'warn') return AlertTriangle;
    return Info;
}
</script>

<template>
    <Head :title="$t('insights.title')" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-8 space-y-5">

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $t('insights.title') }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $t('insights.subtitle') }}</p>
        </div>

        <!-- Purchases -->
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest pt-1">{{ $t('purchases.insights_title') }}</h2>

        <div
            v-if="purchases.receiptsThisMonth === 0"
            class="bg-white rounded-2xl border border-slate-100 p-6 text-center text-sm text-slate-400"
            style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);"
        >
            {{ $t('purchases.no_receipts_month') }}
            <Link href="/waste/receipts" class="block mt-2 font-bold" style="color: #059669;">{{ $t('receipts.title') }} →</Link>
        </div>

        <template v-else>
            <div class="rounded-2xl p-5 flex items-center justify-between text-white" style="background: linear-gradient(135deg, #065f46, #059669, #0d9488); box-shadow: 0 4px 20px rgba(5,150,105,0.3);">
                <div>
                    <p class="text-sm font-medium" style="color: rgba(167,243,208,0.85);">{{ $t('purchases.spend_month') }}</p>
                    <p class="text-4xl font-bold tabular-nums mt-1">{{ formatMoney(purchases.spendThisMonth, currency) }}</p>
                    <p class="text-xs mt-1" style="color: rgba(167,243,208,0.75);">{{ $t('receipts.confirmed_count', { n: purchases.receiptsThisMonth }) }}</p>
                </div>
                <div v-if="spendChange !== null" class="text-right">
                    <p class="text-sm font-bold" :style="spendChange <= 0 ? 'color:#6ee7b7;' : 'color:#fca5a5;'">{{ spendChange <= 0 ? '' : '+' }}{{ spendChange }}%</p>
                    <p class="text-xs mt-0.5" style="color: rgba(167,243,208,0.6);">{{ $t('insights.vs_last_month') }}</p>
                </div>
            </div>

            <!-- Bought vs binned -->
            <div v-if="purchases.binnedPercent !== null" class="rounded-2xl p-4" style="background: #fff7ed; border: 1px solid #fed7aa;">
                <p class="text-sm font-bold" style="color: #9a3412;">{{ $t('purchases.bvb_title') }}</p>
                <p class="text-sm mt-1" style="color: #9a3412;">
                    {{ $t('purchases.bvb_text', { bought: formatMoney(purchases.foodSpend, currency), binned: formatMoney(purchases.wasteCost, currency), pct: purchases.binnedPercent }) }}
                </p>
            </div>

            <div v-if="purchases.bySupplier.length" class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <p class="px-4 py-3 text-sm font-bold text-slate-800 border-b border-slate-100">{{ $t('purchases.top_suppliers') }}</p>
                <ul>
                    <li v-for="(row, i) in purchases.bySupplier" :key="i" class="flex items-center justify-between gap-3 px-4 py-2.5" :class="i > 0 ? 'border-t border-slate-50' : ''">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ row.supplier || $t('receipts.unknown_supplier') }}</p>
                        <p class="shrink-0 text-sm font-bold tabular-nums text-slate-900">{{ formatMoney(row.total, currency) }}</p>
                    </li>
                </ul>
            </div>

            <div v-if="Object.keys(purchases.byCategory).length" class="bg-white rounded-2xl border border-slate-100 p-4 space-y-3" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <p class="text-sm font-bold text-slate-800">{{ $t('purchases.by_category') }}</p>
                <div v-for="(total, category) in purchases.byCategory" :key="category" class="flex items-center gap-3">
                    <span class="w-24 shrink-0 text-xs font-semibold text-slate-600 truncate">{{ $t(`receipts.categories.${category}`) }}</span>
                    <div class="h-2 flex-1 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full" style="background: #059669;" :style="`width: ${Math.max(0, Math.round((total / maxSpendCategory) * 100))}%;`" />
                    </div>
                    <span class="w-20 shrink-0 text-right text-xs font-bold tabular-nums text-slate-700">{{ formatMoney(total, currency) }}</span>
                </div>
            </div>
        </template>

        <!-- Waste -->
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest pt-3">{{ $t('purchases.waste_title') }}</h2>

        <!-- Empty state -->
        <div
            v-if="!hasData"
            class="bg-white rounded-2xl border border-slate-100 p-10 text-center text-sm text-slate-400"
            style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);"
        >
            <Lightbulb class="mx-auto mb-3 size-10 text-slate-200" />
            {{ $t('insights.no_data') }}
        </div>

        <template v-else>

            <!-- Monthly summary card -->
            <div
                class="rounded-2xl p-5 flex items-center justify-between text-white"
                style="background: linear-gradient(135deg, #065f46, #059669, #0d9488); box-shadow: 0 4px 20px rgba(5,150,105,0.3);"
            >
                <div>
                    <p class="text-sm font-medium" style="color: rgba(167,243,208,0.85);">{{ $t('insights.this_month') }}</p>
                    <p class="text-4xl font-bold tabular-nums mt-1">{{ thisMonthKg.toFixed(1) }}<span class="text-lg ml-1 font-medium" style="color:rgba(167,243,208,0.8);">kg</span></p>
                </div>
                <div class="text-right" v-if="monthChange !== null">
                    <component
                        :is="monthChange < 0 ? TrendingDown : TrendingUp"
                        class="ml-auto size-6 mb-1"
                        :style="monthChange < 0 ? 'color:#6ee7b7;' : 'color:#fca5a5;'"
                    />
                    <p class="text-sm font-bold" :style="monthChange < 0 ? 'color:#6ee7b7;' : 'color:#fca5a5;'">
                        {{ monthChange < 0 ? '' : '+' }}{{ monthChange }}%
                    </p>
                    <p class="text-xs mt-0.5" style="color:rgba(167,243,208,0.6);">{{ $t('insights.vs_last_month') }}</p>
                </div>
            </div>

            <!-- Money lost card -->
            <div class="bg-white rounded-2xl border border-slate-100 p-5" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <template v-if="hasCostData">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ $t('cost.lost_month') }}</p>
                            <p class="text-3xl font-bold tabular-nums mt-1" style="color:#b91c1c;">{{ formatMoney(thisMonthCost, currency) }}</p>
                        </div>
                        <div v-if="costChange !== null" class="text-right">
                            <p class="text-sm font-bold" :style="costChange <= 0 ? 'color:#059669;' : 'color:#dc2626;'">
                                {{ costChange <= 0 ? '' : '+' }}{{ costChange }}%
                            </p>
                            <p class="text-xs text-slate-400">{{ $t('cost.vs') }}</p>
                        </div>
                    </div>
                    <p v-if="topCostCategory" class="text-xs text-slate-500 mt-3">
                        {{ $t('cost.top_cost') }}:
                        <span class="font-semibold text-slate-700">{{ CATEGORY_LABELS[topCostCategory] ?? topCostCategory }}</span>
                        · {{ formatMoney(costByCategory[topCostCategory], currency) }}
                    </p>
                </template>
                <Link v-else href="/waste/settings#cost-per-kg" class="flex items-center justify-between text-sm font-semibold" style="color:#059669;">
                    {{ $t('cost.set_prices') }}
                    <span class="text-xs font-bold px-3 py-1.5 rounded-full" style="background:#ecfdf5;">{{ $t('cost.set_prices_link') }}</span>
                </Link>
                <p class="text-xs text-slate-400 mt-3 pt-3 border-t border-slate-100">
                    {{ $t('cost.co2e') }}: <span class="font-semibold text-slate-600 tabular-nums">{{ thisMonthCo2e.toFixed(1) }} kg</span>
                </p>
            </div>

            <!-- Insight cards -->
            <div class="space-y-3">
                <div
                    v-for="(insight, i) in insights"
                    :key="i"
                    class="rounded-2xl p-4 flex items-start gap-3"
                    :style="cardStyle(insight.type)"
                >
                    <div
                        class="size-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                        :style="`background:${TYPE_STYLES[insight.type].bg};`"
                    >
                        <component
                            :is="insightIcon(insight)"
                            class="size-4"
                            :style="`color:${iconColor(insight.type)};`"
                        />
                    </div>
                    <div>
                        <p class="font-bold text-sm text-slate-800">{{ insight.title }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ insight.detail }}</p>
                    </div>
                </div>
            </div>

            <!-- Category breakdown mini bars -->
            <div class="bg-white rounded-2xl border border-slate-100 p-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">{{ $t('insights.top_category') }}</p>
                <div class="space-y-3">
                    <div v-for="(kg, cat) in byCategory" :key="cat" class="flex items-center gap-3">
                        <span
                            class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0 w-20 text-center"
                            :style="`background:${CATEGORY_COLORS[cat]}18;color:${CATEGORY_COLORS[cat]};`"
                        >{{ CATEGORY_LABELS[cat] ?? cat }}</span>
                        <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all"
                                :style="`width:${thisMonthKg > 0 ? Math.round((kg / thisMonthKg) * 100) : 0}%;background:${CATEGORY_COLORS[cat]};`"
                            />
                        </div>
                        <span class="text-xs font-semibold tabular-nums text-slate-600 w-14 text-right">{{ kg.toFixed(1) }} kg</span>
                    </div>
                </div>
            </div>

            <!-- Tips -->
            <div class="bg-white rounded-2xl border border-slate-100 p-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <div class="flex items-center gap-2 mb-4">
                    <div class="size-7 rounded-xl flex items-center justify-center" style="background:#fefce8;">
                        <Lightbulb class="size-4" style="color:#d97706;" />
                    </div>
                    <p class="text-sm font-bold text-slate-800">
                        {{ $t('insights.tips_title').replace('{reason}', tipsReasonLabel) }}
                    </p>
                </div>
                <ul class="space-y-2.5">
                    <li v-for="(tip, i) in activeTips" :key="i" class="flex items-start gap-2.5 text-sm text-slate-700">
                        <span class="size-5 rounded-full flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold" style="background:#ecfdf5;color:#059669;">{{ i + 1 }}</span>
                        {{ tip }}
                    </li>
                </ul>
            </div>

            <!-- 8-week trend chart -->
            <div class="bg-white rounded-2xl border border-slate-100 p-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">{{ $t('insights.week_chart') }}</p>
                <div class="flex items-end gap-1.5" style="height:80px;">
                    <div
                        v-for="week in weeklyTrend"
                        :key="week.label"
                        class="flex-1 flex flex-col items-center gap-1"
                        style="height:100%;"
                    >
                        <div class="flex-1 w-full flex items-end">
                            <div
                                class="w-full rounded-t-md transition-all"
                                :style="`height:${barHeight(week.kg)};background:${week.kg > 0 ? 'linear-gradient(to top,#059669,#34d399)' : '#f1f5f9'};min-height:3px;`"
                                :title="`${week.label}: ${week.kg} kg`"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 mt-2">
                    <span v-for="week in weeklyTrend" :key="week.label" class="flex-1 text-center" style="font-size:9px;color:#94a3b8;white-space:nowrap;overflow:hidden;">
                        {{ week.label }}
                    </span>
                </div>
            </div>

        </template>
    </div>
</template>
