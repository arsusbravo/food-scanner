<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import { docs } from '@/routes';
import { Toaster } from '@/components/ui/sonner';
import { ref, computed, onMounted } from 'vue';
import { House, UtensilsCrossed, ScanLine, FileBarChart2, Globe, Lightbulb, Settings2, BookOpen, MessageSquare, ExternalLink } from 'lucide-vue-next';
import { useLocale, type SupportedLocale } from '@/composables/useLocale';

const page = usePage<{ auth: { user: { name: string; email: string; is_pro: boolean } }; contactUnread: number; locale: string }>();

const userName = computed(() => page.props.auth?.user?.name ?? '');
const userEmail = computed(() => page.props.auth?.user?.email ?? '');
const userInitials = computed(() => {
    const name = userName.value;
    return name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase() || '?';
});

const isPro = computed(() => page.props.auth?.user?.is_pro ?? false);
const contactUnread = computed(() => page.props.contactUnread ?? 0);

const url = computed(() => page.url);
const { locale, setLocale } = useLocale();

// The server always knows the true locale (from the cookie).
// Force-sync on every mount so switching language on the public pages
// is immediately reflected when entering the app.
onMounted(() => {
    const serverLocale = page.props.locale as SupportedLocale;
    if (serverLocale && serverLocale !== locale.value) {
        setLocale(serverLocale);
    }
});

const langOpen = ref(false);
const avatarOpen = ref(false);

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

const navItems = [
    { label: 'Home',     icon: House,           href: '/waste' },
    { label: 'Log',      icon: UtensilsCrossed, href: '/waste/entries' },
    { label: 'Scan',     icon: ScanLine,        href: '/waste/ai-scan' },
    { label: 'Report',   icon: FileBarChart2,   href: '/waste/report' },
    { label: 'Insights', icon: Lightbulb,       href: '/waste/insights' },
];

function isActive(href: string) {
    if (href === '/waste') return url.value === '/waste';
    return url.value.startsWith(href);
}
</script>

