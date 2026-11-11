<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Receipt, Trash2 } from 'lucide-vue-next';

const { t } = useI18n();
const page = usePage<{ auth: { user: { name: string } } }>();
const firstName = computed(() => page.props.auth?.user?.name?.split(' ')[0] ?? 'there');

const greeting = computed(() => {
    const h = new Date().getHours();

    if (h < 12) {
        return t('home.greeting_morning');
    }

    if (h < 18) {
        return t('home.greeting_afternoon');
    }

    return t('home.greeting_evening');
});

const todayDate = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });
</script>

<template>
    <Head title="Home" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-8 space-y-5">

        <!-- Greeting -->
        <div>
            <p class="text-slate-400 text-sm">{{ todayDate }}</p>
            <h2 class="text-2xl font-bold text-slate-900 mt-0.5">{{ greeting }}, {{ firstName }}</h2>
        </div>

        <!-- Main areas -->
        <div class="space-y-4">
            <Link
                href="/waste/receipts"
                class="rounded-3xl p-6 flex items-center gap-4 transition-opacity active:opacity-80"
                style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 6px 20px rgba(5,150,105,0.3); min-height: 132px;"
            >
                <div class="size-14 rounded-2xl flex items-center justify-center shrink-0" style="background: rgba(255,255,255,0.2);">
                    <Receipt style="width: 28px; height: 28px; color: white;" />
                </div>
                <div>
                    <p class="text-xl font-bold text-white">{{ $t('home.receipts_title') }}</p>
                    <p class="text-sm mt-1" style="color: rgba(167,243,208,0.9);">{{ $t('home.receipts_desc') }}</p>
                </div>
            </Link>

            <Link
                href="/waste/entries"
                class="rounded-3xl p-6 flex items-center gap-4 transition-opacity active:opacity-80"
                style="background: linear-gradient(135deg, #0d9488, #0f766e); box-shadow: 0 6px 20px rgba(13,148,136,0.3); min-height: 132px;"
            >
                <div class="size-14 rounded-2xl flex items-center justify-center shrink-0" style="background: rgba(255,255,255,0.2);">
                    <Trash2 style="width: 28px; height: 28px; color: white;" />
                </div>
                <div>
                    <p class="text-xl font-bold text-white">{{ $t('home.waste_title') }}</p>
                    <p class="text-sm mt-1" style="color: rgba(153,246,228,0.9);">{{ $t('home.waste_desc') }}</p>
                </div>
            </Link>
        </div>
    </div>
</template>
