<script setup lang="ts">
import { CATEGORY_COLORS, CATEGORY_EMOJIS, CATEGORY_LABELS, type WasteCategory } from '@/types/waste';

const model = defineModel<WasteCategory | ''>({ default: '' });

const categories: WasteCategory[] = ['protein', 'veg', 'dairy', 'prepared'];
</script>

<template>
    <div class="grid grid-cols-2 gap-3">
        <button
            v-for="cat in categories"
            :key="cat"
            type="button"
            :class="[
                'flex flex-col items-center justify-center gap-2 rounded-xl border-2 p-5 text-center font-medium transition-all active:scale-95',
                model === cat
                    ? CATEGORY_COLORS[cat] + ' ring-2 ring-offset-1'
                    : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-emerald-300 hover:bg-emerald-50',
            ]"
            @click="model = cat"
        >
            <span class="text-3xl">{{ CATEGORY_EMOJIS[cat] }}</span>
            <span class="text-sm font-semibold">{{ CATEGORY_LABELS[cat] }}</span>
        </button>
    </div>
</template>
