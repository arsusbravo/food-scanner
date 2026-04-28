<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import { Toaster } from '@/components/ui/sonner';
import { computed } from 'vue';
import { House, UtensilsCrossed, ScanLine, FileBarChart2 } from 'lucide-vue-next';

const page = usePage<{ auth: { user: { name: string; email: string } } }>();

const userInitials = computed(() => {
    const name = page.props.auth?.user?.name ?? '';
    return name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase() || '?';
});

const url = computed(() => page.url);

const navItems = [
    { label: 'Home',     icon: House,           href: '/waste' },
    { label: 'Log',      icon: UtensilsCrossed, href: '/waste/entries' },
    { label: 'AI Scan',  icon: ScanLine,        href: '/waste/ai-scan' },
    { label: 'Report',   icon: FileBarChart2,   href: '/waste/report' },
];

function isActive(href: string) {
    if (href === '/waste') return url.value === '/waste';
    return url.value.startsWith(href);
}
</script>

<template>
    <div class="flex flex-col bg-slate-50" style="min-height: 100dvh;">

        <!-- ── Gradient header ── -->
        <header
            class="flex-shrink-0 shadow-lg"
            style="background: linear-gradient(135deg, #065f46 0%, #059669 45%, #0d9488 100%);"
        >
            <div class="flex items-center justify-between px-5 py-4 max-w-lg mx-auto">
                <div>
                    <p style="color: rgba(167,243,208,0.85); font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;">
                        Kitchen Tracker
                    </p>
                    <h1 class="text-white text-xl font-bold mt-0.5 tracking-tight">FoodWise</h1>
                </div>

                <!-- User avatar -->
                <div
                    class="size-10 rounded-full flex items-center justify-center text-white font-bold text-sm"
                    style="background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.35); backdrop-filter: blur(4px);"
                >
                    {{ userInitials }}
                </div>
            </div>
        </header>

        <!-- ── Scrollable page content ── -->
        <main class="flex-1 overflow-y-auto pb-2">
            <slot />
        </main>

        <!-- ── Bottom navigation ── -->
        <nav
            class="flex-shrink-0 bg-white border-t border-slate-200"
            style="box-shadow: 0 -4px 24px rgba(0,0,0,0.07);"
        >
            <div class="flex items-center max-w-lg mx-auto px-2" style="height: 64px;">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all"
                    :style="isActive(item.href)
                        ? 'color: #059669;'
                        : 'color: #94a3b8;'"
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