<template>
    <div class="bg-slate-50" style="min-height: 100dvh;">

        <!-- Fixed header -->
        <header
            class="shadow-lg"
            style="position: fixed; top: 0; left: 0; right: 0; z-index: 50;
                   background: linear-gradient(135deg, #065f46 0%, #059669 45%, #0d9488 100%);"
        >
            <div class="flex items-center justify-between px-5 py-4 max-w-lg mx-auto">
                <div>
                    <p style="color: rgba(167,243,208,0.85); font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;">
                        KitchenLog
                    </p>
                    <h1 class="text-white text-xl font-bold mt-0.5 tracking-tight">K-Log</h1>
                </div>

                <div class="flex items-center gap-3">

                    <!-- Language switcher — globe icon only -->
                    <div class="relative">
                        <button
                            type="button"
                            class="size-10 rounded-full flex items-center justify-center shrink-0 transition-colors"
                            style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);"
                            @click="langOpen = !langOpen; avatarOpen = false"
                        >
                            <Globe style="width: 18px; height: 18px; color: white;" />
                        </button>

                        <!-- Backdrop -->
                        <div
                            v-if="langOpen"
                            style="position: fixed; inset: 0; z-index: 98;"
                            @click="langOpen = false"
                        />

                        <!-- Dropdown -->
                        <div
                            v-if="langOpen"
                            style="position: absolute; right: 0; top: calc(100% + 8px); background: white; border-radius: 14px; box-shadow: 0 8px 32px rgba(0,0,0,0.15); min-width: 160px; overflow: hidden; z-index: 99;"
                        >
                            <button
                                v-for="code in (['en','nl','de','fr','es','zh-TW','zh-CN','tr'] as SupportedLocale[])"
                                :key="code"
                                type="button"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-semibold text-left transition-colors"
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

                    <!-- Avatar — opens dropdown menu -->
                    <div class="relative">
                        <button
                            type="button"
                            class="size-10 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0 transition-colors"
                            style="position: relative; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.35); backdrop-filter: blur(4px);"
                            @click="avatarOpen = !avatarOpen; langOpen = false"
                        >
                            {{ userInitials }}
                            <!-- Unread dot (Pro + unread contact messages) -->
                            <span
                                v-if="isPro && contactUnread > 0"
                                style="position: absolute; top: 0; right: 0; width: 10px; height: 10px; background: #ef4444; border-radius: 50%; border: 2px solid #059669;"
                            />
                        </button>

                        <!-- Backdrop -->
                        <div
                            v-if="avatarOpen"
                            style="position: fixed; inset: 0; z-index: 98;"
                            @click="avatarOpen = false"
                        />

                        <!-- Dropdown -->
                        <div
                            v-if="avatarOpen"
                            style="position: absolute; right: 0; top: calc(100% + 8px); background: white; border-radius: 14px; box-shadow: 0 8px 32px rgba(0,0,0,0.15); min-width: 200px; overflow: hidden; z-index: 99;"
                        >
                            <!-- User info header -->
                            <div class="px-4 py-3" style="border-bottom: 1px solid #f1f5f9;">
                                <p class="text-sm font-bold text-slate-800 truncate">{{ userName }}</p>
                                <p class="text-xs text-slate-400 truncate mt-0.5">{{ userEmail }}</p>
                            </div>

                            <!-- Settings -->
                            <Link
                                href="/waste/settings"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-semibold text-left transition-colors"
                                style="color: #475569; display: flex;"
                                @click="avatarOpen = false"
                            >
                                <Settings2 style="width: 16px; height: 16px; shrink-0: 1; color: #94a3b8;" />
                                Settings
                            </Link>

                            <!-- Docs — full page load -->
                            <a
                                :href="docs().url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-semibold transition-colors"
                                style="color: #475569; display: flex;"
                                @click="avatarOpen = false"
                            >
                                <BookOpen style="width: 16px; height: 16px; color: #94a3b8;" />
                                Docs
                                <ExternalLink style="width: 12px; height: 12px; color: #cbd5e1; margin-left: auto;" />
                            </a>

                            <!-- Support — Pro only -->
                            <Link
                                v-if="isPro"
                                href="/waste/contact"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-semibold text-left transition-colors"
                                style="color: #475569; display: flex; border-top: 1px solid #f1f5f9;"
                                @click="avatarOpen = false"
                            >
                                <MessageSquare style="width: 16px; height: 16px; color: #94a3b8;" />
                                Support
                                <span
                                    v-if="contactUnread > 0"
                                    style="margin-left: auto; width: 8px; height: 8px; background: #ef4444; border-radius: 50%;"
                                />
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </header>

        <!-- Scrollable content — padded to clear fixed header (76px) and fixed nav (84px) -->
        <main style="padding-top: 76px; padding-bottom: 84px;">
            <slot />
        </main>

        <!-- Fixed bottom navigation -->
        <nav
            class="bg-white border-t border-slate-200"
            style="position: fixed; bottom: 0; left: 0; right: 0; z-index: 50;
                   box-shadow: 0 -4px 24px rgba(0,0,0,0.07);"
        >
            <div class="flex items-center max-w-lg mx-auto px-2" style="height: 64px;">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all"
                    :style="isActive(item.href) ? 'color: #059669;' : 'color: #94a3b8;'"
                >
                    <div
                        class="flex items-center justify-center rounded-xl transition-colors"
                        style="width: 36px; height: 32px;"
                        :style="isActive(item.href) ? 'background: #ecfdf5;' : ''"
                    >
                        <component :is="item.icon" style="width: 20px; height: 20px;" />
                    </div>
                    <span style="font-size: 10px; font-weight: 600; line-height: 1;">{{ item.label }}</span>
                </Link>
            </div>
        </nav>

        <Toaster />
    </div>
</template>
