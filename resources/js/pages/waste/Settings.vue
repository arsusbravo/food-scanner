<script setup lang="ts">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ArrowLeft, User, Building2, FileText, KeyRound, Eye, EyeOff, Check, LogOut } from 'lucide-vue-next';
import {
    profile as profileRoute,
    company as companyRoute,
    password as passwordRoute,
    documentLocale as documentLocaleRoute,
} from '@/routes/waste/settings';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    user: {
        name: string;
        email: string;
        document_locale: string | null;
    };
    company: {
        name: string;
        phone: string | null;
        address: string | null;
        city: string | null;
        postal_code: string | null;
        country: string | null;
    } | null;
}>();

const page = usePage<{ auth: { user: { name: string } } }>();

// ── Profile ───────────────────────────────────────────────────
const profileForm = useForm({
    name:  props.user.name,
    email: props.user.email,
});

function saveProfile() {
    profileForm.patch(profileRoute().url, { preserveScroll: true });
}

// ── Company ───────────────────────────────────────────────────
const companyForm = useForm({
    name:        props.company?.name        ?? '',
    phone:       props.company?.phone       ?? '',
    address:     props.company?.address     ?? '',
    city:        props.company?.city        ?? '',
    postal_code: props.company?.postal_code ?? '',
    country:     props.company?.country     ?? '',
});

function saveCompany() {
    companyForm.patch(companyRoute().url, { preserveScroll: true });
}

// ── Document language ─────────────────────────────────────────
const LOCALES = [
    { code: 'en', label: 'English' },
    { code: 'nl', label: 'Nederlands' },
    { code: 'de', label: 'Deutsch' },
    { code: 'fr', label: 'Français' },
    { code: 'es', label: 'Español' },
];

const localeForm = useForm({
    document_locale: props.user.document_locale ?? '',
});

function saveLocale() {
    localeForm.patch(documentLocaleRoute().url, { preserveScroll: true });
}

// ── Password ──────────────────────────────────────────────────
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrent = ref(false);
const showNew     = ref(false);

