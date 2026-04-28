<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Camera, RotateCcw } from 'lucide-vue-next';
import CategoryPicker from '@/components/waste/CategoryPicker.vue';
import WeightInput from '@/components/waste/WeightInput.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { store as entriesStore } from '@/routes/waste/entries';
import { REASON_LABELS, type WasteCategory } from '@/types/waste';

type AiResult = {
    item_name: string;
    weight_kg: number | null;
    category: WasteCategory;
    reason: string;
    confidence: 'high' | 'medium' | 'low';
    notes: string | null;
};

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
        analyseError.value = 'Network error. Please try again.';
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
    saveForm.post(entriesStore().url, { onSuccess: () => retake() });
}

const CONFIDENCE_STYLES: Record<'high' | 'medium' | 'low', string> = {
    high:   'background: #ecfdf5; color: #059669;',
    medium: 'background: #fefce8; color: #ca8a04;',
    low:    'background: #fef2f2; color: #dc2626;',
};
</script>

<template>
    <Head title="AI Scan" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-4 space-y-5">

        <!-- Step 1: Upload -->
        <div v-if="step === 'upload'">
            <div class="bg-white rounded-2xl border border-slate-100 p-5" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <h1 class="text-xl font-bold text-slate-900 mb-1">AI Waste Scanner</h1>
                <p class="text-sm text-slate-400 mb-5">
                    Take a photo of the food waste. AI will extract the details automatically.
                </p>

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
                            <p class="font-semibold text-slate-700">Take or upload a photo</p>
                            <p class="text-xs text-slate-400 mt-1">JPG, PNG, WebP up to 10 MB</p>
                        </div>
                    </template>
                    <template v-else>
                        <img :src="previewUrl" alt="Preview" class="max-h-64 rounded-xl object-contain" />
                        <p class="text-xs text-slate-400">Tap to change photo</p>
                    </template>
                </label>

                <p v-if="analyseError" class="mt-3 text-sm font-medium" style="color: #dc2626;">
                    {{ analyseError }}
                </p>

                <button
                    class="mt-4 w-full h-12 rounded-xl font-semibold text-base text-white transition-opacity disabled:opacity-50"
                    style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 16px rgba(5,150,105,0.25);"
                    :disabled="!previewUrl || analysing"
                    @click="analyse"
                >
                    <span v-if="analysing">Analysing…</span>
                    <span v-else>Analyse with AI</span>
                </button>
            </div>
        </div>

        <!-- Step 2: Review -->
        <div v-else>
            <div class="bg-white rounded-2xl border border-slate-100 p-5" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-xl font-bold text-slate-900">Review AI Results</h1>
                    <span
                        v-if="aiResult"
                        class="rounded-full px-3 py-1 text-xs font-bold capitalize"
                        :style="CONFIDENCE_STYLES[aiResult.confidence]"
                    >
                        {{ aiResult.confidence }} confidence
                    </span>
                </div>

                <div v-if="previewUrl" class="mb-5">
                    <img :src="previewUrl" alt="Scanned" class="max-h-40 w-full rounded-xl object-contain bg-slate-50" />
                </div>

                <form class="space-y-5" @submit.prevent="save">
                    <div class="space-y-2">
                        <Label class="text-slate-700 font-semibold">Category</Label>
                        <CategoryPicker v-model="saveForm.category" />
                        <InputError :message="saveForm.errors.category" />
                    </div>

                    <div class="space-y-2">
                        <Label for="review_item" class="text-slate-700 font-semibold">Item Name</Label>
                        <Input
                            id="review_item"
                            v-model="saveForm.item_name"
                            class="h-12 text-base border-slate-200 bg-slate-50 text-slate-900"
                        />
                        <InputError :message="saveForm.errors.item_name" />
                    </div>

                    <div class="space-y-2">
                        <Label class="text-slate-700 font-semibold">Weight</Label>
                        <WeightInput v-model="saveForm.weight_kg" />
                        <InputError :message="saveForm.errors.weight_kg" />
                    </div>

                    <div class="space-y-2">
                        <Label class="text-slate-700 font-semibold">Reason for Waste</Label>
                        <Select v-model="saveForm.reason">
                            <SelectTrigger class="h-12 w-full text-base border-slate-200 bg-slate-50 text-slate-900">
                                <SelectValue placeholder="Select reason…" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="(label, key) in REASON_LABELS" :key="key" :value="key">
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="saveForm.errors.reason" />
                    </div>

                    <div class="space-y-2">
                        <Label for="review_notes" class="text-slate-700 font-semibold">
                            Notes <span class="text-slate-400 font-normal">(optional)</span>
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
                            Retake
                        </button>
                        <button
                            type="submit"
                            class="flex-1 h-12 rounded-xl font-semibold text-base text-white transition-opacity disabled:opacity-50"
                            style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 16px rgba(5,150,105,0.25);"
                            :disabled="saveForm.processing || !saveForm.category || !saveForm.item_name"
                        >
                            {{ saveForm.processing ? 'Saving…' : 'Save Entry' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
