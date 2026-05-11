import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

export type SupportedLocale = 'en' | 'nl' | 'de' | 'fr' | 'es' | 'zh-TW' | 'zh-CN' | 'tr';

const SUPPORTED: SupportedLocale[] = ['en', 'nl', 'de', 'fr', 'es', 'zh-TW', 'zh-CN', 'tr'];

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') return;
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

// The cookie (read server-side) is the single source of truth so that switching
// language on the public Blade pages is immediately reflected here and vice-versa.
// We read it from the Inertia page data that is embedded in the DOM before this
// module even runs, sync localStorage so app.ts sees the same value, then fall back.
const getInitialLocale = (): SupportedLocale => {
    if (typeof document === 'undefined') return 'en';
    try {
        const pageEl = document.getElementById('app') as HTMLElement | null;
        const server = JSON.parse(pageEl?.dataset?.page ?? '{}')?.props?.locale as string | undefined;
        if (server && SUPPORTED.includes(server as SupportedLocale)) {
            localStorage.setItem('locale', server);
            return server as SupportedLocale;
        }
    } catch { /* non-Inertia page or parse error — fall through */ }
    const stored = localStorage.getItem('locale') as SupportedLocale | null;
    return stored && SUPPORTED.includes(stored) ? stored : 'en';
};

const locale = ref<SupportedLocale>(getInitialLocale());

export function useLocale() {
    const i18n = useI18n();

    function setLocale(code: SupportedLocale) {
        locale.value = code;
        i18n.locale.value = code;
        localStorage.setItem('locale', code);
        setCookie('locale', code);
    }

    return {
        locale,
        setLocale,
        supported: SUPPORTED,
    };
}
