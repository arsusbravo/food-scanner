<script setup lang="ts">
import { Globe } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useLocale  } from '@/composables/useLocale';
import type {SupportedLocale} from '@/composables/useLocale';

const { locale, setLocale } = useLocale();

const LABELS: Record<SupportedLocale, string> = {
    en: 'EN',
    nl: 'NL',
    de: 'DE',
    fr: 'FR',
    es: 'ES',
    'zh-TW': '繁中',
    'zh-CN': '简中',
    tr: 'TR',
    id: 'ID',
};

const FULL_LABELS: Record<SupportedLocale, string> = {
    en: 'English',
    nl: 'Nederlands',
    de: 'Deutsch',
    fr: 'Français',
    es: 'Español',
    'zh-TW': '繁體中文',
    'zh-CN': '简体中文',
    tr: 'Türkçe',
    id: 'Bahasa Indonesia',
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
                v-for="code in (['en', 'nl', 'de', 'fr', 'es', 'zh-TW', 'zh-CN', 'tr', 'id'] as SupportedLocale[])"
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
