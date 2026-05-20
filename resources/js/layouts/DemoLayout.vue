<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Globe } from 'lucide-vue-next';
import { login } from '@/routes';
import { useLocale, type SupportedLocale } from '@/composables/useLocale';

const { locale, setLocale } = useLocale();
const langOpen = ref(false);

const LANG_LABELS: Record<SupportedLocale, string> = {
    en: 'English',
    nl: 'Nederlands',
    de: 'Deutsch',
    fr: 'Français',
    es: 'Español',
    'zh-TW': '繁體中文',
    'zh-CN': '简体中文',
    tr: 'Türkçe',
};

function selectLocale(code: SupportedLocale) {
    setLocale(code);
    langOpen.value = false;
}
</script>

<template>
    <div class="bg-slate-50" style="min-height: 100dvh;">
        <!-- Header -->
        <header
            class="shadow-lg"
            style="position: sticky; top: 0; z-index: 50;
                   background: linear-gradient(135deg, #065f46 0%, #059669 45%, #0d9488 100%);"
        >
            <div class="flex items-center justify-between px-5 py-4 max-w-2xl mx-auto">
                <div>
                    <p style="color: rgba(167,243,208,0.85); font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;">
                        KitchenLog
                    </p>
                    <h1 class="text-white text-xl font-bold mt-0.5 tracking-tight">Live demo</h1>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Language switcher -->
                    <div class="relative">
                        <button
                            type="button"
                            class="size-10 rounded-full flex items-center justify-center shrink-0"
                            style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);"
                            @click="langOpen = !langOpen"
                        >
                            <Globe style="width: 18px; height: 18px; color: white;" />
                        </button>

                        <div
                            v-if="langOpen"
                            style="position: fixed; inset: 0; z-index: 98;"
                            @click="langOpen = false"
                        />

                        <div
                            v-if="langOpen"
                            style="position: absolute; right: 0; top: calc(100% + 8px); background: white; border-radius: 14px; box-shadow: 0 8px 32px rgba(0,0,0,0.15); min-width: 160px; overflow: hidden; z-index: 99;"
                        >
                            <button
                                v-for="code in (['en','nl','de','fr','es','zh-TW','zh-CN','tr'] as SupportedLocale[])"
                                :key="code"
                                type="button"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-semibold text-left"
                                :style="locale === code
                                    ? 'background: #ecfdf5; color: #059669;'
                                    : 'color: #475569;'"
                                @click="selectLocale(code)"
                            >
                                {{ LANG_LABELS[code] }}
                                <span class="ml-auto text-xs font-bold opacity-50">{{ code.toUpperCase() }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Only log-in is exposed in the header. The register CTA
                         appears inside the demo flow itself, after the visitor
                         has tested a scan or generated a report. -->
                    <Link
                        :href="login()"
                        class="text-sm font-semibold text-white"
                        style="text-decoration: none; opacity: 0.9;"
                    >
                        {{ $t('demo.log_in') }}
                    </Link>
                </div>
            </div>
        </header>

        <main class="max-w-2xl mx-auto px-4 py-6">
            <slot />
        </main>

        <footer style="border-top: 1px solid #e2e8f0; padding: 20px; text-align: center;">
            <p style="font-size: 11px; color: #94a3b8; margin: 0;">
                &copy; {{ new Date().getFullYear() }} KitchenLog &mdash; Arsus B.V.
            </p>
        </footer>
    </div>
</template>
