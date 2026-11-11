<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ArrowLeft } from 'lucide-vue-next';
import ReceiptEditor, { type EditorLine } from '@/components/waste/ReceiptEditor.vue';
import { destroy as destroyRoute, index as indexRoute, update as updateRoute } from '@/routes/waste/receipts';

const props = defineProps<{
    receipt: {
        id: number;
        status: 'draft' | 'confirmed';
        supplier_name: string | null;
        supplier_vat_id: string | null;
        invoice_number: string | null;
        invoice_date: string | null;
        currency: string;
        net_total: string | null;
        vat_total: string | null;
        gross_total: string | null;
        confidence: 'high' | 'medium' | 'low';
        notes: string | null;
        lines: EditorLine[];
        images: { id: number; page: number; url: string }[];
    };
    duplicateId: number | null;
    lineTotalMismatch: boolean;
    categories: string[];
}>();

const { t } = useI18n();

const form = useForm({
    supplier_name: props.receipt.supplier_name ?? '',
    supplier_vat_id: props.receipt.supplier_vat_id ?? '',
    invoice_number: props.receipt.invoice_number ?? '',
    invoice_date: props.receipt.invoice_date ?? '',
    currency: props.receipt.currency,
    net_total: props.receipt.net_total ?? '',
    vat_total: props.receipt.vat_total ?? '',
    gross_total: props.receipt.gross_total ?? '',
    notes: props.receipt.notes ?? '',
    lines: props.receipt.lines.map((line) => ({ ...line })) as EditorLine[],
    confirm: false,
});

function save(confirm: boolean) {
    form.transform((data) => ({ ...data, confirm })).patch(updateRoute(props.receipt.id).url, { preserveScroll: true });
}

function remove() {
    if (confirm(t('receipts.delete_confirm'))) {
        router.delete(destroyRoute(props.receipt.id).url);
    }
}
</script>

<template>
    <Head :title="$t('receipts.edit_title')" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-8 space-y-5">
        <div>
            <Link :href="indexRoute().url" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-400 mb-2">
                <ArrowLeft style="width: 14px; height: 14px;" /> {{ $t('receipts.title') }}
            </Link>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-slate-900">{{ $t('receipts.edit_title') }}</h1>
                <span
                    class="text-[11px] font-bold px-2.5 py-1 rounded-full"
                    :style="receipt.status === 'confirmed' ? 'background:#ecfdf5;color:#059669;' : 'background:#fefce8;color:#a16207;'"
                >{{ $t(`receipts.status_${receipt.status}`) }}</span>
            </div>
        </div>

        <!-- Photos -->
        <div v-if="receipt.images.length" class="flex gap-3 overflow-x-auto pb-1">
            <a v-for="image in receipt.images" :key="image.id" :href="image.url" target="_blank" rel="noopener" class="shrink-0">
                <img :src="image.url" :alt="$t('receipts.page', { n: image.page })" class="h-28 w-auto rounded-xl border border-slate-200 object-cover" />
            </a>
        </div>

        <ReceiptEditor v-model="form" :errors="form.errors" :categories="categories" :confidence="receipt.confidence" :duplicate-id="duplicateId" />

        <!-- Actions -->
        <div class="space-y-3">
            <button
                type="button"
                class="w-full h-12 rounded-xl font-semibold text-sm text-white disabled:opacity-60"
                style="background: linear-gradient(135deg, #059669, #047857);"
                :disabled="form.processing"
                @click="save(true)"
            >
                {{ receipt.status === 'confirmed' ? $t('receipts.save') : $t('receipts.confirm') }}
            </button>
            <button
                v-if="receipt.status === 'draft'"
                type="button"
                class="w-full h-11 rounded-xl font-semibold text-sm disabled:opacity-60"
                style="background: #ecfdf5; color: #047857;"
                :disabled="form.processing"
                @click="save(false)"
            >
                {{ $t('receipts.save_draft') }}
            </button>
            <button type="button" class="w-full h-10 text-sm font-semibold" style="color: #dc2626;" @click="remove">{{ $t('receipts.delete') }}</button>
        </div>
    </div>
</template>
