import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h, Fragment } from 'vue';
import { createI18n } from 'vue-i18n';
import DesktopNotice from '@/components/DesktopNotice.vue';
import { initializeTheme } from '@/composables/useAppearance';
import { messages as de } from '@/i18n/de';
import { messages as en } from '@/i18n/en';
import { messages as es } from '@/i18n/es';
import { messages as fr } from '@/i18n/fr';
import { messages as id } from '@/i18n/id';
import { messages as nl } from '@/i18n/nl';
import { messages as tr } from '@/i18n/tr';
import { messages as zhCN } from '@/i18n/zh-CN';
import { messages as zhTW } from '@/i18n/zh-TW';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import DemoLayout from '@/layouts/DemoLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import WasteLayout from '@/layouts/WasteLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// useLocale.ts already resolved the correct locale from the Inertia page data
// (= server cookie) and synced it to localStorage, so we just read from there.
const storedLocale = (typeof window !== 'undefined' && localStorage.getItem('locale')) || 'en';

const i18n = createI18n({
    legacy: false,
    locale: storedLocale,
    fallbackLocale: 'en',
    messages: { en, nl, de, fr, es, 'zh-TW': zhTW, 'zh-CN': zhCN, tr, id },
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            case name.startsWith('waste/'):
                return WasteLayout;
            case name.startsWith('demo/'):
                return DemoLayout;
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(Fragment, [h(App, props), h(DesktopNotice)]) })
            .use(plugin)
            .use(i18n)
            .mount(el!);
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
