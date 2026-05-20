<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { register, login } from '@/routes';

defineProps<{
    // Heading variant — defaults to the generic "Why create an account?"
    headingKey?: string;
}>();

const benefits = [
    'demo.benefit_save',
    'demo.benefit_official',
    'demo.benefit_more',
    'demo.benefit_archive',
    'demo.benefit_audit',
] as const;
</script>

<template>
    <div
        class="bg-white rounded-2xl border border-slate-100 p-6"
        style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);"
    >
        <h3 class="text-lg font-bold text-slate-900 mb-4">
            {{ $t(headingKey ?? 'demo.why_title') }}
        </h3>

        <ul class="space-y-3 mb-5">
            <li v-for="key in benefits" :key="key" class="flex items-start gap-3">
                <span
                    class="shrink-0 inline-flex items-center justify-center"
                    style="width: 22px; height: 22px; border-radius: 999px; background: #ecfdf5;"
                >
                    <Check style="width: 14px; height: 14px; color: #059669;" />
                </span>
                <span class="text-sm text-slate-700 leading-snug">{{ $t(key) }}</span>
            </li>
        </ul>

        <Link
            :href="register()"
            class="inline-flex items-center justify-center w-full h-12 rounded-xl font-bold text-white no-underline"
            style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 16px rgba(5,150,105,0.25);"
        >
            {{ $t('demo.cta_register') }}
        </Link>

        <div class="mt-3 text-center">
            <Link :href="login()" class="text-sm font-semibold no-underline" style="color: #059669;">
                {{ $t('demo.cta_login') }}
            </Link>
        </div>
    </div>
</template>
