<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Activity, Globe, Languages, Link2, ScanLine, FileBarChart2, TrendingUp, UserPlus } from 'lucide-vue-next';
import { computed } from 'vue';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Demo statistics', href: '/admin/demo' }],
    },
});

type Stats = {
    total_devices: number;
    devices_7d: number;
    total_scans: number;
    total_reports: number;
    signups_from_demo: number;
    conversion_pct: number;
};

type Funnel = { visits: number; scanned: number; reported: number; signups: number };

type DailyRow = { date: string; visit: number; scan: number; report: number };

type TopRow = { label: string; devices: number };

type RecentEvent = {
    id: number;
    when: string;
    type: 'visit' | 'scan' | 'report';
    device: string;
    ip: string | null;
    country: string | null;
    locale: string | null;
    referer: string | null;
};

const props = defineProps<{
    stats:         Stats;
    funnel:        Funnel;
    daily:         DailyRow[];
    top_countries: TopRow[];
    top_locales:   TopRow[];
    top_referers:  TopRow[];
    recent:        RecentEvent[];
    window_days:   number;
}>();

// ── Funnel drop-off ───────────────────────────────────────────────────────
const funnelSteps = computed(() => {
    const f = props.funnel;
    return [
        { label: 'Visits',   value: f.visits,   icon: Activity,        color: 'slate' },
        { label: 'Scanned',  value: f.scanned,  icon: ScanLine,        color: 'teal' },
        { label: 'Reported', value: f.reported, icon: FileBarChart2,   color: 'emerald' },
        { label: 'Signed up', value: f.signups, icon: UserPlus,        color: 'violet' },
    ];
});
function pctOf(value: number, ref: number): string {
    if (!ref) return '—';
    return `${Math.round((value / ref) * 100)}%`;
}

// ── Daily chart geometry ──────────────────────────────────────────────────
// Inline SVG; no chart library. Each day = one stacked bar (visit / scan / report).
const CHART_W = 720;
const CHART_H = 180;
const CHART_PAD = { top: 10, right: 10, bottom: 24, left: 32 };
const innerW = CHART_W - CHART_PAD.left - CHART_PAD.right;
const innerH = CHART_H - CHART_PAD.top - CHART_PAD.bottom;

const maxTotal = computed(() =>
    Math.max(10, ...props.daily.map((d) => d.visit + d.scan + d.report)),
);

const barLayout = computed(() => {
    const n = props.daily.length || 1;
    const slot = innerW / n;
    const barW = Math.max(2, slot - 2);
    return props.daily.map((d, i) => {
        const x = CHART_PAD.left + i * slot + (slot - barW) / 2;
        const total = d.visit + d.scan + d.report;
        const visitH = (d.visit / maxTotal.value) * innerH;
        const scanH = (d.scan / maxTotal.value) * innerH;
        const reportH = (d.report / maxTotal.value) * innerH;
        // Stack from bottom up: visit (base) → scan → report
        const baseY = CHART_PAD.top + innerH;
        return {
            ...d,
            x,
            w: barW,
            visitY: baseY - visitH,
            visitH,
            scanY: baseY - visitH - scanH,
            scanH,
            reportY: baseY - visitH - scanH - reportH,
            reportH,
            total,
        };
    });
});

const ticks = computed(() => {
    // Three Y-axis ticks: 0, mid, max
    const m = maxTotal.value;
    return [0, Math.round(m / 2), m];
});

const xLabels = computed(() => {
    // Show every ~6th day label to avoid clutter
    const step = Math.max(1, Math.floor(props.daily.length / 5));
    return props.daily
        .map((d, i) => ({ d, i }))
        .filter(({ i }) => i % step === 0 || i === props.daily.length - 1);
});

// ── Helpers ───────────────────────────────────────────────────────────────
const TYPE_BADGE: Record<RecentEvent['type'], string> = {
    visit:  'background:#f1f5f9; color:#475569;',
    scan:   'background:#ecfdf5; color:#059669;',
    report: 'background:#dbeafe; color:#2563eb;',
};

function flag(cc: string | null): string {
    if (!cc || cc.length !== 2) return '';
    const A = 0x1f1e6;
    return String.fromCodePoint(
        ...cc.toUpperCase().split('').map((c) => A + c.charCodeAt(0) - 65),
    );
}

function fmtDay(iso: string): string {
    return new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
}

