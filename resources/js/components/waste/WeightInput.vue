<script setup lang="ts">
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';

const model = defineModel<number | ''>({ default: '' });

const unit = ref<'kg' | 'g'>('kg');
const displayValue = ref<string>('');

watch(
    model,
    (kg) => {
        if (kg === '' || kg === null || kg === undefined) {
            displayValue.value = '';
            return;
        }
        displayValue.value = unit.value === 'g' ? String(Number(kg) * 1000) : String(kg);
    },
    { immediate: true },
);

function onInput(e: Event) {
    const val = (e.target as HTMLInputElement).value;
    displayValue.value = val;
    const num = parseFloat(val);
    if (isNaN(num) || val === '') {
        model.value = '';
        return;
    }
    model.value = unit.value === 'g' ? num / 1000 : num;
}

function toggleUnit() {
    const prev = unit.value;
    unit.value = prev === 'kg' ? 'g' : 'kg';
    if (model.value !== '' && model.value !== null) {
        displayValue.value = unit.value === 'g'
            ? String(Number(model.value) * 1000)
            : String(Number(model.value));
    }
}
</script>

<template>
    <div class="flex gap-2">
        <Input
            type="number"
            inputmode="decimal"
            min="0"
            step="any"
            placeholder="0.00"
            :value="displayValue"
            class="flex-1 h-12 text-lg border-slate-200 bg-slate-50 text-slate-900 placeholder:text-slate-400"
            @input="onInput"
        />
        <button
            type="button"
            class="flex min-w-[4rem] items-center justify-center rounded-md border-2 px-3 font-bold text-sm transition-colors"
            style="border-color: #6ee7b7; color: #059669; background: #f0fdf4;"
            @click="toggleUnit"
        >
            {{ unit }}
        </button>
    </div>
</template>