function savePassword() {
    passwordForm.patch(passwordRoute().url, {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

// ── Logout ────────────────────────────────────────────────────
function logout() {
    router.post('/logout');
}

// ── Helpers ───────────────────────────────────────────────────
const initials = computed(() => {
    const name = page.props.auth?.user?.name ?? props.user.name;
    return name.split(' ').map((w: string) => w[0]).slice(0, 2).join('').toUpperCase();
});

const inputMd  = 'width:100%;height:44px;border-radius:12px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;font-size:15px;padding:0 14px;outline:none;box-sizing:border-box;';
const selectMd = 'width:100%;height:44px;border-radius:12px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;font-size:15px;padding:0 12px;outline:none;box-sizing:border-box;appearance:none;';
</script>

<template>
    <Head :title="t('settings.title')" />

    <div class="max-w-lg mx-auto px-4 pt-5 pb-8 space-y-5">

        <!-- Header -->
        <div class="flex items-center gap-3">
            <button
                @click="$inertia.visit('/waste')"
                class="size-9 rounded-xl border flex items-center justify-center shrink-0 transition-colors hover:bg-slate-100"
                style="background:white;border-color:#e2e8f0;"
            >
                <ArrowLeft style="width:18px;height:18px;color:#64748b;" />
            </button>
            <div>
                <h1 class="text-xl font-bold text-slate-900">{{ t('settings.title') }}</h1>
                <p class="text-xs text-slate-400 mt-0.5">{{ t('settings.subtitle') }}</p>
            </div>
        </div>

        <!-- Avatar -->
        <div class="flex flex-col items-center py-4">
            <div
                class="size-20 rounded-full flex items-center justify-center text-2xl font-bold text-white"
                style="background: linear-gradient(135deg, #059669, #0d9488);"
            >
                {{ initials }}
            </div>
            <p class="mt-3 font-semibold text-slate-800">{{ user.name }}</p>
            <p class="text-sm text-slate-400">{{ user.email }}</p>
        </div>

        <!-- Profile card -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 space-y-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <div class="flex items-center gap-2">
                <div class="size-7 rounded-xl flex items-center justify-center" style="background:#ecfdf5;">
                    <User style="width:14px;height:14px;color:#059669;" />
                </div>
                <p class="font-bold text-sm text-slate-800">{{ t('settings.profile') }}</p>
            </div>

            <div class="space-y-3">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">{{ t('settings.name') }}</label>
                    <input v-model="profileForm.name" type="text" :style="inputMd" />
                    <p v-if="profileForm.errors.name" class="text-xs mt-1" style="color:#dc2626;">{{ profileForm.errors.name }}</p>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">{{ t('settings.email') }}</label>
                    <input v-model="profileForm.email" type="email" :style="inputMd" />
                    <p v-if="profileForm.errors.email" class="text-xs mt-1" style="color:#dc2626;">{{ profileForm.errors.email }}</p>
                </div>
            </div>

            <button
                @click="saveProfile"
                :disabled="profileForm.processing"
                class="w-full h-11 rounded-xl font-semibold text-sm text-white disabled:opacity-60 flex items-center justify-center gap-2"
                style="background: linear-gradient(135deg, #059669, #047857);"
            >
                <Check v-if="profileForm.recentlySuccessful" style="width:16px;height:16px;" />
                {{ profileForm.recentlySuccessful ? t('settings.saved') : profileForm.processing ? t('settings.saving') : t('settings.save_profile') }}
            </button>
        </div>

        <!-- Company card -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 space-y-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <div class="flex items-center gap-2">
                <div class="size-7 rounded-xl flex items-center justify-center" style="background:#f0f9ff;">
                    <Building2 style="width:14px;height:14px;color:#0284c7;" />
                </div>
                <p class="font-bold text-sm text-slate-800">{{ t('settings.company') }}</p>
            </div>

            <div class="space-y-3">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">
                        {{ t('settings.company_name') }} <span style="color:#dc2626;">*</span>
                    </label>
                    <input v-model="companyForm.name" type="text" :style="inputMd" />
                    <p v-if="companyForm.errors.name" class="text-xs mt-1" style="color:#dc2626;">{{ companyForm.errors.name }}</p>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">{{ t('settings.phone') }}</label>
                    <input v-model="companyForm.phone" type="tel" :style="inputMd" />
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">{{ t('settings.address') }}</label>
                    <input v-model="companyForm.address" type="text" :style="inputMd" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">{{ t('settings.city') }}</label>
                        <input v-model="companyForm.city" type="text" :style="inputMd" />
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">{{ t('settings.postal_code') }}</label>
                        <input v-model="companyForm.postal_code" type="text" :style="inputMd" />
                    </div>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">{{ t('settings.country') }}</label>
                    <input v-model="companyForm.country" type="text" :style="inputMd" />
                </div>
            </div>

            <button
                @click="saveCompany"
                :disabled="companyForm.processing || !companyForm.name"
                class="w-full h-11 rounded-xl font-semibold text-sm text-white disabled:opacity-60 flex items-center justify-center gap-2"
                style="background: linear-gradient(135deg, #0284c7, #0369a1);"
            >
                <Check v-if="companyForm.recentlySuccessful" style="width:16px;height:16px;" />
                {{ companyForm.recentlySuccessful ? t('settings.saved') : companyForm.processing ? t('settings.saving') : t('settings.save_company') }}
            </button>
        </div>

        <!-- Document language card -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 space-y-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <div class="flex items-center gap-2">
                <div class="size-7 rounded-xl flex items-center justify-center" style="background:#eff6ff;">
                    <FileText style="width:14px;height:14px;color:#2563eb;" />
                </div>
                <p class="font-bold text-sm text-slate-800">{{ t('settings.document_language') }}</p>
            </div>

            <p class="text-xs text-slate-500 leading-relaxed">
                {{ t('settings.document_language_desc') }}
            </p>

            <div>
                <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">{{ t('settings.language') }}</label>
                <div style="position:relative;">
                    <select v-model="localeForm.document_locale" :style="selectMd">
                        <option value="">{{ t('settings.same_as_app') }}</option>
                        <option v-for="loc in LOCALES" :key="loc.code" :value="loc.code">{{ loc.label }}</option>
                    </select>
                    <span style="position:absolute;right:14px;top:50%;transform:translateY(-50%);pointer-events:none;color:#94a3b8;">▾</span>
                </div>
            </div>

            <button
                @click="saveLocale"
                :disabled="localeForm.processing"
                class="w-full h-11 rounded-xl font-semibold text-sm text-white disabled:opacity-60 flex items-center justify-center gap-2"
                style="background: linear-gradient(135deg, #2563eb, #4f46e5);"
            >
                <Check v-if="localeForm.recentlySuccessful" style="width:16px;height:16px;" />
                {{ localeForm.recentlySuccessful ? t('settings.saved') : localeForm.processing ? t('settings.saving') : t('settings.save_language') }}
            </button>
        </div>

        <!-- Password card -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 space-y-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <div class="flex items-center gap-2">
                <div class="size-7 rounded-xl flex items-center justify-center" style="background:#fefce8;">
                    <KeyRound style="width:14px;height:14px;color:#d97706;" />
                </div>
                <p class="font-bold text-sm text-slate-800">{{ t('settings.change_password') }}</p>
            </div>

            <div class="space-y-3">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">{{ t('settings.current_password') }}</label>
                    <div style="position:relative;">
                        <input
                            v-model="passwordForm.current_password"
                            :type="showCurrent ? 'text' : 'password'"
                            :style="inputMd + 'padding-right:44px;'"
                            autocomplete="current-password"
                        />
                        <button
                            type="button"
                            @click="showCurrent = !showCurrent"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:#94a3b8;"
                        >
                            <EyeOff v-if="showCurrent" style="width:16px;height:16px;" />
                            <Eye v-else style="width:16px;height:16px;" />
                        </button>
                    </div>
                    <p v-if="passwordForm.errors.current_password" class="text-xs mt-1" style="color:#dc2626;">{{ passwordForm.errors.current_password }}</p>
                </div>

                <div>
                    <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">{{ t('settings.new_password') }}</label>
                    <div style="position:relative;">
                        <input
                            v-model="passwordForm.password"
                            :type="showNew ? 'text' : 'password'"
                            :style="inputMd + 'padding-right:44px;'"
                            autocomplete="new-password"
                        />
                        <button
                            type="button"
                            @click="showNew = !showNew"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:#94a3b8;"
                        >
                            <EyeOff v-if="showNew" style="width:16px;height:16px;" />
                            <Eye v-else style="width:16px;height:16px;" />
                        </button>
                    </div>
                    <p v-if="passwordForm.errors.password" class="text-xs mt-1" style="color:#dc2626;">{{ passwordForm.errors.password }}</p>
                </div>

                <div>
                    <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px;">{{ t('settings.confirm_new_password') }}</label>
                    <input
                        v-model="passwordForm.password_confirmation"
                        :type="showNew ? 'text' : 'password'"
                        :style="inputMd"
                        autocomplete="new-password"
                    />
                </div>
            </div>

            <button
                @click="savePassword"
                :disabled="passwordForm.processing || !passwordForm.password || !passwordForm.current_password"
                class="w-full h-11 rounded-xl font-semibold text-sm text-white disabled:opacity-40 flex items-center justify-center gap-2"
                style="background: linear-gradient(135deg, #1e293b, #334155);"
            >
                <Check v-if="passwordForm.recentlySuccessful" style="width:16px;height:16px;" />
                {{ passwordForm.recentlySuccessful ? t('settings.updated') : passwordForm.processing ? t('settings.updating') : t('settings.update_password') }}
            </button>
        </div>

        <!-- Logout -->
        <button
            @click="logout"
            class="w-full h-12 rounded-2xl font-semibold text-sm flex items-center justify-center gap-2 transition-colors hover:bg-red-50"
            style="background:white;border:1.5px solid #fecaca;color:#dc2626;box-shadow:0 2px 12px rgba(0,0,0,0.05);"
        >
            <LogOut style="width:16px;height:16px;" />
            {{ t('settings.logout') }}
        </button>

    </div>
</template>
