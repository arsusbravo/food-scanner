<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Trash2, ScanLine } from 'lucide-vue-next';
import { Spinner } from '@/components/ui/spinner';
import CategoryPicker from '@/components/waste/CategoryPicker.vue';
import WeightInput from '@/components/waste/WeightInput.vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { store, destroy } from '@/routes/waste/entries';
import { index as aiScanIndex } from '@/routes/waste/ai-scan';
import { type WasteEntry, type WasteCategory } from '@/types/waste';

type Props = { recentEntries: WasteEntry[] };
defineProps<Props>();

const { t } = useI18n();

const form = useForm({
    category: '' as WasteCategory | '',
    item_name: '',
    weight_kg: '' as number | '',
    reason: '',
    notes: '',
});

const reasonOptions = computed(() => [
    { value: 'spoilage',       label: t('log_waste.reasons.spoilage') },
    { value: 'overproduction', label: t('log_waste.reasons.overproduction') },
    { value: 'expiry',         label: t('log_waste.reasons.expiry') },
    { value: 'prep_waste',     label: t('log_waste.reasons.prep_waste') },
    { value: 'other',          label: t('log_waste.reasons.other') },
]);

function submit() {
    form.post(store().url, { onSuccess: () => form.reset() });
}

function deleteEntry(entry: WasteEntry) {
    if (!confirm(t('log_waste.delete_confirm'))) return;
    useForm({}).delete(destroy(entry.id).url);
}

function formatWeight(kg: string): string {
    const num = parseFloat(kg);
    return num >= 1 ? `${num.toFixed(2)} kg` : `${(num * 1000).toFixed(0)} g`;
}
</script>

<template>
    <Head title="Log Waste" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-8 space-y-5">

        <!-- Form card -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <h1 class="text-xl font-bold text-slate-900 mb-5">{{ $t('log_waste.title') }}</h1>

            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label class="text-slate-700 font-semibold">{{ $t('log_waste.category') }}</Label>
                    <CategoryPicker v-model="form.category" />
                    <InputError :message="form.errors.category" />
                </div>

                <div class="space-y-2">
                    <Label for="item_name" class="text-slate-700 font-semibold">{{ $t('log_waste.item_name') }}</Label>
                    <Input
                        id="item_name"
                        v-model="form.item_name"
                        :placeholder="$t('log_waste.item_placeholder')"
                        class="h-12 text-base border-slate-200 bg-slate-50 text-slate-900 placeholder:text-slate-400"
                    />
                    <InputError :message="form.errors.item_name" />
                </div>

                <div class="space-y-2">
                    <Label class="text-slate-700 font-semibold">{{ $t('log_waste.weight') }}</Label>
                    <WeightInput v-model="form.weight_kg" />
                    <InputError :message="form.errors.weight_kg" />
                </div>

                <div class="space-y-2">
                    <Label class="text-slate-700 font-semibold">{{ $t('log_waste.reason') }}</Label>
                    <Select v-model="form.reason">
                        <SelectTrigger class="h-12 w-full text-base border-slate-200 bg-slate-50 text-slate-900">
                            <SelectValue :placeholder="$t('log_waste.reason_placeholder')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="opt in reasonOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.reason" />
                </div>

                <div class="space-y-2">
                    <Label for="notes" class="text-slate-700 font-semibold">
                        {{ $t('log_waste.notes') }}
                        <span class="text-slate-400 font-normal">{{ $t('log_waste.notes_optional') }}</span>
                    </Label>
                    <Input
                        id="notes"
                        v-model="form.notes"
                        :placeholder="$t('log_waste.notes_placeholder')"
                        class="border-slate-200 bg-slate-50 text-slate-900 placeholder:text-slate-400"
                    />
                </div>

                <button
                    type="submit"
                    class="w-full h-12 rounded-xl font-semibold text-base text-white transition-opacity disabled:opacity-50 flex items-center justify-center gap-2"
                    style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 16px rgba(5,150,105,0.25);"
                    :disabled="form.processing || !form.category || !form.item_name || form.weight_kg === ''"
                >
                    <Spinner v-if="form.processing" class="text-white" />
                    {{ form.processing ? $t('log_waste.submitting') : $t('log_waste.submit') }}
                </button>
            </form>
        </div>

        <!-- AI Scan shortcut -->
        <a
            :href="aiScanIndex().url as string"
            class="flex items-center justify-center gap-3 rounded-2xl border-2 border-dashed p-4 text-sm font-medium transition-colors"
            style="border-color: #6ee7b7; color: #059669; background: #f0fdf4;"
        >
            <ScanLine style="width: 20px; height: 20px;" />
            <span>{{ $t('log_waste.ai_shortcut') }}</span>
        </a>

        <!-- Recent entries -->
        <div>
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-3">{{ $t('log_waste.recent_title') }}</h2>

            <div
                v-if="recentEntries.length === 0"
                class="bg-white rounded-2xl p-8 text-center text-sm text-slate-400 border border-slate-100"
            >
                {{ $t('log_waste.no_entries') }}
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
                            {{ $t(`log_waste.categories.${entry.category}`) }} · {{ formatWeight(entry.weight_kg) }} · {{ $t(`log_waste.reasons.${entry.reason}`) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 ml-3 shrink-0">
                        <span
                            v-if="entry.source === 'ai_scan'"
                            class="text-xs font-bold px-2 py-0.5 rounded-full"
                            style="background: #ecfdf5; color: #059669;"
                        >AI</span>
                        <button
                            type="button"
                            class="transition-colors text-slate-300 hover:text-red-400"
                            @click="deleteEntry(entry)"
                        >
                            <Trash2 style="width: 16px; height: 16px;" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
