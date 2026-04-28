import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

export type SupportedLocale = 'en' | 'de' | 'fr' | 'es';

const SUPPORTED: SupportedLocale[] = ['en', 'de', 'fr', 'es'];

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') return;
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getStoredLocale = (): SupportedLocale => {
    if (typeof window === 'undefined') return 'en';
    const stored = localStorage.getItem('locale') as SupportedLocale | null;
    return stored && SUPPORTED.includes(stored) ? stored : 'en';
};

const locale = ref<SupportedLocale>(getStoredLocale());

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
