import { onMounted, ref, watch } from 'vue';

const SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

type TurnstileApi = {
    render: (
        el: HTMLElement,
        opts: {
            sitekey: string;
            theme?: string;
            callback?: (token: string) => void;
            'expired-callback'?: () => void;
            'error-callback'?: () => void;
            'timeout-callback'?: () => void;
        },
    ) => string;
    reset: (el?: HTMLElement) => void;
    remove?: (el?: HTMLElement | string) => void;
    getResponse: (el?: HTMLElement) => string | undefined;
};

const getApi = (): TurnstileApi | undefined =>
    (window as unknown as { turnstile?: TurnstileApi }).turnstile;

/**
 * Renders a Cloudflare Turnstile widget into a container ref and keeps it
 * working across Inertia SPA navigations (the api.js auto-render only fires
 * once on load, so we render explicitly instead). The container may appear
 * after mount — e.g. login only shows the widget after repeated failures —
 * so rendering is re-attempted whenever the ref becomes available.
 *
 * The widget injects a hidden `cf-turnstile-response` input that Inertia's
 * <Form> submits automatically. Call `reset()` after a failed submit so the
 * single-use token is regenerated for the next attempt (e.g. wrong password).
 *
 * `ready` flips true when Turnstile has computed a token and is safe to
 * submit. Gate your submit buttons on it so a user can't click before
 * Cloudflare has finished loading and end up with an empty token (which the
 * server rejects as a CAPTCHA failure). It returns to false on reset, token
 * expiry, or a widget error.
 */
export function useTurnstile(siteKey: string | null | undefined) {
    const container = ref<HTMLElement | null>(null);
    // When Turnstile is disabled entirely (no site key — local dev), the
    // widget never renders, so callers should treat the gate as "ready". This
    // means existing call sites that don't read `ready` keep working.
    const ready = ref<boolean>(!siteKey);
    let rendered = false;

    function render() {
        const api = getApi();
        if (rendered || !siteKey || !container.value || !api) return;
        container.value.innerHTML = '';
        api.render(container.value, {
            sitekey: siteKey,
            theme: 'light',
            callback: () => {
                ready.value = true;
            },
            'expired-callback': () => {
                ready.value = false;
            },
            'error-callback': () => {
                ready.value = false;
            },
            'timeout-callback': () => {
                ready.value = false;
            },
        });
        rendered = true;
    }

    function reset() {
        ready.value = false;
        const api = getApi();
        if (api && container.value) api.reset(container.value);
    }

    // The current solved token, for manual (fetch/XHR) submissions. Forms can
    // ignore this — Turnstile injects a hidden cf-turnstile-response input.
    function getToken(): string {
        const api = getApi();
        if (!api || !container.value) return '';
        try {
            return api.getResponse(container.value) ?? '';
        } catch {
            return '';
        }
    }

    function ensureScriptThenRender() {
        if (!siteKey) return;

        if (getApi()) {
            render();
            return;
        }

        const existing = document.querySelector<HTMLScriptElement>(`script[src="${SCRIPT_SRC}"]`);
        if (existing) {
            existing.addEventListener('load', render, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = SCRIPT_SRC;
        script.async = true;
        script.defer = true;
        script.addEventListener('load', render, { once: true });
        document.head.appendChild(script);
    }

    onMounted(ensureScriptThenRender);

    // The widget can be mounted later (conditional v-if), or remounted as the
    // user navigates steps. Clean up the previous widget (so Cloudflare doesn't
    // keep stale handles) and re-render into the fresh container.
    watch(container, (el, oldEl) => {
        if (oldEl) {
            const api = getApi();
            try { api?.remove?.(oldEl); } catch { /* ignore */ }
            rendered = false;
            ready.value = !siteKey;
        }
        if (el) ensureScriptThenRender();
    });

    return { container, reset, getToken, ready };
}
