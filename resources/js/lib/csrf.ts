/**
 * CSRF utilities for raw `fetch` callers.
 *
 * The CSRF token rendered into the page's <meta name="csrf-token"> at load is
 * a snapshot. After long idle the Laravel session and its token rotate; the
 * cached meta value goes stale and POSTs come back as 419 ("Page Expired").
 *
 * Inertia/axios traffic doesn't hit this because Inertia reads the rotating
 * XSRF cookie on every request — but our hand-rolled `fetch` calls (AI scan,
 * demo scan/report, logout form) do.
 *
 * The fix here:
 *   1. Read the meta token first (fast path — usually still valid).
 *   2. On a 419 response, refresh via GET /csrf-token, update the meta tag,
 *      and retry the request once.
 */

function getMetaToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function setMetaToken(token: string): void {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) meta.setAttribute('content', token);
}

/** Hit the refresh endpoint and update the meta tag. Returns the new token. */
export async function refreshCsrfToken(): Promise<string> {
    try {
        const res = await fetch('/csrf-token', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });
        if (!res.ok) return getMetaToken();
        const { token } = (await res.json()) as { token: string };
        setMetaToken(token);
        return token;
    } catch {
        return getMetaToken();
    }
}

/**
 * `fetch` with `X-CSRF-TOKEN` populated from the meta tag. If the server replies
 * 419, refresh the token and retry the request once before returning.
 */
export async function csrfFetch(input: RequestInfo | URL, init: RequestInit = {}): Promise<Response> {
    const send = (token: string) =>
        fetch(input, {
            ...init,
            credentials: init.credentials ?? 'same-origin',
            headers: {
                ...(init.headers || {}),
                'X-CSRF-TOKEN': token,
            },
        });

    let res = await send(getMetaToken());
    if (res.status === 419) {
        const fresh = await refreshCsrfToken();
        if (fresh) res = await send(fresh);
    }
    return res;
}
