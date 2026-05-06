import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createI18n } from 'vue-i18n';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import WasteLayout from '@/layouts/WasteLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { messages as en } from '@/i18n/en';
import { messages as nl } from '@/i18n/nl';
import { messages as de } from '@/i18n/de';
import { messages as fr } from '@/i18n/fr';
import { messages as es } from '@/i18n/es';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const storedLocale = (typeof window !== 'undefined' && localStorage.getItem('locale')) || 'en';

const i18n = createI18n({
    legacy: false,
    locale: storedLocale,
    fallbackLocale: 'en',
    messages: { en, nl, de, fr, es },
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
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .mount(el!);
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
