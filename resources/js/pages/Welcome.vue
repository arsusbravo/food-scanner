<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { UtensilsCrossed, ScanLine, FileBarChart2, CheckCircle2, CircleHelp } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { useLocale, type SupportedLocale } from '@/composables/useLocale';
import { login, register, docs } from '@/routes';

const { t } = useI18n();
const { locale, setLocale, supported } = useLocale();

withDefaults(
    defineProps<{ canRegister: boolean }>(),
    { canRegister: true },
);

const features = [
    { icon: UtensilsCrossed, titleKey: 'welcome.feature_log_title', descKey: 'welcome.feature_log_desc', color: '#059669', bg: '#ecfdf5' },
    { icon: ScanLine, titleKey: 'welcome.feature_scan_title', descKey: 'welcome.feature_scan_desc', color: '#0d9488', bg: '#f0fdfa' },
    { icon: FileBarChart2, titleKey: 'welcome.feature_report_title', descKey: 'welcome.feature_report_desc', color: '#0284c7', bg: '#f0f9ff' },
];

const highlightKeys = [
    'welcome.highlight_1',
    'welcome.highlight_2',
    'welcome.highlight_3',
    'welcome.highlight_4',
    'welcome.highlight_5',
    'welcome.highlight_6',
];
</script>

<template>
    <Head title="KitchenLog — EU Food Waste Tracker" />

    <div style="min-height: 100dvh; background: #f8fafc; font-family: Inter, system-ui, sans-serif;">

        <!-- Hero -->
        <div style="background: linear-gradient(160deg, #064e3b 0%, #059669 55%, #0d9488 100%); padding: 56px 24px 64px;">
            <div style="max-width: 480px; margin: 0 auto; text-align: center;">

                <!-- Language switcher -->
                <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
                    <div style="display: inline-flex; gap: 2px; background: rgba(0,0,0,0.2); border-radius: 999px; padding: 3px;">
                        <button
                            v-for="code in supported"
                            :key="code"
                            @click="setLocale(code as SupportedLocale)"
                            :style="locale === code
                                ? 'background: white; color: #059669; font-weight: 700;'
                                : 'background: transparent; color: rgba(209,250,229,0.85); font-weight: 600;'"
                            style="border: none; cursor: pointer; font-size: 11px; padding: 4px 9px; border-radius: 999px; font-family: inherit; letter-spacing: 0.04em;"
                        >{{ code.toUpperCase() }}</button>
                    </div>
                </div>

                <!-- Logo mark -->
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; background: rgba(255,255,255,0.15); border-radius: 20px; margin-bottom: 20px; border: 1.5px solid rgba(255,255,255,0.25);">
                    <UtensilsCrossed style="width: 30px; height: 30px; color: white;" />
                </div>

                <p style="color: rgba(167,243,208,0.85); font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 8px;">
                    {{ t('welcome.tagline') }}
                </p>
                <h1 style="color: white; font-size: 40px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 12px;">
                    KitchenLog
                </h1>
                <p style="color: rgba(209,250,229,0.9); font-size: 17px; line-height: 1.5; margin: 0 0 28px;">
                    {{ t('welcome.subtitle') }}
                </p>

                <!-- Badges -->
                <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                    <span style="background: rgba(255,255,255,0.15); color: rgba(209,250,229,0.95); font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.2);">
                        EU Directive 2018/851
                    </span>
                    <span style="background: rgba(255,255,255,0.15); color: rgba(209,250,229,0.95); font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.2);">
                        FLW Protocol
                    </span>
                    <span style="background: rgba(255,255,255,0.15); color: rgba(209,250,229,0.95); font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.2);">
                        HORECA
                    </span>
                </div>
            </div>
        </div>

        <!-- CTA buttons — overlapping hero/content split -->
        <div style="max-width: 480px; margin: -28px auto 0; padding: 0 24px; position: relative; z-index: 10;">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <Link
                    v-if="canRegister"
                    :href="register().url"
                    style="display: flex; align-items: center; justify-content: center; height: 54px; border-radius: 16px; background: linear-gradient(135deg, #059669, #047857); color: white; font-size: 16px; font-weight: 700; text-decoration: none; box-shadow: 0 8px 24px rgba(5,150,105,0.4);"
                >
                    {{ t('welcome.cta_register') }}
                </Link>
                <Link
                    :href="login().url"
                    style="display: flex; align-items: center; justify-content: center; height: 54px; border-radius: 16px; background: white; color: #059669; font-size: 16px; font-weight: 700; text-decoration: none; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 2px solid #d1fae5;"
                >
                    {{ t('welcome.cta_login') }}
                </Link>
            </div>
        </div>

        <!-- Features -->
        <div style="max-width: 480px; margin: 0 auto; padding: 40px 24px 0;">
            <h2 style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 16px;">
                {{ t('welcome.features_title') }}
            </h2>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div
                    v-for="f in features"
                    :key="f.titleKey"
                    style="background: white; border-radius: 18px; padding: 20px; display: flex; gap: 16px; align-items: flex-start; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;"
                >
                    <div
                        style="width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"
                        :style="`background: ${f.bg};`"
                    >
                        <component :is="f.icon" style="width: 22px; height: 22px;" :style="`color: ${f.color};`" />
                    </div>
                    <div>
                        <p style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 4px;">{{ t(f.titleKey) }}</p>
                        <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0;">{{ t(f.descKey) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Highlights checklist -->
        <div style="max-width: 480px; margin: 0 auto; padding: 32px 24px 0;">
            <div style="background: white; border-radius: 18px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <h2 style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 16px;">
                    {{ t('welcome.highlights_title') }}
                </h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div
                        v-for="key in highlightKeys"
                        :key="key"
                        style="display: flex; align-items: center; gap: 8px;"
                    >
                        <CheckCircle2 style="width: 15px; height: 15px; flex-shrink: 0; color: #059669;" />
                        <span style="font-size: 12px; font-weight: 600; color: #374151;">{{ t(key) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="max-width: 480px; margin: 0 auto; padding: 32px 24px 48px; text-align: center;">
            <Link
                :href="docs().url"
                style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; margin-bottom: 16px; text-decoration: none;"
            >
                <CircleHelp style="width: 20px; height: 20px; color: #059669;" />
            </Link>
            <p style="font-size: 12px; color: #94a3b8;">
                {{ t('welcome.footer') }}
            </p>
        </div>

    </div>
</template>
