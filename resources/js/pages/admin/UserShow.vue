<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowLeft, Pencil, Power, Trash2, X, Check,
    ScanLine, Scale, CalendarDays, Eye, EyeOff,
    Wand2, Copy, KeyRound, CreditCard,
} from 'lucide-vue-next';
import { users as adminUsers } from '@/routes/admin';
import {
    update as updateUserFn,
    destroy as destroyUserFn,
    toggleActive as toggleActiveFn,
    updatePassword as updatePasswordFn,
    updatePlan as updatePlanFn,
} from '@/routes/admin/users';
import { update as updateEntryFn, destroy as destroyEntryFn } from '@/routes/admin/entries';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Users', href: adminUsers() },
            { title: 'User Detail' },
        ],
    },
});

type UserProp = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    plan: string;
    plan_raw: string;
    plan_expires_at: string | null;
    ai_scan_limit: number | null;
    export_limit: number | null;
    ai_scans_used: number;
    ai_scan_quota: number | null;
    exports_used: number;
    export_quota: number | null;
    created_at: string;
};

type EntryRow = {
    id: number;
    item_name: string;
    category: string;
    weight_kg: string;
    reason: string;
    notes: string | null;
    source: string;
    logged_at: string;
};

type Stats = {
    total_entries: number;
    total_kg: number;
    ai_scan_count: number;
    this_month: number;
};

type ConversationRow = {
    id: number;
    subject: string;
    unread: boolean;
    last_message_at: string;
    messages_count: number;
};

const props = defineProps<{
    user: UserProp;
    entries: EntryRow[];
    stats: Stats;
    conversations: ConversationRow[];
}>();

// ── Edit profile ──────────────────────────────────────────────
const userForm = useForm({
    name: props.user.name,
    email: props.user.email,
});

function saveUser() {
    userForm.patch(updateUserFn(props.user.id).url, { preserveScroll: true });
}

// ── Password ──────────────────────────────────────────────────
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const generatedPwd  = ref('');
const copied        = ref(false);

function generatePassword(): void {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    const pwd = Array.from(bytes).map(b => chars[b % chars.length]).join('');
    generatedPwd.value                    = pwd;
    passwordForm.password                 = pwd;
    passwordForm.password_confirmation    = pwd;
    showPassword.value                    = true;
    copied.value                          = false;
}

function copyGenerated(): void {
    navigator.clipboard.writeText(generatedPwd.value);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}

function savePassword(): void {
    passwordForm.patch(updatePasswordFn(props.user.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            generatedPwd.value = '';
            showPassword.value = false;
        },
    });
}

// ── Subscription ──────────────────────────────────────────────
const planForm = useForm({
    plan:            props.user.plan_raw,
    plan_expires_at: props.user.plan_expires_at ?? '',
    ai_scan_limit:   props.user.ai_scan_limit !== null ? String(props.user.ai_scan_limit) : '',
    export_limit:    props.user.export_limit !== null ? String(props.user.export_limit) : '',
});

function savePlan(): void {
    planForm.patch(updatePlanFn(props.user.id).url, { preserveScroll: true });
}

// ── Delete user ───────────────────────────────────────────────
const deleteUserConfirm = ref(false);

function doDeleteUser(): void {
    router.delete(destroyUserFn(props.user.id).url);
}

function doToggleActive(): void {
    router.patch(toggleActiveFn(props.user.id).url, {}, { preserveScroll: true });
}

// ── Entry inline editing ──────────────────────────────────────
const editingId             = ref<number | null>(null);
const deleteEntryConfirmId  = ref<number | null>(null);

const editForm = useForm({
    item_name: '',
    category:  '',
    weight_kg: '',
    reason:    '',
    notes:     '',
    logged_at: '',
});

