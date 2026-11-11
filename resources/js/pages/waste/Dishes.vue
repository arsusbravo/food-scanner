<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Camera, Check, ChevronDown, Copy, Loader2, MapPin, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { csrfFetch } from '@/lib/csrf';
import { blobToBase64, compressImage } from '@/lib/image';
import { destroy as destroyRoute, store as storeRoute, update as updateRoute } from '@/routes/waste/dishes';

type Dish = {
    id: number;
    dish_name: string;
    origin: string | null;
    title: string;
    description: string;
    confidence?: 'high' | 'medium' | 'low';
};

const props = defineProps<{
    recent: Dish[];
    languages: Record<string, string>;
    defaultLocale: string;
    quota: { used: number; limit: number | null };
}>();

const { t } = useI18n();

const file = ref<File | null>(null);
const previewUrl = ref<string | null>(null);
const hintName = ref('');
const hintOrigin = ref('');
const locale = ref(props.defaultLocale);
const working = ref(false);
const error = ref<string | null>(null);
const result = ref<Dish | null>(null);
const history = ref<Dish[]>([...props.recent]);
const used = ref(props.quota.used);
const copiedKey = ref<string | null>(null);
const saving = ref(false);
const saved = ref(false);
const justSavedId = ref<number | null>(null);
const banner = ref(false);
const copyFailed = ref(false);

/** Earlier dishes; the one being edited above is left out so it is not shown twice. */
const listed = computed(() => history.value.filter((dish) => dish.id !== result.value?.id));

const quotaLeft = computed(() => (props.quota.limit === null ? null : Math.max(0, props.quota.limit - used.value)));

function onFile(e: Event) {
    const picked = (e.target as HTMLInputElement).files?.[0];

    if (!picked) {
        return;
    }

    file.value = picked;
    previewUrl.value = URL.createObjectURL(picked);
    error.value = null;
}

async function write() {
    if (!file.value || working.value) {
        return;
    }

    working.value = true;
    error.value = null;

    try {
        const photo = await blobToBase64(await compressImage(file.value, 1600, 0.85));
        const res = await csrfFetch(storeRoute().url, {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ photo, name: hintName.value || null, origin: hintOrigin.value || null, locale: locale.value }),
        });
        const body = await res.json().catch(() => ({}));

        if (!res.ok) {
            error.value = body.error ?? Object.values((body.errors ?? {}) as Record<string, string[]>)[0]?.[0] ?? t('dishes.failed');

            return;
        }

        result.value = body;
        history.value = [body, ...history.value].slice(0, 20);
        used.value += 1;
        saved.value = false;
    } catch {
        error.value = t('dishes.failed');
    } finally {
        working.value = false;
    }
}

function again() {
    result.value = null;
    error.value = null;
    copyFailed.value = false;
    file.value = null;
    previewUrl.value = null;
    hintName.value = '';
    hintOrigin.value = '';
}

/** Clipboard API needs a secure context; fall back to a hidden textarea. */
async function copyText(text: string): Promise<boolean> {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);

            return true;
        }
    } catch {
        // fall through to the legacy path
    }

    const area = document.createElement('textarea');

    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();
    area.setSelectionRange(0, text.length);

    try {
        return document.execCommand('copy');
    } catch {
        return false;
    } finally {
        document.body.removeChild(area);
    }
}

type CopyPart = 'all' | 'title' | 'description';

async function copy(dish: Dish, part: CopyPart = 'all') {
    copyFailed.value = false;

    const text = part === 'title' ? dish.title : part === 'description' ? dish.description : `${dish.title}\n\n${dish.description}`;

    if (await copyText(text)) {
        const key = `${dish.id}-${part}`;

        copiedKey.value = key;
        setTimeout(() => {
            if (copiedKey.value === key) {
                copiedKey.value = null;
            }
        }, 1800);
    } else {
        copyFailed.value = true;
    }
}

