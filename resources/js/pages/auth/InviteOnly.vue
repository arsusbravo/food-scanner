<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { login } from '@/routes';
import { Lock } from 'lucide-vue-next';

onMounted(() => {
    // The server rendered this page because there is no valid token.
    // If the URL already has a token param it means the server just rejected it —
    // clear localStorage and stop so we never loop.
    if (new URLSearchParams(window.location.search).get('token')) {
        localStorage.removeItem('invite_token');
        return;
    }
    // Try once with a locally saved token, then clear it regardless so a future
    // redirect can't loop back here with the same stale value.
    const token = localStorage.getItem('invite_token');
    localStorage.removeItem('invite_token');
    if (token) {
        window.location.href = '/register?token=' + encodeURIComponent(token);
    }
});
</script>

<template>
    <Head title="Invitation Only" />

    <div class="flex flex-col items-center gap-5 text-center">
        <div
            class="size-16 rounded-2xl flex items-center justify-center"
            style="background: linear-gradient(135deg, #fef3c7, #fde68a);"
        >
            <Lock style="width: 28px; height: 28px; color: #d97706;" />
        </div>

        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">
                Registration by invitation only
            </h2>
            <p style="font-size: 14px; color: #64748b; line-height: 1.6; max-width: 300px;">
                This application is currently only available to invited users.
                Contact us to request access.
            </p>
        </div>

        <a
            href="mailto:info@arsus.nl"
            style="font-size: 14px; font-weight: 600; color: #059669; text-decoration: none;"
        >
            info@arsus.nl
        </a>

        <Link
            :href="login()"
            style="font-size: 14px; font-weight: 600; color: #64748b; text-decoration: none;"
        >
            ← Back to log in
        </Link>
    </div>
</template>
