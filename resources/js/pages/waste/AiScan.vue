<script setup lang="ts">
import { useForm, Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Camera, RotateCcw } from 'lucide-vue-next';
import { Spinner } from '@/components/ui/spinner';
import CategoryPicker from '@/components/waste/CategoryPicker.vue';
import WeightInput from '@/components/waste/WeightInput.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { store as entriesStore } from '@/routes/waste/entries';
import { type WasteCategory } from '@/types/waste';

type AiResult = {
    item_name: string;
    weight_kg: number | null;
    category: WasteCategory;
    reason: string;
    confidence: 'high' | 'medium' | 'low';
    notes: string | null;
};

const { t } = useI18n();

const step = ref<'upload' | 'review'>('upload');
const previewUrl = ref<string | null>(null);
const aiResult = ref<AiResult | null>(null);
const analysing = ref(false);
const analyseError = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const saveForm = useForm({
    category: '' as WasteCategory | '',
    item_name: '',
    weight_kg: '' as number | '',
    reason: '',
    notes: '',
    source: 'ai_scan' as const,
});

const reasonOptions = computed(() => [
    { value: 'spoilage',       label: t('log_waste.reasons.spoilage') },
    { value: 'overproduction', label: t('log_waste.reasons.overproduction') },
    { value: 'expiry',         label: t('log_waste.reasons.expiry') },
    { value: 'prep_waste',     label: t('log_waste.reasons.prep_waste') },
    { value: 'other',          label: t('log_waste.reasons.other') },
]);

function onFileSelect(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    previewUrl.value = URL.createObjectURL(file);
    analyseError.value = null;
}

function compressImage(file: File, maxPx = 1920, quality = 0.85): Promise<Blob> {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => {
            const scale = Math.min(1, maxPx / Math.max(img.width, img.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            canvas.getContext('2d')!.drawImage(img, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => resolve(blob ?? file), 'image/jpeg', quality);
        };
        img.src = URL.createObjectURL(file);
    });
}

async function analyse() {
    const file = fileInput.value?.files?.[0];
    if (!file) return;

    analysing.value = true;
    analyseError.value = null;

    console.log('[AIScan] original file', { name: file.name, size: file.size, type: file.type });

    const compressed = await compressImage(file);
    console.log('[AIScan] compressed', { size: compressed.size, type: compressed.type });

    const base64 = await new Promise<string>((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => resolve((e.target!.result as string).split(',')[1]);
        reader.onerror = reject;
        reader.readAsDataURL(compressed);
    });

    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
    console.log('[AIScan] sending request', {
        base64Length: base64.length,
        csrfPresent: !!csrfToken,
        url: '/waste/ai-scan',
    });

    try {
        const res = await fetch('/waste/ai-scan', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ photo: base64 }),
        });

        console.log('[AIScan] response', { status: res.status, ok: res.ok });

        if (!res.ok) {
            const body = await res.json().catch(() => ({}));
            console.error('[AIScan] error body', body);
            const firstValidationError = body.errors
                ? Object.values(body.errors as Record<string, string[]>)[0]?.[0]
                : null;
            const rawMessage = firstValidationError ?? body.error ?? body.message ?? '';
            analyseError.value = rawMessage.toLowerCase().includes('required')
                ? 'Upload failed: image is too large for the server. Please try a smaller photo.'
                : rawMessage || 'Analysis failed. Please try again.';
            return;
        }

        const result: AiResult = await res.json();
        console.log('[AIScan] success', result);
        aiResult.value = result;
        saveForm.category = result.category;
        saveForm.item_name = result.item_name;
        saveForm.weight_kg = result.weight_kg ?? '';
        saveForm.reason = result.reason;
        saveForm.notes = result.notes ?? '';
        step.value = 'review';
    } catch (err) {
        console.error('[AIScan] network error', err);
        analyseError.value = t('ai_scan.error_network');
    } finally {
        analysing.value = false;
    }
}

function retake() {
    step.value = 'upload';
    previewUrl.value = null;
    aiResult.value = null;
    analyseError.value = null;
    if (fileInput.value) fileInput.value.value = '';
    saveForm.reset();
}

function save() {
    saveForm.post(entriesStore().url, { onSuccess: () => router.visit('/waste/entries') });
}

const CONFIDENCE_STYLES: Record<'high' | 'medium' | 'low', string> = {
    high:   'background: #ecfdf5; color: #059669;',
    medium: 'background: #fefce8; color: #ca8a04;',
    low:    'background: #fef2f2; color: #dc2626;',
};
</script>