function startEdit(entry: EntryRow): void {
    editingId.value            = entry.id;
    deleteEntryConfirmId.value = null;
    editForm.item_name         = entry.item_name;
    editForm.category          = entry.category;
    editForm.weight_kg         = entry.weight_kg;
    editForm.reason            = entry.reason;
    editForm.notes             = entry.notes ?? '';
    editForm.logged_at         = toDatetimeLocal(entry.logged_at);
}

function cancelEdit(): void {
    editingId.value = null;
    editForm.reset();
}

function saveEntry(entry: EntryRow): void {
    editForm.patch(updateEntryFn(entry.id).url, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
}

function doDeleteEntry(id: number): void {
    deleteEntryConfirmId.value = null;
    router.delete(destroyEntryFn(id).url, { preserveScroll: true });
}

// ── Helpers ───────────────────────────────────────────────────
function initials(name: string): string {
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

function fmtDate(d: string): string {
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function fmtDateTime(d: string): string {
    return new Date(d).toLocaleString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

function fmtKg(kg: string | number): string {
    const n = typeof kg === 'string' ? parseFloat(kg) : kg;
    return n >= 1 ? `${n.toFixed(2)} kg` : `${(n * 1000).toFixed(0)} g`;
}

function toDatetimeLocal(d: string): string {
    const dt  = new Date(d);
    const pad = (n: number) => n.toString().padStart(2, '0');
    return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
}

const CATEGORY_COLORS: Record<string, string> = {
    protein:  'background:#fef2f2;color:#dc2626;',
    veg:      'background:#f0fdf4;color:#16a34a;',
    dairy:    'background:#fefce8;color:#ca8a04;',
    prepared: 'background:#fff7ed;color:#ea580c;',
};

const CATEGORY_LABELS: Record<string, string> = {
    protein: 'Protein', veg: 'Veg', dairy: 'Dairy', prepared: 'Prepared',
};

const REASON_LABELS: Record<string, string> = {
    spoilage: 'Spoilage', overproduction: 'Overproduction', expiry: 'Expiry',
    prep_waste: 'Prep Waste', other: 'Other',
};

const CATEGORIES = ['protein', 'veg', 'dairy', 'prepared'] as const;
const REASONS    = ['spoilage', 'overproduction', 'expiry', 'prep_waste', 'other'] as const;

const inputSm  = 'width:100%;height:36px;border-radius:8px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;font-size:13px;padding:0 10px;outline:none;box-sizing:border-box;';
const selectSm = 'width:100%;height:36px;border-radius:8px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;font-size:13px;padding:0 8px;outline:none;box-sizing:border-box;';
const inputMd  = 'width:100%;height:40px;border-radius:10px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;font-size:14px;padding:0 12px;outline:none;box-sizing:border-box;';
</script>

<template>
    <Head :title="`${user.name} — User`" />

    <div class="flex flex-col gap-6 p-6">

        <!-- ── Page header ── -->
        <div class="flex items-start gap-4">
            <Link
                :href="adminUsers().url"
                class="size-9 rounded-lg border flex items-center justify-center hover:bg-muted transition-colors shrink-0 mt-1"
            >
                <ArrowLeft class="size-4 text-muted-foreground" />
            </Link>

            <div class="flex items-center gap-3 flex-wrap flex-1 min-w-0">
                <div
                    class="size-12 rounded-full flex items-center justify-center text-base font-bold shrink-0"
                    :style="user.is_active ? 'background:#dcfce7;color:#16a34a;' : 'background:#f1f5f9;color:#94a3b8;'"
                >{{ initials(user.name) }}</div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-bold">{{ user.name }}</h1>
                        <span
                            class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                            :style="user.is_active ? 'background:#dcfce7;color:#16a34a;' : 'background:#f1f5f9;color:#64748b;'"
                        >{{ user.is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ user.email }} · Joined {{ fmtDate(user.created_at) }}</p>
                </div>
            </div>
        </div>

        <!-- ── Stats bar ── -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center gap-1.5 mb-1.5">
                    <CalendarDays class="size-3.5 text-muted-foreground" />
                    <p class="text-xs text-muted-foreground font-medium">Total Entries</p>
                </div>
                <p class="text-2xl font-bold tabular-nums">{{ stats.total_entries }}</p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center gap-1.5 mb-1.5">
                    <Scale class="size-3.5 text-muted-foreground" />
                    <p class="text-xs text-muted-foreground font-medium">Total Waste</p>
                </div>
                <p class="text-2xl font-bold tabular-nums">{{ stats.total_kg }}<span class="text-sm font-medium text-muted-foreground ml-1">kg</span></p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center gap-1.5 mb-1.5">
                    <ScanLine class="size-3.5 text-emerald-500" />
                    <p class="text-xs text-muted-foreground font-medium">Scans</p>
                </div>
                <p class="text-2xl font-bold tabular-nums">{{ stats.ai_scan_count }}</p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center gap-1.5 mb-1.5">
                    <CalendarDays class="size-3.5 text-violet-500" />
                    <p class="text-xs text-muted-foreground font-medium">This Month</p>
                </div>
                <p class="text-2xl font-bold tabular-nums">{{ stats.this_month }}</p>
            </div>
        </div>

        <!-- ── Management cards (2-col then 4-col grid) ── -->
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

                <!-- Edit profile -->
                <div class="rounded-xl border bg-card p-5 flex flex-col gap-4">
                    <p class="text-sm font-semibold">Edit Profile</p>
                    <div class="flex flex-col gap-3">
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Name</label>
                            <input v-model="userForm.name" type="text" :style="inputMd" />
                            <p v-if="userForm.errors.name" class="text-xs text-red-500 mt-1">{{ userForm.errors.name }}</p>
                        </div>
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Email</label>
                            <input v-model="userForm.email" type="email" :style="inputMd" />
                            <p v-if="userForm.errors.email" class="text-xs text-red-500 mt-1">{{ userForm.errors.email }}</p>
                        </div>
                    </div>
                    <button
                        @click="saveUser"
                        :disabled="userForm.processing"
                        class="w-full h-10 rounded-xl text-sm font-semibold transition-opacity disabled:opacity-60 mt-auto"
                        style="background:linear-gradient(135deg,#059669,#0d9488);color:white;"
                    >{{ userForm.processing ? 'Saving…' : 'Save Changes' }}</button>
                </div>

                <!-- Change password -->
                <div class="rounded-xl border bg-card p-5 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <KeyRound class="size-4 text-muted-foreground" />
                        <p class="text-sm font-semibold">Change Password</p>
                    </div>
                    <button
                        @click="generatePassword"
                        class="w-full h-9 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 border transition-colors hover:bg-muted"
                        style="color:#059669;"
                    >
                        <Wand2 class="size-4" />
                        Generate Password
                    </button>
                    <div v-if="generatedPwd" class="flex items-center gap-2 rounded-lg px-3 py-2" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <code class="flex-1 text-xs font-mono text-emerald-700 break-all">{{ generatedPwd }}</code>
                        <button
                            @click="copyGenerated"
                            class="shrink-0 size-6 rounded flex items-center justify-center transition-colors hover:bg-emerald-100"
                            :title="copied ? 'Copied!' : 'Copy'"
                        >
                            <Check v-if="copied" class="size-3.5 text-emerald-600" />
                            <Copy v-else class="size-3.5 text-emerald-600" />
                        </button>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">New Password</label>
                        <div style="position:relative;">
                            <input
                                v-model="passwordForm.password"
                                :type="showPassword ? 'text' : 'password'"
                                :style="inputMd + 'padding-right:40px;'"
                                autocomplete="new-password"
                            />
                            <button
                                @click="showPassword = !showPassword"
                                type="button"
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;"
                            >
                                <EyeOff v-if="showPassword" style="width:16px;height:16px;color:#94a3b8;" />
                                <Eye v-else style="width:16px;height:16px;color:#94a3b8;" />
                            </button>
                        </div>
                        <p v-if="passwordForm.errors.password" class="text-xs text-red-500 mt-1">{{ passwordForm.errors.password }}</p>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Confirm Password</label>
                        <input
                            v-model="passwordForm.password_confirmation"
                            :type="showPassword ? 'text' : 'password'"
                            :style="inputMd"
                            autocomplete="new-password"
                        />
                    </div>
                    <button
                        @click="savePassword"
                        :disabled="passwordForm.processing || !passwordForm.password"
                        class="w-full h-10 rounded-xl text-sm font-semibold transition-opacity disabled:opacity-40 mt-auto"
                        style="background:#1e293b;color:white;"
                    >{{ passwordForm.processing ? 'Updating…' : 'Update Password' }}</button>
                </div>

                <!-- Subscription -->
                <div class="rounded-xl border bg-card p-5 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <CreditCard class="size-4 text-muted-foreground" />
                        <p class="text-sm font-semibold">Subscription</p>
                    </div>

                    <!-- Usage summary -->
                    <div class="flex gap-3 text-xs text-muted-foreground">
                        <span>
                            Scans:
                            <strong style="color:#0f172a;">{{ user.ai_scans_used }} / {{ user.ai_scan_quota ?? '∞' }}</strong>
                        </span>
                        <span>
                            Exports:
                            <strong style="color:#0f172a;">{{ user.exports_used }} / {{ user.export_quota ?? '∞' }}</strong>
                        </span>
                    </div>

                    <!-- Plan selector -->
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Plan</label>
                        <select v-model="planForm.plan" :style="inputMd">
                            <option value="free">Free</option>
                            <option value="pro">Pro</option>
                        </select>
                    </div>

                    <!-- Trial expiry -->
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Trial Expires (optional)</label>
                        <input v-model="planForm.plan_expires_at" type="date" :style="inputMd" />
                    </div>

                    <!-- Custom limits -->
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Scan Limit</label>
                            <input v-model="planForm.ai_scan_limit" type="number" min="0" placeholder="Plan default" :style="inputMd" />
                        </div>
                        <div class="flex-1">
                            <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Export Limit</label>
                            <input v-model="planForm.export_limit" type="number" min="0" placeholder="Plan default" :style="inputMd" />
                        </div>
                    </div>

                    <button
                        @click="savePlan"
                        :disabled="planForm.processing"
                        class="w-full h-10 rounded-xl text-sm font-semibold transition-opacity disabled:opacity-60 mt-auto"
                        style="background:linear-gradient(135deg,#2563eb,#4f46e5);color:white;"
                    >{{ planForm.processing ? 'Saving…' : 'Save Subscription' }}</button>
                </div>

                <!-- Account actions -->
                <div class="rounded-xl border bg-card p-5 flex flex-col gap-2">
                    <p class="text-sm font-semibold mb-1">Account</p>
                    <button
                        @click="doToggleActive"
                        class="h-9 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 border transition-colors"
                        :style="user.is_active ? 'background:#f8fafc;color:#64748b;' : 'background:#dcfce7;color:#16a34a;'"
                    >
                        <Power class="size-4" />
                        {{ user.is_active ? 'Deactivate User' : 'Activate User' }}
                    </button>
                    <template v-if="deleteUserConfirm">
                        <p class="text-xs text-muted-foreground text-center pt-1">All entries will be deleted too.</p>
                        <div class="flex gap-2">
                            <button @click="doDeleteUser" class="flex-1 h-9 rounded-lg text-sm font-semibold" style="background:#fee2e2;color:#dc2626;">Yes, Delete</button>
                            <button @click="deleteUserConfirm = false" class="flex-1 h-9 rounded-lg text-sm font-semibold border" style="background:#f8fafc;color:#64748b;">Cancel</button>
                        </div>
                    </template>
                    <button
                        v-else
                        @click="deleteUserConfirm = true"
                        class="h-9 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 transition-colors"
                        style="background:#fee2e2;color:#dc2626;"
                    >
                        <Trash2 class="size-4" />
                        Delete User & All Data
                    </button>
                </div>

        </div>

        <!-- ── Entries table (full width) ── -->
        <div class="rounded-xl border bg-card overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b">
                    <h2 class="font-semibold text-sm">Waste Entries</h2>
                    <span class="text-xs text-muted-foreground">{{ entries.length }} total</span>
                </div>

                <div v-if="entries.length === 0" class="p-10 text-center text-sm text-muted-foreground">
                    No entries yet.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th class="text-left px-5 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide whitespace-nowrap">Date</th>
                                <th class="text-left px-3 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Item</th>
                                <th class="text-center px-3 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden sm:table-cell">Cat.</th>
                                <th class="text-left px-3 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden md:table-cell">Reason</th>
                                <th class="text-right px-3 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Weight</th>
                                <th class="text-center px-3 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden sm:table-cell">Src</th>
                                <th class="text-right px-5 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="entry in entries" :key="entry.id">

                                <!-- View row -->
                                <tr
                                    class="border-t hover:bg-muted/10 transition-colors"
                                    :class="{ 'bg-blue-50/40': editingId === entry.id }"
                                >
                                    <td class="px-5 py-3 text-xs text-muted-foreground whitespace-nowrap">{{ fmtDateTime(entry.logged_at) }}</td>
                                    <td class="px-3 py-3 font-medium max-w-40">
                                        <p class="truncate">{{ entry.item_name }}</p>
                                        <p v-if="entry.notes" class="text-xs text-muted-foreground truncate">{{ entry.notes }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-center hidden sm:table-cell">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded-full" :style="CATEGORY_COLORS[entry.category]">
                                            {{ CATEGORY_LABELS[entry.category] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-muted-foreground hidden md:table-cell whitespace-nowrap">{{ REASON_LABELS[entry.reason] }}</td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums text-muted-foreground whitespace-nowrap">{{ fmtKg(entry.weight_kg) }}</td>
                                    <td class="px-3 py-3 text-center hidden sm:table-cell">
                                        <span v-if="entry.source === 'ai_scan'" class="text-xs font-bold px-2 py-0.5 rounded-full" style="background:#ecfdf5;color:#059669;">Scan</span>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div v-if="deleteEntryConfirmId === entry.id" class="flex items-center justify-end gap-1.5">
                                            <button @click="doDeleteEntry(entry.id)" class="text-xs font-bold px-2 py-1 rounded-lg" style="background:#fee2e2;color:#dc2626;">Yes</button>
                                            <button @click="deleteEntryConfirmId = null" class="text-xs font-bold px-2 py-1 rounded-lg" style="background:#f1f5f9;color:#64748b;">No</button>
                                        </div>
                                        <div v-else class="flex items-center justify-end gap-1">
                                            <button
                                                v-if="editingId === entry.id"
                                                @click="cancelEdit"
                                                class="size-7 rounded-lg flex items-center justify-center hover:bg-muted transition-colors"
                                                title="Cancel"
                                            ><X class="size-3.5 text-muted-foreground" /></button>
                                            <button
                                                v-else
                                                @click="startEdit(entry)"
                                                class="size-7 rounded-lg flex items-center justify-center hover:bg-muted transition-colors"
                                                title="Edit"
                                            ><Pencil class="size-3.5 text-muted-foreground" /></button>
                                            <button
                                                @click="deleteEntryConfirmId = entry.id; editingId = null"
                                                class="size-7 rounded-lg flex items-center justify-center hover:bg-red-50 transition-colors"
                                                title="Delete"
                                            ><Trash2 class="size-3.5 text-red-400" /></button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Inline edit form row -->
                                <tr v-if="editingId === entry.id" class="border-t bg-blue-50/20">
                                    <td colspan="7" class="px-5 py-4">
                                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
                                            <div class="col-span-2 xl:col-span-2">
                                                <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:3px;">Item Name</label>
                                                <input v-model="editForm.item_name" type="text" :style="inputSm" />
                                                <p v-if="editForm.errors.item_name" class="text-xs text-red-500 mt-0.5">{{ editForm.errors.item_name }}</p>
                                            </div>
                                            <div>
                                                <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:3px;">Category</label>
                                                <select v-model="editForm.category" :style="selectSm">
                                                    <option v-for="c in CATEGORIES" :key="c" :value="c">{{ CATEGORY_LABELS[c] }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:3px;">Reason</label>
                                                <select v-model="editForm.reason" :style="selectSm">
                                                    <option v-for="r in REASONS" :key="r" :value="r">{{ REASON_LABELS[r] }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:3px;">Weight (kg)</label>
                                                <input v-model="editForm.weight_kg" type="number" step="0.001" min="0.001" :style="inputSm" />
                                                <p v-if="editForm.errors.weight_kg" class="text-xs text-red-500 mt-0.5">{{ editForm.errors.weight_kg }}</p>
                                            </div>
                                            <div>
                                                <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:3px;">Date &amp; Time</label>
                                                <input v-model="editForm.logged_at" type="datetime-local" :style="inputSm" />
                                            </div>
                                            <div class="col-span-2 sm:col-span-3 xl:col-span-6">
                                                <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:3px;">Notes (optional)</label>
                                                <input v-model="editForm.notes" type="text" placeholder="Leave blank to clear" :style="inputSm" />
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 mt-3">
                                            <button
                                                @click="saveEntry(entry)"
                                                :disabled="editForm.processing"
                                                class="h-8 px-4 rounded-lg text-xs font-semibold flex items-center gap-1.5 disabled:opacity-60"
                                                style="background:linear-gradient(135deg,#059669,#0d9488);color:white;"
                                            >
                                                <Check class="size-3" />
                                                {{ editForm.processing ? 'Saving…' : 'Save Entry' }}
                                            </button>
                                            <button
                                                @click="cancelEdit"
                                                class="h-8 px-4 rounded-lg text-xs font-semibold border"
                                                style="background:#f8fafc;color:#64748b;"
                                            >Cancel</button>
                                        </div>
                                    </td>
                                </tr>

                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Support Conversations -->
            <div class="rounded-xl border bg-card overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4 border-b">
                    <h2 class="font-semibold text-sm">Support Conversations</h2>
                    <span class="text-xs text-muted-foreground">{{ conversations.length }} total</span>
                </div>

                <div v-if="conversations.length === 0" class="p-10 text-center text-sm text-muted-foreground">
                    No conversations yet.
                </div>

                <div v-else class="divide-y">
                    <Link
                        v-for="conv in conversations"
                        :key="conv.id"
                        :href="`/admin/contact/${conv.id}`"
                        class="flex items-center gap-4 px-5 py-3 hover:bg-muted/40 transition-colors"
                    >
                        <span
                            class="text-[10px] font-bold px-2 py-0.5 rounded-full capitalize"
                            :style="{
                                question:  'background:#dbeafe; color:#1d4ed8;',
                                feedback:  'background:#dcfce7; color:#059669;',
                                complaint: 'background:#fee2e2; color:#dc2626;',
                                other:     'background:#f1f5f9; color:#475569;',
                            }[conv.subject] ?? 'background:#f1f5f9; color:#475569;'"
                        >{{ conv.subject }}</span>
                        <span v-if="conv.unread" class="size-2 rounded-full bg-emerald-500 shrink-0" />
                        <span class="flex-1 text-xs text-muted-foreground">{{ conv.messages_count }} messages</span>
                        <span class="text-xs text-muted-foreground">
                            {{ new Date(conv.last_message_at).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                        </span>
                        <span class="text-xs font-semibold" style="color:#059669;">Open →</span>
                    </Link>
                </div>
            </div>

    </div>
</template>
