<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { CheckCircle2, CreditCard, Zap, FileBarChart2, ShieldCheck } from 'lucide-vue-next';
import { Spinner } from '@/components/ui/spinner';
import { checkout, portal } from '@/routes/waste/subscription';
import { toast } from 'vue-sonner';

type Props = {
    plan: 'free' | 'pro';
    interval: 'monthly' | 'annual' | null;
    scans_used: number;
    scan_quota: number | null;
    exports_used: number;
    export_quota: number | null;
    next_billing_date: string | null;
    on_grace_period: boolean;
    flash: 'activated' | null;
};

const props = defineProps<Props>();
const { t } = useI18n();

onMounted(() => {
    if (props.flash === 'activated') {
        toast.success(t('subscription.success_title'));
    }
});

const scanPct = computed(() => {
    if (!props.scan_quota) return 0;
    return Math.min(100, Math.round((props.scans_used / props.scan_quota) * 100));
});

const monthlyForm = useForm({ interval: 'monthly' });
const annualForm  = useForm({ interval: 'annual' });

function upgradeMonthly() {
    monthlyForm.post(checkout().url);
}
function upgradeAnnual() {
    annualForm.post(checkout().url);
}
function goToPortal() {
    window.location.href = portal().url;
}
</script>

<template>
    <Head :title="$t('subscription.title')" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-8 space-y-5">

        <!-- Current plan card -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-bold text-slate-900">{{ $t('subscription.title') }}</h1>
                <span
                    class="text-xs font-bold px-3 py-1 rounded-full"
                    :style="plan === 'pro'
                        ? 'background: linear-gradient(135deg,#0284c7,#0ea5e9); color: white;'
                        : 'background: #f1f5f9; color: #64748b;'"
                >
                    {{ plan === 'pro' ? $t('subscription.pro') : $t('subscription.free') }}
                </span>
            </div>

            <!-- Scan usage bar -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-1.5">
                    <span style="font-size: 12px; font-weight: 700; color: #374151;">{{ $t('subscription.scans_used') }}</span>
                    <span style="font-size: 12px; color: #64748b;">
                        {{ scans_used }} / {{ scan_quota ?? '∞' }}
                    </span>
                </div>
                <div style="height: 6px; background: #f1f5f9; border-radius: 999px; overflow: hidden;">
                    <div
                        style="height: 100%; border-radius: 999px; transition: width 0.4s ease;"
                        :style="{
                            width: scan_quota ? `${scanPct}%` : '30%',
                            background: scanPct >= 90 ? '#ef4444' : scanPct >= 70 ? '#f59e0b' : '#059669',
                        }"
                    />
                </div>
            </div>

            <!-- Grace period warning -->
            <div
                v-if="on_grace_period && next_billing_date"
                class="rounded-xl px-4 py-3 mb-4 text-sm font-medium"
                style="background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa;"
            >
                {{ $t('subscription.grace_period') }} {{ next_billing_date }}
            </div>

            <!-- Pro billing info -->
            <div v-if="plan === 'pro' && !on_grace_period && next_billing_date" class="mb-4 text-sm text-slate-500">
                {{ $t('subscription.next_billing') }}: <strong class="text-slate-700">{{ next_billing_date }}</strong>
            </div>

            <!-- Pro action -->
            <div v-if="plan === 'pro'">
                <button
                    type="button"
                    class="w-full h-11 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 transition-colors"
                    style="background: #f8fafc; border: 1.5px solid #e2e8f0; color: #374151;"
                    @click="goToPortal"
                >
                    <CreditCard style="width: 16px; height: 16px;" />
                    {{ $t('subscription.manage') }}
                </button>
                <p class="text-center mt-2" style="font-size: 11px; color: #94a3b8;">{{ $t('subscription.cancel_info') }}</p>
            </div>
        </div>

        <!-- Upgrade cards — only shown on free plan -->
        <template v-if="plan === 'free'">
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest">{{ $t('subscription.upgrade') }}</h2>

            <div class="grid grid-cols-2 gap-3">

                <!-- Monthly -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col" style="box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <p style="font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 2px;">{{ $t('subscription.monthly') }}</p>
                    <p style="font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.1;">€39</p>
                    <p style="font-size: 11px; color: #94a3b8; margin-bottom: 12px;">{{ $t('subscription.per_month') }} · {{ $t('subscription.excl_vat') }}</p>

                    <ul class="space-y-1.5 flex-1 mb-4">
                        <li class="flex items-start gap-1.5">
                            <CheckCircle2 style="width: 13px; height: 13px; color: #059669; margin-top: 2px; flex-shrink: 0;" />
                            <span style="font-size: 11px; color: #374151;">{{ $t('subscription.feature_scans') }}</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <CheckCircle2 style="width: 13px; height: 13px; color: #059669; margin-top: 2px; flex-shrink: 0;" />
                            <span style="font-size: 11px; color: #374151;">{{ $t('subscription.feature_exports') }}</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <CheckCircle2 style="width: 13px; height: 13px; color: #059669; margin-top: 2px; flex-shrink: 0;" />
                            <span style="font-size: 11px; color: #374151;">{{ $t('subscription.feature_eu') }}</span>
                        </li>
                    </ul>

                    <button
                        type="button"
                        class="w-full h-10 rounded-xl font-semibold text-sm text-white flex items-center justify-center gap-1.5 disabled:opacity-60"
                        style="background: linear-gradient(135deg, #059669, #047857);"
                        :disabled="monthlyForm.processing"
                        @click="upgradeMonthly"
                    >
                        <Spinner v-if="monthlyForm.processing" class="text-white" />
                        <Zap v-else style="width: 13px; height: 13px;" />
                        {{ $t('subscription.upgrade_cta') }}
                    </button>
                </div>

                <!-- Annual -->
                <div
                    class="rounded-2xl p-4 flex flex-col relative"
                    style="background: linear-gradient(160deg, #f0fdf4, #ecfdf5); border: 2px solid #6ee7b7; box-shadow: 0 2px 12px rgba(5,150,105,0.1);"
                >
                    <!-- Save badge -->
                    <span
                        class="absolute top-3 right-3 text-xs font-bold px-2 py-0.5 rounded-full"
                        style="background: #059669; color: white; font-size: 9px;"
                    >{{ $t('subscription.save_label') }}</span>

                    <p style="font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 2px;">{{ $t('subscription.annual') }}</p>
                    <p style="font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.1;">€390</p>
                    <p style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 2px;">= €32.50 {{ $t('subscription.per_month') }}</p>
                    <p style="font-size: 11px; color: #94a3b8; margin-bottom: 12px;">{{ $t('subscription.per_year') }} · {{ $t('subscription.excl_vat') }}</p>

                    <ul class="space-y-1.5 flex-1 mb-4">
                        <li class="flex items-start gap-1.5">
                            <CheckCircle2 style="width: 13px; height: 13px; color: #059669; margin-top: 2px; flex-shrink: 0;" />
                            <span style="font-size: 11px; color: #374151;">{{ $t('subscription.feature_scans') }}</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <CheckCircle2 style="width: 13px; height: 13px; color: #059669; margin-top: 2px; flex-shrink: 0;" />
                            <span style="font-size: 11px; color: #374151;">{{ $t('subscription.feature_exports') }}</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <CheckCircle2 style="width: 13px; height: 13px; color: #059669; margin-top: 2px; flex-shrink: 0;" />
                            <span style="font-size: 11px; color: #374151;">{{ $t('subscription.feature_eu') }}</span>
                        </li>
                    </ul>

                    <button
                        type="button"
                        class="w-full h-10 rounded-xl font-semibold text-sm text-white flex items-center justify-center gap-1.5 disabled:opacity-60"
                        style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 12px rgba(5,150,105,0.3);"
                        :disabled="annualForm.processing"
                        @click="upgradeAnnual"
                    >
                        <Spinner v-if="annualForm.processing" class="text-white" />
                        <Zap v-else style="width: 13px; height: 13px;" />
                        {{ $t('subscription.upgrade_cta') }}
                    </button>
                </div>
            </div>

            <!-- Trust line -->
            <div class="flex items-center justify-center gap-2" style="color: #94a3b8; font-size: 12px;">
                <ShieldCheck style="width: 13px; height: 13px;" />
                Secure payment via Stripe · Cancel any time
            </div>
        </template>

    </div>
</template>