<template>
    <Head title="AI Scan" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-8 space-y-5">

        <!-- Step 1: Upload -->
        <div v-if="step === 'upload'">
            <div class="bg-white rounded-2xl border border-slate-100 p-5" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <h1 class="text-xl font-bold text-slate-900 mb-1">{{ $t('ai_scan.title') }}</h1>
                <p class="text-sm text-slate-400 mb-5">{{ $t('ai_scan.subtitle') }}</p>

                <!-- Drop zone -->
                <label
                    class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed p-8 text-center transition-colors"
                    :style="previewUrl
                        ? 'border-color: #34d399; background: #f0fdf4;'
                        : 'border-color: #d1fae5; background: #f9fafb;'"
                >
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        capture="environment"
                        class="sr-only"
                        @change="onFileSelect"
                    />
                    <template v-if="!previewUrl">
                        <div class="size-14 rounded-2xl flex items-center justify-center" style="background: #ecfdf5;">
                            <Camera style="width: 28px; height: 28px; color: #059669;" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-700">{{ $t('ai_scan.upload_label') }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ $t('ai_scan.upload_hint') }}</p>
                        </div>
                    </template>
                    <template v-else>
                        <div class="relative w-full">
                            <img :src="previewUrl" alt="Preview" class="max-h-64 w-full rounded-xl object-contain" />
                            <!-- Scanning overlay -->
                            <div
                                v-if="analysing"
                                class="absolute inset-0 rounded-xl flex flex-col items-center justify-center gap-3"
                                style="background: rgba(5,150,105,0.88); backdrop-filter: blur(2px);"
                            >
                                <Spinner class="text-white" style="width:32px;height:32px;" />
                                <p class="text-white font-semibold text-sm tracking-wide">{{ $t('ai_scan.analysing') }}</p>
                            </div>
                        </div>
                        <p v-if="!analysing" class="text-xs text-slate-400">{{ $t('ai_scan.change_photo') }}</p>
                    </template>
                </label>

                <p v-if="analyseError" class="mt-3 text-sm font-medium" style="color: #dc2626;">
                    {{ analyseError }}
                </p>

                <button
                    class="mt-4 w-full h-12 rounded-xl font-semibold text-base text-white transition-opacity disabled:opacity-50 flex items-center justify-center gap-2"
                    style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 16px rgba(5,150,105,0.25);"
                    :disabled="!previewUrl || analysing"
                    @click="analyse"
                >
                    <Spinner v-if="analysing" class="text-white" />
                    {{ analysing ? $t('ai_scan.analysing') : $t('ai_scan.analyse') }}
                </button>
            </div>
        </div>

        <!-- Step 2: Review -->
        <div v-else>
            <div class="bg-white rounded-2xl border border-slate-100 p-5" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-xl font-bold text-slate-900">{{ $t('ai_scan.review_title') }}</h1>
                    <span
                        v-if="aiResult"
                        class="rounded-full px-3 py-1 text-xs font-bold capitalize"
                        :style="CONFIDENCE_STYLES[aiResult.confidence]"
                    >
                        {{ $t(`ai_scan.confidence_${aiResult.confidence}`) }}
                    </span>
                </div>

                <div v-if="previewUrl" class="mb-5">
                    <img :src="previewUrl" alt="Scanned" class="max-h-40 w-full rounded-xl object-contain bg-slate-50" />
                </div>

                <form class="space-y-5" @submit.prevent="save">
                    <div class="space-y-2">
                        <Label class="text-slate-700 font-semibold">{{ $t('log_waste.category') }}</Label>
                        <CategoryPicker v-model="saveForm.category" />
                        <InputError :message="saveForm.errors.category" />
                    </div>

                    <div class="space-y-2">
                        <Label for="review_item" class="text-slate-700 font-semibold">{{ $t('log_waste.item_name') }}</Label>
                        <Input
                            id="review_item"
                            v-model="saveForm.item_name"
                            class="h-12 text-base border-slate-200 bg-slate-50 text-slate-900"
                        />
                        <InputError :message="saveForm.errors.item_name" />
                    </div>

                    <div class="space-y-2">
                        <Label class="text-slate-700 font-semibold">{{ $t('log_waste.weight') }}</Label>
                        <WeightInput v-model="saveForm.weight_kg" />
                        <InputError :message="saveForm.errors.weight_kg" />
                    </div>

                    <div class="space-y-2">
                        <Label class="text-slate-700 font-semibold">{{ $t('log_waste.reason') }}</Label>
                        <Select v-model="saveForm.reason">
                            <SelectTrigger class="h-12 w-full text-base border-slate-200 bg-slate-50 text-slate-900">
                                <SelectValue :placeholder="$t('log_waste.reason_placeholder')" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="opt in reasonOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="saveForm.errors.reason" />
                    </div>

                    <div class="space-y-2">
                        <Label for="review_notes" class="text-slate-700 font-semibold">
                            {{ $t('log_waste.notes') }}
                            <span class="text-slate-400 font-normal">{{ $t('log_waste.notes_optional') }}</span>
                        </Label>
                        <Input
                            id="review_notes"
                            v-model="saveForm.notes"
                            class="border-slate-200 bg-slate-50 text-slate-900"
                        />
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            class="flex-1 h-12 rounded-xl font-semibold text-sm border-2 flex items-center justify-center gap-2 transition-colors"
                            style="border-color: #d1fae5; color: #059669; background: #f0fdf4;"
                            @click="retake"
                        >
                            <RotateCcw style="width: 16px; height: 16px;" />
                            {{ $t('ai_scan.retake') }}
                        </button>
                        <button
                            type="submit"
                            class="flex-1 h-12 rounded-xl font-semibold text-base text-white transition-opacity disabled:opacity-50 flex items-center justify-center gap-2"
                            style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 16px rgba(5,150,105,0.25);"
                            :disabled="saveForm.processing || !saveForm.category || !saveForm.item_name"
                        >
                            <Spinner v-if="saveForm.processing" class="text-white" />
                            {{ saveForm.processing ? $t('ai_scan.saving') : $t('ai_scan.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
