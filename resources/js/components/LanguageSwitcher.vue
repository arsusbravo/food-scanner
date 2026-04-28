<script setup lang="ts">
import { Globe } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useLocale, type SupportedLocale } from '@/composables/useLocale';

const { locale, setLocale } = useLocale();

const LABELS: Record<SupportedLocale, string> = {
    en: 'EN',
    de: 'DE',
    fr: 'FR',
    es: 'ES',
};

const FULL_LABELS: Record<SupportedLocale, string> = {
    en: 'English',
    de: 'Deutsch',
    fr: 'Français',
    es: 'Español',
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger
            class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm text-sidebar-foreground/70 outline-none hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
        >
            <Globe class="size-4" />
            <span>{{ FULL_LABELS[locale] }}</span>
            <span class="ml-auto text-xs opacity-50">{{ LABELS[locale] }}</span>
        </DropdownMenuTrigger>
        <DropdownMenuContent side="top" align="start" class="w-40">
            <DropdownMenuItem
                v-for="code in (['en', 'de', 'fr', 'es'] as SupportedLocale[])"
                :key="code"
                :class="{ 'font-semibold text-primary': locale === code }"
                @click="setLocale(code)"
            >
                {{ FULL_LABELS[code] }}
                <span class="ml-auto text-xs text-muted-foreground">{{ LABELS[code] }}</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
