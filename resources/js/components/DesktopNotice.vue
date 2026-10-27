<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Smartphone, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const STORAGE_KEY = 'desktop-notice-dismissed';
const DESKTOP_QUERY = '(min-width: 768px) and (hover: hover) and (pointer: fine)';

const page = usePage();
const isDesktop = ref(false);
const isDismissed = ref(false);

// The admin area is intentionally desktop-oriented.
const isAdminPage = computed(() => page.url.startsWith('/admin'));
const isVisible = computed(() => isDesktop.value && !isDismissed.value && !isAdminPage.value);

let mediaQuery: MediaQueryList | null = null;

function syncDesktop(): void {
    isDesktop.value = mediaQuery?.matches ?? false;
}

function dismiss(): void {
    isDismissed.value = true;

    try {
        localStorage.setItem(STORAGE_KEY, '1');
    } catch {
        // Storage unavailable — notice simply reappears next visit.
    }
}

onMounted(() => {
    try {
        isDismissed.value = localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        isDismissed.value = false;
    }

    mediaQuery = window.matchMedia(DESKTOP_QUERY);
    syncDesktop();
    mediaQuery.addEventListener('change', syncDesktop);
});

onBeforeUnmount(() => {
    mediaQuery?.removeEventListener('change', syncDesktop);
});
</script>

<template>
    <Transition name="desktop-notice">
        <div
            v-if="isVisible"
            role="dialog"
            aria-modal="true"
            :aria-label="$t('desktop_notice.title')"
            style="position: fixed; inset: 0; z-index: 200; display: flex; align-items: center; justify-content: center; padding: 24px; background: rgba(6,78,59,0.65); backdrop-filter: blur(6px);"
            @click.self="dismiss"
        >
            <div class="desktop-notice-card" style="position: relative; width: 100%; max-width: 420px; background: white; border-radius: 28px; padding: 40px 32px 32px; text-align: center; box-shadow: 0 24px 80px rgba(0,0,0,0.35);">
                <button
                    type="button"
                    :aria-label="$t('desktop_notice.dismiss')"
                    style="position: absolute; top: 14px; right: 14px; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border: 0; color: #64748b; cursor: pointer;"
                    @click="dismiss"
                >
                    <X style="width: 18px; height: 18px;" />
                </button>

                <div
                    class="desktop-notice-icon"
                    style="width: 88px; height: 88px; margin: 0 auto 24px; border-radius: 26px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #065f46 0%, #059669 45%, #0d9488 100%); box-shadow: 0 10px 30px rgba(5,150,105,0.45);"
                >
                    <Smartphone style="width: 44px; height: 44px; color: white;" />
                </div>

                <h2 style="font-size: 24px; font-weight: 800; letter-spacing: -0.02em; color: #064e3b; margin: 0;">
                    {{ $t('desktop_notice.title') }}
                </h2>
                <p style="font-size: 15px; line-height: 1.55; color: #475569; margin: 12px 0 28px;">
                    {{ $t('desktop_notice.message') }}
                </p>

                <button
                    type="button"
                    style="width: 100%; padding: 14px 20px; border: 0; border-radius: 14px; font-size: 15px; font-weight: 700; color: white; cursor: pointer; background: linear-gradient(135deg, #065f46 0%, #059669 45%, #0d9488 100%);"
                    @click="dismiss"
                >
                    {{ $t('desktop_notice.dismiss') }}
                </button>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.desktop-notice-enter-active,
.desktop-notice-leave-active {
    transition: opacity 0.25s ease;
}

.desktop-notice-enter-from,
.desktop-notice-leave-to {
    opacity: 0;
}

.desktop-notice-enter-active .desktop-notice-card {
    animation: desktop-notice-pop 0.35s cubic-bezier(0.2, 0.9, 0.3, 1.2);
}

.desktop-notice-icon {
    animation: desktop-notice-wiggle 2.4s ease-in-out 0.6s infinite;
}

@keyframes desktop-notice-pop {
    from {
        transform: translateY(24px) scale(0.94);
    }
    to {
        transform: translateY(0) scale(1);
    }
}

@keyframes desktop-notice-wiggle {
    0%, 60%, 100% {
        transform: rotate(0deg);
    }
    68% {
        transform: rotate(-10deg);
    }
    76% {
        transform: rotate(10deg);
    }
    84% {
        transform: rotate(-6deg);
    }
    92% {
        transform: rotate(6deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .desktop-notice-icon,
    .desktop-notice-enter-active .desktop-notice-card {
        animation: none;
    }
}
</style>
