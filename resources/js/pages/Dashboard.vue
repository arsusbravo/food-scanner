<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Users, Trash2, ScanLine, TrendingUp, Activity, Scale, Zap } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import { type WasteEntry } from '@/types/waste';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

type Stats = {
    total_users: number;
    total_entries: number;
    ai_scan_count: number;
    this_week_entries: number;
    this_week_kg: number;
    active_users: number;
};

type RecentEntry = WasteEntry & { user: { id: number; name: string; email: string } };

type OpenRouterData = {
    label: string;
    usage: number;
    limit: number | null;
    is_free_tier: boolean;
    rate_limit: { requests: number; interval: string };
};

const props = defineProps<{
    stats: Stats;
    recentEntries: RecentEntry[];
    openrouter: OpenRouterData | null;
}>();

const CATEGORY_COLORS: Record<string, string> = {
    protein:  'background:#fef2f2; color:#dc2626;',
    veg:      'background:#f0fdf4; color:#16a34a;',
    dairy:    'background:#fefce8; color:#ca8a04;',
    prepared: 'background:#fff7ed; color:#ea580c;',
};

const CATEGORY_LABELS: Record<string, string> = {
    protein: 'Protein', veg: 'Veg', dairy: 'Dairy', prepared: 'Prepared',
};

const REASON_LABELS: Record<string, string> = {
    spoilage: 'Spoilage', overproduction: 'Overproduction', expiry: 'Expiry',
    prep_waste: 'Prep Waste', other: 'Other',
};

function fmtKg(kg: number | string): string {
    const n = typeof kg === 'string' ? parseFloat(kg) : kg;
    return n >= 1 ? `${n.toFixed(2)} kg` : `${(n * 1000).toFixed(0)} g`;
}

function fmtDate(d: string): string {
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

function initials(name: string): string {
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

function fmtCredits(n: number): string {
    return '$' + n.toFixed(4);
}

function usagePct(used: number, limit: number | null): number {
    if (!limit) return 0;
    return Math.min(100, (used / limit) * 100);
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Stat cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-blue-50 flex items-center justify-center">
                        <Users class="size-4 text-blue-600" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">Total Users</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.total_users }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ stats.active_users }} active this week</p>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <Trash2 class="size-4 text-emerald-600" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">Total Entries</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.total_entries }}</p>
                <p class="text-xs text-muted-foreground mt-1">all time</p>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-teal-50 flex items-center justify-center">
                        <ScanLine class="size-4 text-teal-600" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">Scans</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.ai_scan_count }}</p>
                <p class="text-xs text-muted-foreground mt-1">photo scans total</p>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-violet-50 flex items-center justify-center">
                        <TrendingUp class="size-4 text-violet-600" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">This Week</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.this_week_entries }}</p>
                <p class="text-xs text-muted-foreground mt-1">entries logged</p>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-orange-50 flex items-center justify-center">
                        <Scale class="size-4 text-orange-500" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">This Week</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.this_week_kg }} kg</p>
                <p class="text-xs text-muted-foreground mt-1">food waste logged</p>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <Activity class="size-4 text-emerald-600" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">Active Users</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.active_users }}</p>
                <p class="text-xs text-muted-foreground mt-1">logged in last 7 days</p>
            </div>
        </div>

        <!-- OpenRouter credits -->
        <div v-if="openrouter" class="rounded-xl border bg-card p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="size-9 rounded-lg bg-amber-50 flex items-center justify-center">
                    <Zap class="size-4 text-amber-500" />
                </div>
                <div>
                    <p class="text-sm font-semibold">OpenRouter Credits</p>
                    <p class="text-xs text-muted-foreground">{{ openrouter.label }}</p>
                </div>
                <span v-if="openrouter.is_free_tier" class="ml-auto text-xs font-bold px-2 py-0.5 rounded-full" style="background:#fef3c7; color:#d97706;">Free tier</span>
            </div>
            <div class="flex items-end gap-6">
                <div>
                    <p class="text-xs text-muted-foreground mb-0.5">Used</p>
                    <p class="text-2xl font-bold tabular-nums">{{ fmtCredits(openrouter.usage) }}</p>
                </div>
                <div v-if="openrouter.limit">
                    <p class="text-xs text-muted-foreground mb-0.5">Limit</p>
                    <p class="text-2xl font-bold tabular-nums text-muted-foreground">{{ fmtCredits(openrouter.limit) }}</p>
                </div>
                <div v-if="openrouter.rate_limit" class="ml-auto text-right">
                    <p class="text-xs text-muted-foreground mb-0.5">Rate limit</p>
                    <p class="text-sm font-semibold">{{ openrouter.rate_limit.requests }} / {{ openrouter.rate_limit.interval }}</p>
                </div>
            </div>
            <div v-if="openrouter.limit" class="mt-3">
                <div class="h-1.5 rounded-full bg-muted overflow-hidden">
                    <div
                        class="h-full rounded-full bg-amber-400 transition-all"
                        :style="{ width: usagePct(openrouter.usage, openrouter.limit) + '%' }"
                    />
                </div>
                <p class="text-xs text-muted-foreground mt-1">{{ usagePct(openrouter.usage, openrouter.limit).toFixed(1) }}% of limit used</p>
            </div>
        </div>

        <!-- Recent activity -->
        <div class="rounded-xl border bg-card">
            <div class="flex items-center justify-between px-5 py-4 border-b">
                <h2 class="font-semibold text-sm">Recent Activity</h2>
                <span class="text-xs text-muted-foreground">Last 10 entries across all users</span>
            </div>

            <div v-if="recentEntries.length === 0" class="p-10 text-center text-sm text-muted-foreground">
                No entries yet.
            </div>

            <div v-else class="divide-y">
                <div
                    v-for="entry in recentEntries"
                    :key="entry.id"
                    class="flex items-center gap-4 px-5 py-3"
                >
                    <!-- User avatar -->
                    <div class="size-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600 shrink-0">
                        {{ initials(entry.user.name) }}
                    </div>

                    <!-- Entry info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate">{{ entry.item_name }}</p>
                        <p class="text-xs text-muted-foreground">{{ entry.user.name }} · {{ fmtDate(entry.logged_at) }}</p>
                    </div>

                    <!-- Category badge -->
                    <span class="hidden sm:inline-flex text-xs font-bold px-2 py-0.5 rounded-full shrink-0" :style="CATEGORY_COLORS[entry.category]">
                        {{ CATEGORY_LABELS[entry.category] }}
                    </span>

                    <!-- Weight -->
                    <span class="text-sm font-semibold tabular-nums text-muted-foreground shrink-0 w-16 text-right">
                        {{ fmtKg(entry.weight_kg) }}
                    </span>

                    <!-- scan badge -->
                    <span v-if="entry.source === 'ai_scan'" class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0" style="background:#ecfdf5; color:#059669;">
                        Scan
                    </span>
                </div>
            </div>
        </div>

    </div>
</template>