function fmtAgo(iso: string): string {
    const t = new Date(iso).getTime();
    const s = Math.max(0, (Date.now() - t) / 1000);
    if (s < 60) return `${Math.floor(s)}s ago`;
    if (s < 3600) return `${Math.floor(s / 60)}m ago`;
    if (s < 86400) return `${Math.floor(s / 3600)}h ago`;
    return new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

// Top-X bar widths — relative to the largest in each list
function barPct(value: number, list: TopRow[]): number {
    const max = list[0]?.devices ?? 0;
    return max > 0 ? (value / max) * 100 : 0;
}
</script>

<template>
    <Head title="Demo statistics" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Demo statistics</h1>
            <p class="text-sm text-muted-foreground mt-1">
                Anonymous visitor usage of the public <code>/demo</code>. Funnel, top-X, daily chart and recent activity cover the last {{ window_days }} days; the headline numbers are lifetime.
            </p>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-slate-100 flex items-center justify-center">
                        <Activity class="size-4 text-slate-600" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">Demo devices</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.total_devices }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ stats.devices_7d }} active last 7d</p>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <ScanLine class="size-4 text-emerald-600" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">Total scans</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.total_scans }}</p>
                <p class="text-xs text-muted-foreground mt-1">AI photo scans served</p>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-blue-50 flex items-center justify-center">
                        <FileBarChart2 class="size-4 text-blue-600" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">Total reports</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.total_reports }}</p>
                <p class="text-xs text-muted-foreground mt-1">sample PDFs downloaded</p>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-violet-50 flex items-center justify-center">
                        <UserPlus class="size-4 text-violet-600" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">Signed up from demo</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.signups_from_demo }}</p>
                <p class="text-xs text-muted-foreground mt-1">accounts linked back to a demo device</p>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-amber-50 flex items-center justify-center">
                        <TrendingUp class="size-4 text-amber-500" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">Conversion</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.conversion_pct }}%</p>
                <p class="text-xs text-muted-foreground mt-1">of scanning devices that later signed up</p>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="size-9 rounded-lg bg-teal-50 flex items-center justify-center">
                        <Activity class="size-4 text-teal-600" />
                    </div>
                    <span class="text-sm text-muted-foreground font-medium">Last 7 days</span>
                </div>
                <p class="text-3xl font-bold tabular-nums">{{ stats.devices_7d }}</p>
                <p class="text-xs text-muted-foreground mt-1">distinct demo devices</p>
            </div>

        </div>

        <!-- Funnel -->
        <div class="rounded-xl border bg-card p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold">Funnel — last {{ window_days }} days</p>
                <p class="text-xs text-muted-foreground">distinct devices per stage / users for signup</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <template v-for="(step, i) in funnelSteps" :key="step.label">
                    <div class="rounded-lg border p-4 flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-muted-foreground">
                            <component :is="step.icon" class="size-3.5" />
                            {{ step.label }}
                        </div>
                        <p class="text-2xl font-bold tabular-nums">{{ step.value }}</p>
                        <p v-if="i > 0" class="text-xs text-muted-foreground tabular-nums">
                            {{ pctOf(step.value, funnelSteps[0].value) }} of visits
                            <span v-if="funnelSteps[i - 1].value > 0">
                                · {{ pctOf(step.value, funnelSteps[i - 1].value) }} from {{ funnelSteps[i - 1].label.toLowerCase() }}
                            </span>
                        </p>
                    </div>
                </template>
            </div>
        </div>

        <!-- Daily activity chart -->
        <div class="rounded-xl border bg-card p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold">Daily activity — last {{ window_days }} days</p>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-slate-300"></span>Visits</span>
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-emerald-400"></span>Scans</span>
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-emerald-700"></span>Reports</span>
                </div>
            </div>
            <svg :viewBox="`0 0 ${CHART_W} ${CHART_H}`" class="w-full h-auto" preserveAspectRatio="xMinYMid meet">
                <!-- Y-axis grid lines + labels -->
                <g v-for="t in ticks" :key="`t-${t}`">
                    <line
                        :x1="CHART_PAD.left"
                        :x2="CHART_W - CHART_PAD.right"
                        :y1="CHART_PAD.top + innerH - (t / maxTotal) * innerH"
                        :y2="CHART_PAD.top + innerH - (t / maxTotal) * innerH"
                        stroke="#e2e8f0"
                        stroke-dasharray="2 3"
                    />
                    <text
                        :x="CHART_PAD.left - 6"
                        :y="CHART_PAD.top + innerH - (t / maxTotal) * innerH + 3"
                        text-anchor="end"
                        font-size="9"
                        fill="#94a3b8"
                    >{{ t }}</text>
                </g>

                <!-- Stacked bars -->
                <g v-for="(b, i) in barLayout" :key="`b-${b.date}-${i}`">
                    <rect
                        :x="b.x" :y="b.visitY" :width="b.w" :height="b.visitH"
                        fill="#cbd5e1"
                        rx="1"
                    >
                        <title>{{ fmtDay(b.date) }} — {{ b.visit }} visits · {{ b.scan }} scans · {{ b.report }} reports</title>
                    </rect>
                    <rect
                        :x="b.x" :y="b.scanY" :width="b.w" :height="b.scanH"
                        fill="#34d399"
                        rx="1"
                    >
                        <title>{{ fmtDay(b.date) }} — {{ b.visit }} visits · {{ b.scan }} scans · {{ b.report }} reports</title>
                    </rect>
                    <rect
                        :x="b.x" :y="b.reportY" :width="b.w" :height="b.reportH"
                        fill="#047857"
                        rx="1"
                    >
                        <title>{{ fmtDay(b.date) }} — {{ b.visit }} visits · {{ b.scan }} scans · {{ b.report }} reports</title>
                    </rect>
                </g>

                <!-- X-axis labels -->
                <g v-for="({ d, i }) in xLabels" :key="`x-${d.date}-${i}`">
                    <text
                        :x="CHART_PAD.left + (i + 0.5) * (innerW / daily.length)"
                        :y="CHART_H - 6"
                        text-anchor="middle"
                        font-size="9"
                        fill="#94a3b8"
                    >{{ fmtDay(d.date) }}</text>
                </g>
            </svg>
        </div>

        <!-- Top X cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-2 mb-4">
                    <Globe class="size-4 text-slate-500" />
                    <p class="text-sm font-semibold">Top countries</p>
                </div>
                <div v-if="top_countries.length === 0" class="text-sm text-muted-foreground">No data yet.</div>
                <ul v-else class="space-y-2">
                    <li v-for="row in top_countries" :key="`c-${row.label}`" class="text-sm">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="font-medium">{{ flag(row.label) }} {{ row.label }}</span>
                            <span class="text-muted-foreground tabular-nums">{{ row.devices }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${barPct(row.devices, top_countries)}%` }"></div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-2 mb-4">
                    <Languages class="size-4 text-slate-500" />
                    <p class="text-sm font-semibold">Top locales</p>
                </div>
                <div v-if="top_locales.length === 0" class="text-sm text-muted-foreground">No data yet.</div>
                <ul v-else class="space-y-2">
                    <li v-for="row in top_locales" :key="`l-${row.label}`" class="text-sm">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="font-medium">{{ row.label }}</span>
                            <span class="text-muted-foreground tabular-nums">{{ row.devices }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-teal-500" :style="{ width: `${barPct(row.devices, top_locales)}%` }"></div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="flex items-center gap-2 mb-4">
                    <Link2 class="size-4 text-slate-500" />
                    <p class="text-sm font-semibold">Top referrers</p>
                </div>
                <div v-if="top_referers.length === 0" class="text-sm text-muted-foreground">No referrer data yet.</div>
                <ul v-else class="space-y-2">
                    <li v-for="row in top_referers" :key="`r-${row.label}`" class="text-sm">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="font-medium truncate" :title="row.label">{{ row.label }}</span>
                            <span class="text-muted-foreground tabular-nums shrink-0">{{ row.devices }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-violet-500" :style="{ width: `${barPct(row.devices, top_referers)}%` }"></div>
                        </div>
                    </li>
                </ul>
            </div>

        </div>

        <!-- Recent activity -->
        <div class="rounded-xl border bg-card p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold">Recent activity</p>
                <p class="text-xs text-muted-foreground">last {{ recent.length }} events</p>
            </div>
            <div v-if="recent.length === 0" class="text-sm text-muted-foreground py-4">No events yet.</div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-muted-foreground border-b">
                            <th class="py-2 pr-3 font-medium">Time</th>
                            <th class="py-2 pr-3 font-medium">Type</th>
                            <th class="py-2 pr-3 font-medium">Device</th>
                            <th class="py-2 pr-3 font-medium">Country</th>
                            <th class="py-2 pr-3 font-medium">Locale</th>
                            <th class="py-2 pr-3 font-medium">Referrer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in recent" :key="e.id" class="border-b last:border-0 hover:bg-slate-50/60">
                            <td class="py-2 pr-3 text-muted-foreground" :title="e.when">{{ fmtAgo(e.when) }}</td>
                            <td class="py-2 pr-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" :style="TYPE_BADGE[e.type]">
                                    {{ e.type }}
                                </span>
                            </td>
                            <td class="py-2 pr-3 font-mono text-xs">{{ e.device }}</td>
                            <td class="py-2 pr-3">
                                <span v-if="e.country">{{ flag(e.country) }} {{ e.country }}</span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="py-2 pr-3 text-muted-foreground">{{ e.locale ?? '—' }}</td>
                            <td class="py-2 pr-3 text-muted-foreground truncate max-w-[260px]" :title="e.referer ?? ''">
                                {{ e.referer ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</template>
