<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { Camera, RotateCcw, Trash2, FileBarChart2 } from 'lucide-vue-next';
import { Spinner } from '@/components/ui/spinner';
import CategoryPicker from '@/components/waste/CategoryPicker.vue';
import WeightInput from '@/components/waste/WeightInput.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTurnstile } from '@/composables/useTurnstile';
import WhyRegister from '@/components/demo/WhyRegister.vue';
import { csrfFetch } from '@/lib/csrf';
import { type WasteCategory } from '@/types/waste';

type AiResult = {
    item_name: string;
    weight_kg: number | null;
    category: WasteCategory;
    reason: string;
    confidence: 'high' | 'medium' | 'low';
    notes: string | null;
};
type DemoEntry = {
    category: WasteCategory;
    item_name: string;
    weight_kg: number;
    reason: string;
    notes: string;
};
type Remaining = { scans: number; reports: number };
type SummaryRow = {
    category: string;
    flw_group: string;
    eu_code: string;
    total_kg: number;
    entry_count: number;
};

const props = defineProps<{
    turnstileSiteKey: string | null;
    remaining: Remaining;
}>();

// Demo allowance numbers come from the server via `remaining` (config/plans.php).
const STORAGE_KEY = 'demo_entries';

const { t } = useI18n();

const step = ref<'scan' | 'review' | 'report'>('scan');
const remaining = ref<Remaining>({ ...props.remaining });
const entries = ref<DemoEntry[]>([]);

const previewUrl = ref<string | null>(null);
const aiResult = ref<AiResult | null>(null);
const analysing = ref(false);
const errorMsg = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const reviewForm = ref<DemoEntry>({
    category: '' as WasteCategory,
    item_name: '',
    weight_kg: '' as unknown as number,
    reason: '',
    notes: '',
});

const summary = ref<Record<string, SummaryRow> | null>(null);
const reportTotal = ref(0);
const reportCount = ref(0);
const generating = ref(false);
const downloading = ref(false);

// One Turnstile widget per action, placed directly above its button so the
// user can see when it has finished loading. Each instance is independent —
// the scan widget resets after each photo, the PDF widget after each download.
const {
    container: scanTurnstileEl,
    reset: resetScanTurnstile,
    getToken: getScanToken,
    ready: scanReady,
} = useTurnstile(props.turnstileSiteKey);
const {
    container: pdfTurnstileEl,
    reset: resetPdfTurnstile,
    getToken: getPdfToken,
    ready: pdfReady,
} = useTurnstile(props.turnstileSiteKey);

// Buttons that need a Turnstile token must wait until the widget has actually
// produced one — otherwise a quick click submits an empty token and the
// server rejects it as a CAPTCHA failure.
const scanTurnstileBusy = computed(() => !!props.turnstileSiteKey && !scanReady.value);
const pdfTurnstileBusy = computed(() => !!props.turnstileSiteKey && !pdfReady.value);

const scansLeft = computed(() => remaining.value.scans);
const reportsLeft = computed(() => remaining.value.reports);
const scansExhausted = computed(() => scansLeft.value <= 0);

const reasonOptions = computed(() => [
    { value: 'spoilage', label: t('log_waste.reasons.spoilage') },
    { value: 'overproduction', label: t('log_waste.reasons.overproduction') },
    { value: 'expiry', label: t('log_waste.reasons.expiry') },
    { value: 'prep_waste', label: t('log_waste.reasons.prep_waste') },
    { value: 'other', label: t('log_waste.reasons.other') },
]);

onMounted(() => {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) entries.value = JSON.parse(raw);
    } catch {
        entries.value = [];
    }
});

function persist() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(entries.value));
}

function onFileSelect(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    previewUrl.value = URL.createObjectURL(file);
    errorMsg.value = null;
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

    const token = getScanToken();
    if (props.turnstileSiteKey && !token) {
        errorMsg.value = t('demo.verify_required');
        return;
    }

    analysing.value = true;
    errorMsg.value = null;

    try {
        const compressed = await compressImage(file);
        const base64 = await new Promise<string>((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => resolve((e.target!.result as string).split(',')[1]);
            reader.onerror = reject;
            reader.readAsDataURL(compressed);
        });

        const res = await csrfFetch('/demo/scan', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ photo: base64, 'cf-turnstile-response': token }),
        });

        const body = await res.json().catch(() => ({}));

        if (!res.ok) {
            if (body.remaining) remaining.value = body.remaining;
            errorMsg.value = body.error || t('demo.error_generic');
            resetScanTurnstile();
            return;
        }

        aiResult.value = body as AiResult;
        if (body.remaining) remaining.value = body.remaining;
        reviewForm.value = {
            category: body.category,
            item_name: body.item_name,
            weight_kg: (body.weight_kg ?? '') as number,
            reason: body.reason,
            notes: body.notes ?? '',
        };
        step.value = 'review';
        resetScanTurnstile();
    } catch {
        errorMsg.value = t('demo.error_network');
        resetScanTurnstile();
    } finally {
        analysing.value = false;
    }
}

