<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { UtensilsCrossed, CheckCircle2 } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { useLocale, type SupportedLocale } from '@/composables/useLocale';
import { login, register } from '@/routes';

const { t } = useI18n();
const { locale, setLocale, supported } = useLocale();

const sections = [
    {
        label: '01',
        titleKey: 'docs.nav_title',
        bodyKey: null,
        images: [1],
        extra: true,
    },
    {
        label: '02',
        titleKey: 'docs.home_title',
        bodyKey: 'docs.home_body',
        images: [2],
        extra: false,
    },
    {
        label: '03',
        titleKey: 'docs.log_title',
        bodyKey: 'docs.log_body',
        images: [3, 4],
        extra: false,
    },
    {
        label: '04',
        titleKey: 'docs.scan_title',
        bodyKey: 'docs.scan_body',
        images: [5, 6, 7, 8],
        extra: false,
    },
    {
        label: '05',
        titleKey: 'docs.entries_title',
        bodyKey: 'docs.entries_body',
        images: [9],
        extra: false,
    },
    {
        label: '06',
        titleKey: 'docs.report_title',
        bodyKey: 'docs.report_body',
        images: [10, 11, 12],
        extra: false,
    },
    {
        label: '07',
        titleKey: 'docs.insights_title',
        bodyKey: 'docs.insights_body',
        images: [13, 14],
        extra: false,
    },
    {
        label: '08',
        titleKey: 'docs.settings_title',
        bodyKey: 'docs.settings_body',
        images: [15, 16, 17, 18, 19],
        extra: false,
    },
];

const navItems = [
    { key: 'docs.nav_home' },
    { key: 'docs.nav_log' },
    { key: 'docs.nav_scan' },
    { key: 'docs.nav_report' },
    { key: 'docs.nav_insights' },
];
</script>

