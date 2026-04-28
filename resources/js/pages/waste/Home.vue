<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { UtensilsCrossed, ScanLine, ChevronRight, TrendingUp, CalendarDays } from 'lucide-vue-next';
import { CATEGORY_LABELS, REASON_LABELS, type WasteEntry } from '@/types/waste';

type Props = {
    todayKg: number;
    todayCount: number;
    weekKg: number;
    weekCount: number;
    recentEntries: WasteEntry[];
};

const props = defineProps<Props>();

const page = usePage<{ auth: { user: { name: string } } }>();
const firstName = computed(() => page.props.auth?.user?.name?.split(' ')[0] ?? 'there');

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Good morning';
    if (h < 18) return 'Good afternoon';
    return 'Good evening';
});

function fmtKg(kg: number): string {
    if (kg === 0) return '0 g';
    return kg >= 1 ? `${kg.toFixed(2)} kg` : `${(kg * 1000).toFixed(0)} g`;
}

function fmtEntryWeight(kg: string): string {
    const n = parseFloat(kg);
    return n >= 1 ? `${n.toFixed(2)} kg` : `${(n * 1000).toFixed(0)} g`;
}

const todayDate = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });
</script>

<template>
    <Head title="Home" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-8 space-y-5">

        <!-- Greeting -->
        <div>
            <p class="text-slate-400 text-sm">{{ todayDate }}</p>
            <h2 class="text-2xl font-bold text-slate-900 mt-0.5">{{ greeting }}, {{ firstName }}</h2>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 gap-3">
            <!-- Today -->
            <div class="bg-white rounded-2xl p-4 border border-slate-100" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <div class="flex items-center gap-2 mb-3">
                    <div class="size-8 rounded-xl flex items-center justify-center" style="background: #ecfdf5;">
                        <CalendarDays style="width: 15px; height: 15px; color: #059669;" />
                    </div>
                    <span class="text-xs font-semibold text-slate-500">Today</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 tabular-nums">{{ fmtKg(todayKg) }}</p>
                <p class="text-xs text-slate-400 mt-1">
                    {{ todayCount }} {{ todayCount === 1 ? 'entry' : 'entries' }}
                </p>
            </div>

            <!-- This week -->
            <div class="bg-white rounded-2xl p-4 border border-slate-100" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <div class="flex items-center gap-2 mb-3">
                    <div class="size-8 rounded-xl flex items-center justify-center" style="background: #f0fdfa;">
                        <TrendingUp style="width: 15px; height: 15px; color: #0d9488;" />
                    </div>
                    <span class="text-xs font-semibold text-slate-500">This week</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 tabular-nums">{{ fmtKg(weekKg) }}</p>
                <p class="text-xs text-slate-400 mt-1">
                    {{ weekCount }} {{ weekCount === 1 ? 'entry' : 'entries' }}
                </p>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="grid grid-cols-2 gap-3">
            <Link
                href="/waste/entries"
                class="rounded-2xl p-4 flex items-center gap-3 transition-opacity active:opacity-80"
                style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 16px rgba(5,150,105,0.3);"
            >
                <div class="size-10 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(255,255,255,0.2);">
                    <UtensilsCrossed style="width: 20px; height: 20px; color: white;" />
                </div>
                <div>
                    <p class="font-semibold text-sm text-white">Log Waste</p>
                    <p class="text-xs mt-0.5" style="color: rgba(167,243,208,0.8);">Manual entry</p>
                </div>
            </Link>

            <Link
                href="/waste/ai-scan"
                class="rounded-2xl p-4 flex items-center gap-3 transition-opacity active:opacity-80"
                style="background: linear-gradient(135deg, #0d9488, #0f766e); box-shadow: 0 4px 16px rgba(13,148,136,0.3);"
            >
                <div class="size-10 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(255,255,255,0.2);">
                    <ScanLine style="width: 20px; height: 20px; color: white;" />
                </div>
                <div>
                    <p class="font-semibold text-sm text-white">AI Scan</p>
                    <p class="text-xs mt-0.5" style="color: rgba(153,246,228,0.8);">Photo scan</p>
                </div>
            </Link>
        </div>

        <!-- Recent entries -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-slate-800">Recent Entries</h3>
                <Link href="/waste/entries" class="flex items-center gap-0.5 text-xs font-semibold" style="color: #059669;">
                    See all <ChevronRight style="width: 14px; height: 14px;" />
                </Link>
            </div>

            <div
                v-if="recentEntries.length === 0"
                class="bg-white rounded-2xl p-8 text-center text-sm text-slate-400 border border-slate-100"
            >
                No entries yet. Start logging food waste.
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="entry in recentEntries"
                    :key="entry.id"
                    class="bg-white rounded-xl border border-slate-100 px-4 py-3 flex items-center justify-between"
                    style="box-shadow: 0 1px 4px rgba(0,0,0,0.04);"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-900 truncate text-sm">{{ entry.item_name }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ CATEGORY_LABELS[entry.category] }} · {{ fmtEntryWeight(entry.weight_kg) }} · {{ REASON_LABELS[entry.reason] }}
                        </p>
                    </div>
                    <span
                        v-if="entry.source === 'ai_scan'"
                        class="ml-3 shrink-0 text-xs font-bold px-2 py-0.5 rounded-full"
                        style="background: #ecfdf5; color: #059669;"
                    >AI</span>
                </div>
            </div>
        </div>
    </div>
</template>