function addEntry() {
    if (!reviewForm.value.category || !reviewForm.value.item_name || reviewForm.value.weight_kg === ('' as unknown as number)) {
        return;
    }
    entries.value.push({ ...reviewForm.value, weight_kg: Number(reviewForm.value.weight_kg) });
    persist();
    resetScan();
    step.value = 'scan';
}

function resetScan() {
    previewUrl.value = null;
    aiResult.value = null;
    errorMsg.value = null;
    if (fileInput.value) fileInput.value.value = '';
}

function removeEntry(i: number) {
    entries.value.splice(i, 1);
    persist();
}

function startOver() {
    entries.value = [];
    persist();
    summary.value = null;
    step.value = 'scan';
    resetScan();
}

async function generateReport() {
    if (!entries.value.length) return;
    generating.value = true;
    errorMsg.value = null;
    try {
        const res = await csrfFetch('/demo/report', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ entries: entries.value }),
        });
        const body = await res.json().catch(() => ({}));
        if (!res.ok) {
            errorMsg.value = body.error || t('demo.error_generic');
            return;
        }
        summary.value = body.summary;
        reportTotal.value = body.grandTotal;
        reportCount.value = body.totalEntries;
        step.value = 'report';
    } catch {
        errorMsg.value = t('demo.error_network');
    } finally {
        generating.value = false;
    }
}

async function downloadPdf() {
    const token = getPdfToken();
    if (props.turnstileSiteKey && !token) {
        errorMsg.value = t('demo.verify_required');
        return;
    }
    downloading.value = true;
    errorMsg.value = null;
    try {
        const res = await csrfFetch('/demo/report/pdf', {
            method: 'POST',
            headers: {
                Accept: 'application/pdf',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ entries: entries.value, 'cf-turnstile-response': token }),
        });

        if (!res.ok) {
            const body = await res.json().catch(() => ({}));
            if (body.remaining) remaining.value = body.remaining;
            errorMsg.value = body.error || t('demo.error_generic');
            resetPdfTurnstile();
            return;
        }

        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'eu-food-waste-report-SAMPLE.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);

        remaining.value = { ...remaining.value, reports: Math.max(0, remaining.value.reports - 1) };
        resetPdfTurnstile();
    } catch {
        errorMsg.value = t('demo.error_network');
        resetPdfTurnstile();
    } finally {
        downloading.value = false;
    }
}

const summaryRows = computed(() =>
    summary.value ? Object.values(summary.value).filter((r) => r.entry_count > 0) : []
);
</script>