<template>
    <Head :title="`KitchenLog — ${t('docs.hero_title')}`" />

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

                <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; background: rgba(255,255,255,0.15); border-radius: 20px; margin-bottom: 20px; border: 1.5px solid rgba(255,255,255,0.25);">
                    <UtensilsCrossed style="width: 30px; height: 30px; color: white;" />
                </div>
                <p style="color: rgba(167,243,208,0.85); font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 8px;">
                    KitchenLog · User Guide
                </p>
                <h1 style="color: white; font-size: 34px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 12px; line-height: 1.15;">
                    {{ t('docs.hero_title') }}
                </h1>
                <p style="color: rgba(209,250,229,0.9); font-size: 15px; line-height: 1.6; margin: 0 0 28px;">
                    {{ t('docs.hero_sub') }}
                </p>
                <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                    <span style="background: rgba(255,255,255,0.15); color: rgba(209,250,229,0.95); font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.2);">EU Directive 2018/851</span>
                    <span style="background: rgba(255,255,255,0.15); color: rgba(209,250,229,0.95); font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.2);">FLW Protocol</span>
                    <span style="background: rgba(255,255,255,0.15); color: rgba(209,250,229,0.95); font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.2);">HORECA</span>
                </div>
            </div>
        </div>

        <!-- CTA buttons -->
        <div style="max-width: 480px; margin: -28px auto 0; padding: 0 24px; position: relative; z-index: 10;">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <Link
                    :href="register().url"
                    style="display: flex; align-items: center; justify-content: center; height: 54px; border-radius: 16px; background: linear-gradient(135deg, #059669, #047857); color: white; font-size: 16px; font-weight: 700; text-decoration: none; box-shadow: 0 8px 24px rgba(5,150,105,0.4);"
                >
                    {{ t('docs.cta_register') }}
                </Link>
                <Link
                    :href="login().url"
                    style="display: flex; align-items: center; justify-content: center; height: 54px; border-radius: 16px; background: white; color: #059669; font-size: 16px; font-weight: 700; text-decoration: none; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 2px solid #d1fae5;"
                >
                    {{ t('docs.cta_login') }}
                </Link>
            </div>
        </div>

        <!-- Why KitchenLog -->
        <div style="max-width: 480px; margin: 40px auto 0; padding: 0 24px;">
            <h2 style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 12px;">
                {{ t('docs.why_title') }}
            </h2>
            <div style="background: white; border-radius: 18px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 12px;">
                <div v-for="key in ['docs.why_1', 'docs.why_2', 'docs.why_3']" :key="key" style="display: flex; gap: 12px; align-items: flex-start;">
                    <CheckCircle2 style="width: 18px; height: 18px; flex-shrink: 0; color: #059669; margin-top: 1px;" />
                    <p style="font-size: 14px; color: #374151; line-height: 1.5; margin: 0;">{{ t(key) }}</p>
                </div>
            </div>
        </div>

        <!-- Sections -->
        <div style="max-width: 480px; margin: 0 auto; padding: 0 24px;">
            <div v-for="section in sections" :key="section.label" style="margin-top: 40px;">

                <!-- Section header -->
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <span style="font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: 0.08em;">{{ section.label }}</span>
                    <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.01em;">{{ t(section.titleKey) }}</h2>
                </div>

                <!-- Nav section special content -->
                <div v-if="section.extra" style="background: white; border-radius: 18px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 16px;">
                    <p style="font-size: 14px; color: #64748b; margin: 0 0 14px; line-height: 1.5;">{{ t('docs.nav_intro') }}</p>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div v-for="item in navItems" :key="item.key" style="display: flex; gap: 10px; align-items: flex-start;">
                            <div style="width: 6px; height: 6px; border-radius: 50%; background: #059669; flex-shrink: 0; margin-top: 7px;"></div>
                            <p style="font-size: 14px; color: #374151; line-height: 1.5; margin: 0;">{{ t(item.key) }}</p>
                        </div>
                    </div>
                    <p style="font-size: 13px; color: #64748b; margin: 14px 0 0; line-height: 1.5; font-style: italic;">{{ t('docs.nav_flow') }}</p>
                </div>

                <!-- Regular body text -->
                <div v-else style="background: white; border-radius: 18px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 16px;">
                    <p style="font-size: 14px; color: #374151; line-height: 1.65; margin: 0;">{{ t(section.bodyKey!) }}</p>
                </div>

                <!-- Screenshots -->
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <img
                        v-for="n in section.images"
                        :key="n"
                        :src="`/images/doc/${n}.png`"
                        :alt="`Screenshot ${n}`"
                        style="width: 100%; display: block; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.10); border: 1px solid #e2e8f0;"
                    />
                </div>
            </div>
        </div>

        <!-- Footer CTA -->
        <div style="max-width: 480px; margin: 48px auto 0; padding: 0 24px 64px;">
            <div style="background: linear-gradient(160deg, #064e3b 0%, #059669 100%); border-radius: 24px; padding: 32px 24px; text-align: center;">
                <h3 style="color: white; font-size: 22px; font-weight: 800; margin: 0 0 8px; letter-spacing: -0.01em;">Ready to reduce waste?</h3>
                <p style="color: rgba(209,250,229,0.9); font-size: 14px; margin: 0 0 24px; line-height: 1.5;">Start tracking in minutes. Free plan included.</p>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <Link
                        :href="register().url"
                        style="display: flex; align-items: center; justify-content: center; height: 50px; border-radius: 14px; background: white; color: #059669; font-size: 15px; font-weight: 700; text-decoration: none;"
                    >
                        {{ t('docs.cta_register') }}
                    </Link>
                    <Link
                        :href="login().url"
                        style="display: flex; align-items: center; justify-content: center; height: 50px; border-radius: 14px; background: rgba(255,255,255,0.15); color: white; font-size: 15px; font-weight: 700; text-decoration: none; border: 1.5px solid rgba(255,255,255,0.3);"
                    >
                        {{ t('docs.cta_login') }}
                    </Link>
                </div>
            </div>
            <p style="font-size: 12px; color: #94a3b8; text-align: center; margin-top: 24px;">
                KitchenLog · EU Directive 2018/851 · FLW Protocol
            </p>
        </div>

    </div>
</template>
