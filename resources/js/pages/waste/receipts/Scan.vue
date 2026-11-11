<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { AlertCircle, ArrowLeft, Camera, CheckCircle2, Loader2, X } from 'lucide-vue-next';
import ReceiptEditor, { type EditorDraft } from '@/components/waste/ReceiptEditor.vue';
import { blobToBase64, compressImage } from '@/lib/image';
import { csrfFetch } from '@/lib/csrf';
import { formatMoney } from '@/lib/money';
import { destroy as destroyRoute, index as receiptsIndex, scan as scanRoute, update as updateRoute } from '@/routes/waste/receipts';

const props = defineProps<{
    quota: { used: number; limit: number | null };
    maxPages: number;
}>();

type Job = {
    label: string;
    files: File[];
    status: 'pending' | 'scanning' | 'done' | 'error';
    skipped: number;
    error?: string;
};

type Result = EditorDraft & {
    id: number;
    status: 'draft' | 'confirmed';
    confidence: 'high' | 'medium' | 'low';
    duplicate_id: number | null;
    errors: Record<string, string | undefined>;
    busy: boolean;
};

const { t } = useI18n();

const files = ref<File[]>([]);
const onePageSet = ref(false);
const jobs = ref<Job[]>([]);
const results = ref<Result[]>([]);
const categories = ref<string[]>([]);
const running = ref(false);
const finished = ref(false);

const quotaLeft = computed(() => (props.quota.limit === null ? null : Math.max(0, props.quota.limit - props.quota.used - results.value.length)));

const tooManyPages = computed(() => onePageSet.value && files.value.length > props.maxPages);

const drafts = computed(() => results.value.filter((result) => result.status === 'draft'));
const skippedTotal = computed(() => jobs.value.reduce((sum, job) => sum + job.skipped, 0));
const failedJobs = computed(() => jobs.value.filter((job) => job.status === 'error'));

function onFiles(e: Event) {
    const picked = Array.from((e.target as HTMLInputElement).files ?? []);

    files.value = [...files.value, ...picked];
    (e.target as HTMLInputElement).value = '';
}

function removeFile(index: number) {
    files.value = files.value.filter((_, i) => i !== index);
}

async function scanJob(job: Job): Promise<boolean> {
    job.status = 'scanning';

    try {
        const photos: string[] = [];

        for (const file of job.files) {
            photos.push(await blobToBase64(await compressImage(file)));
        }

        const res = await csrfFetch(scanRoute().url, {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ photos }),
        });
        const body = await res.json().catch(() => ({}));

        if (!res.ok) {
            job.status = 'error';
            job.error = body.error ?? Object.values((body.errors ?? {}) as Record<string, string[]>)[0]?.[0] ?? t('receipts.scan_failed');

            return res.status === 403;
        }

        job.status = 'done';
        job.skipped = body.skipped ?? 0;
        categories.value = body.categories ?? categories.value;
        results.value.push(...(body.receipts ?? []).map((receipt: Omit<Result, 'errors' | 'busy'>) => ({ ...receipt, errors: {}, busy: false })));
    } catch {
        job.status = 'error';
        job.error = t('receipts.scan_failed');
    }

    return false;
}

async function start() {
    if (!files.value.length || tooManyPages.value) {
        return;
    }

    const make = (label: string, set: File[]): Job => ({ label, files: set, status: 'pending', skipped: 0 });

    jobs.value = onePageSet.value
        ? [make(files.value.map((f) => f.name).join(', '), [...files.value])]
        : files.value.map((file) => make(file.name, [file]));

    running.value = true;
    finished.value = false;

    for (const job of jobs.value) {
        const quotaHit = await scanJob(job);

        if (quotaHit) {
            break;
        }
    }

    running.value = false;
    finished.value = true;
    files.value = [];
}

function retry(job: Job) {
    job.error = undefined;
    running.value = true;
    scanJob(job).finally(() => {
        running.value = false;
    });
}

function scanMore() {
    jobs.value = [];
    results.value = [];
    finished.value = false;
}

function payloadOf(result: Result) {
    const { supplier_name, supplier_vat_id, invoice_number, invoice_date, currency, net_total, vat_total, gross_total, notes, lines } = result;

    return { supplier_name, supplier_vat_id, invoice_number, invoice_date, currency, net_total, vat_total, gross_total, notes, lines, confirm: true };
}