<template>
    <Head title="Live demo" />

    <!-- Intro -->
    <div class="mb-4">
        <h2 class="text-2xl font-bold text-slate-900">{{ $t('demo.title') }}</h2>
        <p class="text-sm text-slate-500 mt-1">{{ $t('demo.subtitle') }}</p>
    </div>

    <!-- Quota banner -->
    <div
        class="mb-4 rounded-xl px-4 py-3 flex items-center justify-between gap-3 text-sm font-medium"
        :style="scansExhausted
            ? 'background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;'
            : 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;'"
    >
        <span>{{ $t('demo.scans_left', { n: scansLeft }) }}</span>
        <span>{{ $t('demo.reports_left', { n: reportsLeft }) }}</span>
    </div>

    <p v-if="errorMsg" class="mb-4 text-sm font-medium" style="color:#dc2626;">{{ errorMsg }}</p>

    <!-- STEP: scan -->
    <div v-if="step === 'scan'" class="space-y-4">
        <!-- Exhausted CTA — full benefits panel once scans are used up -->
        <WhyRegister v-if="scansExhausted" heading-key="demo.exhausted_title" />

        <!-- Uploader -->
        <div v-else class="bg-white rounded-2xl border border-slate-100 p-5" style="box-shadow:0 2px 12px rgba(0,0,0,0.05);">
            <label
                class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed p-8 text-center"
                :style="previewUrl ? 'border-color:#34d399;background:#f0fdf4;' : 'border-color:#d1fae5;background:#f9fafb;'"
            >
                <input ref="fileInput" type="file" accept="image/*" capture="environment" class="sr-only" @change="onFileSelect" />
                <template v-if="!previewUrl">
                    <div class="size-14 rounded-2xl flex items-center justify-center" style="background:#ecfdf5;">
                        <Camera style="width:28px;height:28px;color:#059669;" />
                    </div>
                    <div>
                        <p class="font-semibold text-slate-700">{{ $t('demo.upload_label') }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $t('demo.upload_hint') }}</p>
                    </div>
                </template>
                <template v-else>
                    <div class="relative w-full">
                        <img :src="previewUrl" alt="Preview" class="max-h-64 w-full rounded-xl object-contain" />
                        <div v-if="analysing" class="absolute inset-0 rounded-xl flex flex-col items-center justify-center gap-3"
                            style="background:rgba(5,150,105,0.88);">
                            <Spinner class="text-white" style="width:32px;height:32px;" />
                            <p class="text-white font-semibold text-sm">{{ $t('demo.analysing') }}</p>
                        </div>
                    </div>
                    <p v-if="!analysing" class="text-xs text-slate-400">{{ $t('demo.change_photo') }}</p>
                </template>
            </label>

            <!-- Cloudflare widget — placed directly above the button so the
                 user can see when verification has finished loading -->
            <div v-if="turnstileSiteKey" class="mt-4 flex justify-center">
                <div ref="scanTurnstileEl" />
            </div>

            <button
                class="mt-4 w-full h-12 rounded-xl font-semibold text-base text-white disabled:opacity-50 flex items-center justify-center gap-2"
                style="background: linear-gradient(135deg, #059669, #047857);"
                :disabled="!previewUrl || analysing || scanTurnstileBusy"
                @click="analyse"
            >
                <Spinner v-if="analysing || (previewUrl && scanTurnstileBusy)" class="text-white" />
                {{ analysing
                    ? $t('demo.analysing')
                    : (previewUrl && scanTurnstileBusy ? $t('demo.verify_loading') : $t('demo.analyse')) }}
            </button>
        </div>

        <!-- Entries list -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5" style="box-shadow:0 2px 12px rgba(0,0,0,0.05);">
            <h3 class="font-bold text-slate-900 mb-3">{{ $t('demo.your_entries') }}</h3>
            <p v-if="!entries.length" class="text-sm text-slate-400">{{ $t('demo.no_entries') }}</p>
            <ul v-else class="space-y-2">
                <li v-for="(e, i) in entries" :key="i" class="flex items-center justify-between gap-3 rounded-xl px-3 py-2" style="background:#f8fafc;">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ e.item_name }}</p>
                        <p class="text-xs text-slate-400">
                            {{ t('log_waste.categories.' + e.category) }} · {{ Number(e.weight_kg).toFixed(2) }} kg ·
                            {{ t('log_waste.reasons.' + e.reason) }}
                        </p>
                    </div>
                    <button type="button" class="shrink-0 text-slate-400 hover:text-red-500" @click="removeEntry(i)">
                        <Trash2 style="width:16px;height:16px;" />
                    </button>
                </li>
            </ul>

            <button
                v-if="entries.length"
                class="mt-4 w-full h-12 rounded-xl font-semibold text-base text-white disabled:opacity-50 flex items-center justify-center gap-2"
                style="background: linear-gradient(135deg, #0284c7, #0ea5e9);"
                :disabled="generating"
                @click="generateReport"
            >
                <Spinner v-if="generating" class="text-white" />
                <FileBarChart2 v-else style="width:18px;height:18px;" />
                {{ $t('demo.generate_report') }}
            </button>
        </div>

        <!-- Conversion: once they've added at least one entry, explain why
             registering matters. Hidden when scans are exhausted because the
             full panel already replaces the uploader card above. -->
        <WhyRegister v-if="entries.length && !scansExhausted" />
    </div>

    <!-- STEP: review -->
    <div v-else-if="step === 'review'" class="bg-white rounded-2xl border border-slate-100 p-5" style="box-shadow:0 2px 12px rgba(0,0,0,0.05);">
        <h3 class="text-lg font-bold text-slate-900 mb-4">{{ $t('demo.review_title') }}</h3>

        <div v-if="previewUrl" class="mb-5">
            <img :src="previewUrl" alt="Scanned" class="max-h-40 w-full rounded-xl object-contain bg-slate-50" />
        </div>

        <form class="space-y-5" @submit.prevent="addEntry">
            <div class="space-y-2">
                <label style="font-size:13px;font-weight:700;color:#374151;">{{ $t('log_waste.category') }}</label>
                <CategoryPicker v-model="reviewForm.category" />
            </div>
            <div class="space-y-2">
                <label style="font-size:13px;font-weight:700;color:#374151;">{{ $t('log_waste.item_name') }}</label>
                <input v-model="reviewForm.item_name" type="text"
                    style="width:100%;height:48px;border-radius:12px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;font-size:15px;padding:0 14px;outline:none;box-sizing:border-box;" />
            </div>
            <div class="space-y-2">
                <label style="font-size:13px;font-weight:700;color:#374151;">{{ $t('log_waste.weight') }}</label>
                <WeightInput v-model="reviewForm.weight_kg" />
            </div>
            <div class="space-y-2">
                <label style="font-size:13px;font-weight:700;color:#374151;">{{ $t('log_waste.reason') }}</label>
                <Select v-model="reviewForm.reason">
                    <SelectTrigger class="h-12 w-full text-base border-slate-200 bg-slate-50 text-slate-900">
                        <SelectValue :placeholder="$t('log_waste.reason_placeholder')" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="opt in reasonOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="flex gap-3">
                <button type="button" class="flex-1 h-12 rounded-xl font-semibold text-sm border-2 flex items-center justify-center gap-2"
                    style="border-color:#d1fae5;color:#059669;background:#f0fdf4;" @click="resetScan(); step = 'scan';">
                    <RotateCcw style="width:16px;height:16px;" />
                    {{ $t('demo.retake') }}
                </button>
                <button type="submit" class="flex-1 h-12 rounded-xl font-semibold text-base text-white disabled:opacity-50"
                    style="background: linear-gradient(135deg, #059669, #047857);"
                    :disabled="!reviewForm.category || !reviewForm.item_name">
                    {{ $t('demo.add_entry') }}
                </button>
            </div>
        </form>
    </div>

    <!-- STEP: report -->
    <div v-else class="space-y-4">
        <div class="rounded-xl px-4 py-3 text-sm font-medium" style="background:#fffbeb;color:#b45309;border:1px solid #fde68a;">
            {{ $t('demo.sample_banner') }}
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 p-5" style="box-shadow:0 2px 12px rgba(0,0,0,0.05);">
            <h3 class="text-lg font-bold text-slate-900 mb-1">{{ $t('demo.report_title') }}</h3>
            <div class="grand-total flex items-center justify-between rounded-xl px-4 py-3 my-4" style="background:#0f172a;color:white;">
                <span class="text-sm opacity-75">{{ $t('demo.total_waste') }}</span>
                <span class="text-2xl font-bold">{{ reportTotal.toFixed(2) }} kg</span>
            </div>

            <h4 class="text-xs font-bold uppercase text-slate-400 tracking-wide mb-2">{{ $t('demo.report_summary') }}</h4>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-slate-400 border-b border-slate-100">
                        <th class="py-2">{{ $t('demo.col_category') }}</th>
                        <th class="py-2 text-right">{{ $t('demo.col_total') }}</th>
                        <th class="py-2 text-right">{{ $t('demo.col_entries') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in summaryRows" :key="row.category" class="border-b border-slate-50">
                        <td class="py-2 font-medium text-slate-700">{{ t('log_waste.categories.' + row.category) }}</td>
                        <td class="py-2 text-right text-slate-700">{{ row.total_kg.toFixed(2) }}</td>
                        <td class="py-2 text-right text-slate-500">{{ row.entry_count }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Cloudflare widget — placed directly above the download button -->
            <div v-if="turnstileSiteKey && reportsLeft > 0" class="mt-5 flex justify-center">
                <div ref="pdfTurnstileEl" />
            </div>

            <button
                class="mt-5 w-full h-12 rounded-xl font-semibold text-base text-white disabled:opacity-50 flex items-center justify-center gap-2"
                style="background: linear-gradient(135deg, #0284c7, #0ea5e9);"
                :disabled="downloading || reportsLeft <= 0 || pdfTurnstileBusy"
                @click="downloadPdf"
            >
                <Spinner v-if="downloading || pdfTurnstileBusy" class="text-white" />
                {{ downloading
                    ? $t('demo.downloading')
                    : (pdfTurnstileBusy ? $t('demo.verify_loading') : $t('demo.download_pdf')) }}
            </button>
            <p v-if="reportsLeft <= 0" class="mt-2 text-xs text-center" style="color:#dc2626;">
                {{ $t('demo.reports_exhausted_msg') }}
            </p>

            <div class="flex gap-3 mt-3">
                <button type="button" class="flex-1 h-11 rounded-xl font-semibold text-sm border-2"
                    style="border-color:#d1fae5;color:#059669;background:#f0fdf4;" @click="step = 'scan'">
                    {{ $t('demo.back_to_scan') }}
                </button>
                <button type="button" class="flex-1 h-11 rounded-xl font-semibold text-sm border-2"
                    style="border-color:#e2e8f0;color:#64748b;background:#f8fafc;" @click="startOver">
                    {{ $t('demo.start_over') }}
                </button>
            </div>
        </div>

        <!-- Convert — the visitor has just seen the watermarked report; this is the moment. -->
        <WhyRegister />
    </div>

</template>
