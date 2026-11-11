<script setup lang="ts">
defineProps<{ period: string }>();

defineEmits<{ choose: [period: string] }>();

const options = ['this_month', 'last_month', 'this_year', 'all'] as const;
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <button
            v-for="option in options"
            :key="option"
            type="button"
            class="rounded-full px-4 h-9 text-xs font-bold"
            :style="period === option ? 'background: #059669; color: white;' : 'background: #f1f5f9; color: #475569;'"
            @click="$emit('choose', option)"
        >
            {{ $t(`purchases.period_${option}`) }}
        </button>
    </div>
</template>
