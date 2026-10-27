<script setup lang="ts">
import InputError from '@/components/InputError.vue';

defineProps<{
    currency: string;
    suggested: boolean;
    error?: string;
}>();

const model = defineModel<number | ''>({ default: '' });

const emit = defineEmits<{ edited: [] }>();

function onInput(e: Event) {
    const val = (e.target as HTMLInputElement).value;
    const num = parseFloat(val);
    model.value = val === '' || isNaN(num) ? '' : num;
    emit('edited');
}
</script>

<template>
    <div class="space-y-2">
        <label style="font-size: 13px; font-weight: 700; color: #374151;">
            {{ $t('cost.entry_label') }}
            <span style="font-weight: 400; color: #94a3b8;">({{ currency }})</span>
        </label>
        <input
            type="number"
            inputmode="decimal"
            min="0"
            step="0.01"
            placeholder="0.00"
            :value="model"
            style="width: 100%; height: 48px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #0f172a; font-size: 15px; padding: 0 14px; outline: none; box-sizing: border-box;"
            @input="onInput"
        />
        <p class="text-xs" :style="suggested ? 'color: #059669;' : 'color: #94a3b8;'">
            {{ suggested ? $t('cost.suggested') : $t('cost.entry_hint') }}
        </p>
        <InputError :message="error" />
    </div>
</template>