async function saveEdits(dish: Dish) {
    if (saving.value) {
        return;
    }

    saving.value = true;
    error.value = null;

    try {
        const res = await csrfFetch(updateRoute(dish.id).url, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ title: dish.title, description: dish.description }),
        });

        if (!res.ok) {
            error.value = t('dishes.failed');

            return;
        }

        history.value = history.value.map((item) => (item.id === dish.id ? { ...item, title: dish.title, description: dish.description } : item));
        saved.value = true;

        // Show the green "Saved" state for a moment, then go back to the list.
        await new Promise((resolve) => setTimeout(resolve, 700));

        justSavedId.value = dish.id;
        banner.value = true;
        again();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => {
            banner.value = false;
        }, 2500);
    } catch {
        error.value = t('dishes.failed');
    } finally {
        saving.value = false;
        saved.value = false;
    }
}

async function remove(dish: Dish) {
    if (!confirm(t('dishes.delete_confirm'))) {
        return;
    }

    const res = await csrfFetch(destroyRoute(dish.id).url, { method: 'DELETE', headers: { Accept: 'application/json' } });

    if (res.ok) {
        history.value = history.value.filter((item) => item.id !== dish.id);

        if (result.value?.id === dish.id) {
            again();
        }
    }
}
</script>

<template>
    <Head :title="$t('dishes.title')" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-8 space-y-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $t('dishes.title') }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $t('dishes.subtitle') }}</p>
        </div>

        <p v-if="quotaLeft !== null" class="text-xs font-semibold" :style="quotaLeft === 0 ? 'color:#dc2626;' : 'color:#64748b;'">
            {{ quotaLeft === 0 ? $t('dishes.quota_reached') : $t('dishes.quota_left', { n: quotaLeft }) }}
        </p>

        <!-- Step 1: photo and optional hints -->
        <div v-if="!result" class="bg-white rounded-2xl border border-slate-100 p-5 space-y-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <label
                class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed p-6 text-center overflow-hidden"
                style="border-color: #d1fae5; background: #f9fafb;"
            >
                <input type="file" accept="image/*" class="sr-only" @change="onFile" />
                <img v-if="previewUrl" :src="previewUrl" alt="" class="max-h-56 rounded-xl object-cover" />
                <template v-else>
                    <div class="size-14 rounded-2xl flex items-center justify-center" style="background: #ecfdf5;">
                        <Camera style="width: 28px; height: 28px; color: #059669;" />
                    </div>
                    <p class="font-semibold text-slate-700">{{ $t('dishes.pick_photo') }}</p>
                </template>
            </label>

            <div class="space-y-3">
                <input
                    v-model="hintName"
                    type="text"
                    maxlength="120"
                    :placeholder="$t('dishes.hint_name')"
                    class="w-full h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 placeholder:text-slate-400 [color-scheme:light]"
                />
                <input
                    v-model="hintOrigin"
                    type="text"
                    maxlength="120"
                    :placeholder="$t('dishes.hint_origin')"
                    class="w-full h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 placeholder:text-slate-400 [color-scheme:light]"
                />
                <label class="block">
                    <span class="text-xs font-semibold text-slate-500">{{ $t('dishes.language') }}</span>
                    <select v-model="locale" class="mt-1 w-full h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 [color-scheme:light]">
                        <option v-for="(name, code) in languages" :key="code" :value="code">{{ name }}</option>
                    </select>
                </label>
            </div>

            <p v-if="error" class="rounded-xl px-4 py-3 text-sm font-semibold" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">{{ error }}</p>

            <button
                type="button"
                class="w-full h-12 rounded-xl font-semibold text-sm text-white disabled:opacity-50 flex items-center justify-center gap-2"
                style="background: linear-gradient(135deg, #059669, #047857);"
                :disabled="!file || working || quotaLeft === 0"
                @click="write"
            >
                <Loader2 v-if="working" class="animate-spin" style="width: 18px; height: 18px;" />
                {{ working ? $t('dishes.writing') : $t('dishes.write') }}
            </button>
        </div>

        <!-- Step 2: result, editable -->
        <div v-else class="bg-white rounded-2xl border border-slate-100 p-5 space-y-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                <span class="rounded-full px-2.5 py-1" style="background: #ecfdf5; color: #047857;">{{ result.dish_name }}</span>
                <span v-if="result.origin" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1" style="background: #f1f5f9; color: #475569;">
                    <MapPin style="width: 12px; height: 12px;" /> {{ result.origin }}
                </span>
            </div>

            <p v-if="result.confidence === 'low'" class="rounded-xl px-4 py-3 text-xs font-semibold" style="background: #fefce8; color: #a16207; border: 1px solid #fde68a;">
                {{ $t('dishes.low_confidence') }}
            </p>

            <div class="flex items-center gap-2">
                <input
                    v-model="result.title"
                    type="text"
                    maxlength="120"
                    :aria-label="$t('dishes.field_title')"
                    class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-lg font-bold text-slate-900 [color-scheme:light]"
                    @input="saved = false"
                />
                <button
                    type="button"
                    class="flex size-8 shrink-0 items-center justify-center rounded-lg transition-transform active:scale-90 "
                    :style="copiedKey === `${result.id}-title` ? 'background: #059669; color: white;' : 'background: #ecfdf5; color: #047857;'"
                    :aria-label="$t('dishes.copy_title')"
                    :title="$t('dishes.copy_title')"
                    @click.stop.prevent="copy(result, 'title')"
                >
                    <Check v-if="copiedKey === `${result.id}-title`" style="width: 15px; height: 15px;" />
                    <Copy v-else style="width: 15px; height: 15px;" />
                </button>
            </div>
            <div class="relative">
                <textarea
                    v-model="result.description"
                    rows="9"
                    maxlength="2000"
                    :aria-label="$t('dishes.field_description')"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-3 pr-12 text-sm leading-relaxed text-slate-900 [color-scheme:light]"
                    @input="saved = false"
                />
                <button
                    type="button"
                    class="flex size-8 shrink-0 items-center justify-center rounded-lg transition-transform active:scale-90 absolute right-2 top-2"
                    :style="copiedKey === `${result.id}-description` ? 'background: #059669; color: white;' : 'background: #ecfdf5; color: #047857;'"
                    :aria-label="$t('dishes.copy_description')"
                    :title="$t('dishes.copy_description')"
                    @click.stop.prevent="copy(result, 'description')"
                >
                    <Check v-if="copiedKey === `${result.id}-description`" style="width: 15px; height: 15px;" />
                    <Copy v-else style="width: 15px; height: 15px;" />
                </button>
            </div>

            <p v-if="error" class="rounded-xl px-4 py-3 text-sm font-semibold" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">{{ error }}</p>
            <p v-if="copyFailed" class="rounded-xl px-4 py-3 text-xs font-semibold" style="background: #fefce8; color: #a16207; border: 1px solid #fde68a;">{{ $t('dishes.copy_failed') }}</p>

            <p class="text-xs text-slate-400">{{ $t('dishes.check_hint') }}</p>

            <div class="grid grid-cols-2 gap-3">
                <button
                    type="button"
                    class="h-12 rounded-xl font-semibold text-sm text-white flex items-center justify-center gap-2 transition-transform active:scale-95"
                    style="background: linear-gradient(135deg, #059669, #047857);"
                    @click="copy(result)"
                >
                    <Check v-if="copiedKey === `${result.id}-all`" style="width: 16px; height: 16px;" />
                    <Copy v-else style="width: 16px; height: 16px;" />
                    {{ copiedKey === `${result.id}-all` ? $t('dishes.copied') : $t('dishes.copy_all') }}
                </button>
                <button
                    type="button"
                    class="h-12 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition-all active:scale-95 disabled:opacity-80"
                    :style="saved ? 'background: #059669; color: white;' : 'background: #ecfdf5; color: #047857;'"
                    :disabled="saving"
                    @click="saveEdits(result)"
                >
                    <Loader2 v-if="saving && !saved" class="animate-spin" style="width: 16px; height: 16px;" />
                    <Check v-else-if="saved" style="width: 16px; height: 16px;" />
                    {{ saved ? $t('dishes.saved') : saving ? $t('dishes.saving') : $t('dishes.save_changes') }}
                </button>
            </div>

            <button type="button" class="w-full h-11 rounded-xl text-sm font-semibold" style="background: #f1f5f9; color: #475569;" @click="again">
                {{ $t('dishes.another') }}
            </button>
        </div>

        <!-- Saved banner -->
        <p v-if="banner" class="flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
            <Check style="width: 16px; height: 16px;" /> {{ $t('dishes.saved_banner') }}
        </p>
        <p v-if="copyFailed && !result && listed.length" class="rounded-xl px-4 py-3 text-xs font-semibold" style="background: #fefce8; color: #a16207; border: 1px solid #fde68a;">{{ $t('dishes.copy_failed') }}</p>

        <!-- Earlier dishes -->
        <div v-if="listed.length" class="space-y-2">
            <h2 class="text-sm font-bold text-slate-700">{{ $t('dishes.recent') }}</h2>

            <details
                v-for="dish in listed"
                :key="`${dish.id}-${justSavedId === dish.id}`"
                :open="justSavedId === dish.id"
                class="group bg-white rounded-2xl border border-slate-100 overflow-hidden"
                style="box-shadow: 0 1px 6px rgba(0,0,0,0.04);"
            >
                <summary class="flex cursor-pointer list-none items-center gap-3 p-4 [&::-webkit-details-marker]:hidden">
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-slate-900 truncate">{{ dish.title }}</p>
                        <p v-if="dish.origin" class="text-xs text-slate-400 mt-0.5 truncate">{{ dish.origin }}</p>
                    </div>
                    <button
                    type="button"
                    class="flex size-8 shrink-0 items-center justify-center rounded-lg transition-transform active:scale-90 "
                    :style="copiedKey === `${dish.id}-title` ? 'background: #059669; color: white;' : 'background: #ecfdf5; color: #047857;'"
                    :aria-label="$t('dishes.copy_title')"
                    :title="$t('dishes.copy_title')"
                    @click.stop.prevent="copy(dish, 'title')"
                >
                    <Check v-if="copiedKey === `${dish.id}-title`" style="width: 15px; height: 15px;" />
                    <Copy v-else style="width: 15px; height: 15px;" />
                </button>
                    <button
                            type="button"
                            class="flex size-8 shrink-0 items-center justify-center rounded-lg transition-transform active:scale-90"
                            style="background: #fef2f2; color: #dc2626;"
                            :aria-label="$t('dishes.delete')"
                            :title="$t('dishes.delete')"
                            @click.stop.prevent="remove(dish)"
                        >
                            <Trash2 style="width: 15px; height: 15px;" />
                        </button>
                    <ChevronDown class="shrink-0 text-slate-400 transition-transform group-open:rotate-180" style="width: 20px; height: 20px;" />
                </summary>

                <div class="border-t border-slate-100 px-4 pb-4 pt-3 space-y-3">
                    <div class="relative">
                        <p class="whitespace-pre-line pr-10 text-sm leading-relaxed text-slate-600">{{ dish.description }}</p>
                        <div class="absolute right-0 top-0 flex gap-1.5">
                            <button
                    type="button"
                    class="flex size-8 shrink-0 items-center justify-center rounded-lg transition-transform active:scale-90 "
                    :style="copiedKey === `${dish.id}-description` ? 'background: #059669; color: white;' : 'background: #ecfdf5; color: #047857;'"
                    :aria-label="$t('dishes.copy_description')"
                    :title="$t('dishes.copy_description')"
                    @click.stop.prevent="copy(dish, 'description')"
                >
                    <Check v-if="copiedKey === `${dish.id}-description`" style="width: 15px; height: 15px;" />
                    <Copy v-else style="width: 15px; height: 15px;" />
                </button>
                        </div>
                    </div>
                </div>
            </details>
        </div>
    </div>
</template>