async function confirmOne(result: Result): Promise<boolean> {
    result.busy = true;
    result.errors = {};

    try {
        const res = await csrfFetch(updateRoute(result.id).url, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(payloadOf(result)),
        });
        const body = await res.json().catch(() => ({}));

        if (!res.ok) {
            result.errors = Object.fromEntries(Object.entries((body.errors ?? {}) as Record<string, string[]>).map(([key, messages]) => [key, messages[0]]));

            if (Object.keys(result.errors).length === 0) {
                result.errors = { gross_total: t('receipts.scan_failed') };
            }

            return false;
        }

        result.status = 'confirmed';

        return true;
    } finally {
        result.busy = false;
    }
}

async function confirmAll() {
    for (const result of drafts.value) {
        await confirmOne(result);
    }
}

async function remove(result: Result) {
    if (!confirm(t('receipts.delete_confirm'))) {
        return;
    }

    result.busy = true;

    const res = await csrfFetch(destroyRoute(result.id).url, { method: 'DELETE', headers: { Accept: 'application/json' } });

    if (res.ok) {
        results.value = results.value.filter((item) => item.id !== result.id);
    } else {
        result.busy = false;
    }
}
</script>

<template>
    <Head :title="$t('receipts.scan_title')" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-8 space-y-5">
        <div>
            <Link :href="receiptsIndex().url" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-400 mb-2">
                <ArrowLeft style="width: 14px; height: 14px;" /> {{ $t('receipts.title') }}
            </Link>
            <h1 class="text-2xl font-bold text-slate-900">{{ results.length ? $t('receipts.check_title') : $t('receipts.scan_title') }}</h1>
            <p v-if="!results.length" class="text-xs text-slate-400 mt-1">{{ $t('receipts.scan_hint') }}</p>
        </div>

        <p v-if="quotaLeft !== null" class="text-xs font-semibold" :style="quotaLeft === 0 ? 'color:#dc2626;' : 'color:#64748b;'">
            {{ quotaLeft === 0 ? $t('receipts.quota_reached') : $t('receipts.quota_left', { n: quotaLeft }) }}
        </p>

        <!-- Picker -->
        <div v-if="!running && !finished" class="bg-white rounded-2xl border border-slate-100 p-5 space-y-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <label
                class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed p-8 text-center"
                style="border-color: #d1fae5; background: #f9fafb;"
            >
                <input type="file" accept="image/*" multiple class="sr-only" @change="onFiles" />
                <div class="size-14 rounded-2xl flex items-center justify-center" style="background: #ecfdf5;">
                    <Camera style="width: 28px; height: 28px; color: #059669;" />
                </div>
                <p class="font-semibold text-slate-700">{{ $t('receipts.pick_photos') }}</p>
            </label>

            <p class="text-xs text-slate-500">{{ $t('receipts.multi_hint') }}</p>

            <ul v-if="files.length" class="space-y-2">
                <li v-for="(file, i) in files" :key="i" class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    <span class="truncate">{{ file.name }}</span>
                    <button type="button" class="ml-2 text-slate-400" :aria-label="$t('receipts.remove')" @click="removeFile(i)">
                        <X style="width: 14px; height: 14px;" />
                    </button>
                </li>
            </ul>

            <label v-if="files.length > 1" class="flex items-start gap-2 text-sm text-slate-600">
                <input v-model="onePageSet" type="checkbox" class="mt-0.5" />
                <span>{{ $t('receipts.one_receipt') }}</span>
            </label>
            <p v-if="tooManyPages" class="text-xs font-semibold" style="color: #dc2626;">{{ $t('receipts.max_pages', { n: maxPages }) }}</p>

            <button
                type="button"
                class="w-full h-12 rounded-xl font-semibold text-sm text-white disabled:opacity-50"
                style="background: linear-gradient(135deg, #059669, #047857);"
                :disabled="!files.length || tooManyPages || quotaLeft === 0"
                @click="start"
            >
                {{ $t('receipts.scan_button', { n: onePageSet ? 1 : files.length }) }}
            </button>
        </div>

        <!-- Scanning progress (and failures) -->
        <div v-if="running || failedJobs.length" class="space-y-2">
            <div
                v-for="(job, i) in jobs.filter((j) => running || j.status === 'error')"
                :key="i"
                class="bg-white rounded-xl border border-slate-100 px-4 py-3 flex items-center gap-3"
                style="box-shadow: 0 1px 4px rgba(0,0,0,0.04);"
            >
                <Loader2 v-if="job.status === 'scanning'" class="animate-spin shrink-0" style="width: 18px; height: 18px; color: #059669;" />
                <CheckCircle2 v-else-if="job.status === 'done'" class="shrink-0" style="width: 18px; height: 18px; color: #059669;" />
                <AlertCircle v-else-if="job.status === 'error'" class="shrink-0" style="width: 18px; height: 18px; color: #dc2626;" />
                <span v-else class="shrink-0 rounded-full" style="width: 18px; height: 18px; border: 2px solid #e2e8f0;" />

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-800 truncate">{{ job.label }}</p>
                    <p v-if="job.status === 'scanning'" class="text-xs text-slate-400">{{ $t('receipts.scanning') }}</p>
                    <p v-else-if="job.status === 'error'" class="text-xs" style="color: #dc2626;">{{ job.error }}</p>
                </div>

                <button v-if="job.status === 'error' && !running" type="button" class="text-xs font-bold" style="color: #059669;" @click="retry(job)">
                    {{ $t('receipts.retry') }}
                </button>
            </div>
        </div>

        <p v-if="skippedTotal > 0" class="rounded-xl px-4 py-3 text-sm font-semibold" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">
            {{ $t('receipts.skipped_quota', { n: skippedTotal }) }}
        </p>

        <!-- Results: review and confirm right here -->
        <div v-for="result in results" :key="result.id">
            <div
                v-if="result.status === 'confirmed'"
                class="flex items-center gap-3 rounded-2xl border px-4 py-3"
                style="background: #ecfdf5; border-color: #a7f3d0;"
            >
                <CheckCircle2 class="shrink-0" style="width: 20px; height: 20px; color: #059669;" />
                <p class="min-w-0 flex-1 truncate text-sm font-semibold" style="color: #047857;">
                    {{ result.supplier_name || $t('receipts.unknown_supplier') }}
                    <template v-if="result.gross_total !== null && result.gross_total !== ''"> · {{ formatMoney(Number(result.gross_total), result.currency) }}</template>
                </p>
                <span class="text-xs font-bold" style="color: #059669;">{{ $t('receipts.saved') }}</span>
            </div>

            <div v-else class="space-y-4 rounded-3xl p-3" style="background: #f1f5f9;">
                <ReceiptEditor
                    :model-value="result"
                    :errors="result.errors"
                    :categories="categories"
                    :confidence="result.confidence"
                    :duplicate-id="result.duplicate_id"
                />

                <div class="grid grid-cols-3 gap-3">
                    <button
                        type="button"
                        class="col-span-2 h-12 rounded-xl font-semibold text-sm text-white disabled:opacity-60"
                        style="background: linear-gradient(135deg, #059669, #047857);"
                        :disabled="result.busy"
                        @click="confirmOne(result)"
                    >
                        {{ $t('receipts.confirm') }}
                    </button>
                    <button type="button" class="h-12 rounded-xl text-sm font-semibold disabled:opacity-60" style="background: white; color: #dc2626;" :disabled="result.busy" @click="remove(result)">
                        {{ $t('receipts.delete') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer actions -->
        <div v-if="finished && !running && results.length" class="space-y-3">
            <button
                v-if="drafts.length > 1"
                type="button"
                class="w-full h-12 rounded-xl font-semibold text-sm text-white"
                style="background: linear-gradient(135deg, #059669, #047857);"
                @click="confirmAll"
            >
                {{ $t('receipts.confirm_all', { n: drafts.length }) }}
            </button>

            <Link
                v-if="!drafts.length"
                :href="receiptsIndex().url"
                class="flex h-12 items-center justify-center rounded-xl text-sm font-semibold text-white"
                style="background: linear-gradient(135deg, #059669, #047857);"
            >
                {{ $t('receipts.done') }}
            </Link>

            <button type="button" class="w-full h-11 rounded-xl text-sm font-semibold" style="background: #ecfdf5; color: #047857;" @click="scanMore">
                {{ $t('receipts.scan_more') }}
            </button>
        </div>
    </div>
</template>
