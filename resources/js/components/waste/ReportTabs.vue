<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { index as wasteReport, purchases as purchasesReport } from '@/routes/waste/report';

defineProps<{ active: 'waste' | 'purchases' }>();
</script>

<template>
    <div class="grid grid-cols-2 gap-1 rounded-2xl p-1" style="background: #e2e8f0;">
        <Link
            :href="purchasesReport().url"
            class="rounded-xl py-2.5 text-center text-sm font-bold transition-colors"
            :style="active === 'purchases' ? 'background: white; color: #047857; box-shadow: 0 1px 4px rgba(0,0,0,0.08);' : 'color: #64748b;'"
        >
            {{ $t('purchases.tab_purchases') }}
        </Link>
        <Link
            :href="wasteReport().url"
            class="rounded-xl py-2.5 text-center text-sm font-bold transition-colors"
            :style="active === 'waste' ? 'background: white; color: #047857; box-shadow: 0 1px 4px rgba(0,0,0,0.08);' : 'color: #64748b;'"
        >
            {{ $t('purchases.tab_waste') }}
        </Link>
    </div>
</template>
