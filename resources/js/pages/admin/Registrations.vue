<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Link2, Trash2, Plus, Copy, Check, LockKeyhole, Globe, MousePointerClick, Home } from 'lucide-vue-next';
import { mode as modeRoute } from '@/routes/admin/registrations';
import { store as storeInvitation, destroy as destroyInvitation } from '@/routes/admin/registrations/invitations';

type Invitation = {
    id: number;
    email: string | null;
    note: string | null;
    created_by: string;
    expires_at: string | null;
    clicks: number;
    status: 'active' | 'expired';
    home_url: string;
    register_url: string;
};

const props = defineProps<{
    mode: 'invite_only' | 'open';
    invitations: Invitation[];
}>();

// ── Mode toggle ───────────────────────────────────────────────
function setMode(newMode: 'invite_only' | 'open') {
    if (newMode === props.mode) return;
    router.patch(modeRoute().url, { mode: newMode }, { preserveScroll: true });
}

// ── New invitation form ───────────────────────────────────────
const showNewForm = ref(false);

const newForm = useForm({
    email:      '',
    note:       '',
    expires_at: '',
});

function createInvitation() {
    newForm.post(storeInvitation().url, {
        preserveScroll: true,
        onSuccess: () => {
            newForm.reset();
            showNewForm.value = false;
        },
    });
}

// ── Delete invitation ─────────────────────────────────────────
function deleteInvitation(id: number) {
    router.delete(destroyInvitation(id).url, { preserveScroll: true });
}

// ── Copy link ─────────────────────────────────────────────────
const copied = ref<{ id: number; type: 'home' | 'register' } | null>(null);

function copyUrl(inv: Invitation, type: 'home' | 'register') {
    const url = type === 'home' ? inv.home_url : inv.register_url;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url);
    } else {
        const el = document.createElement('textarea');
        el.value = url;
        el.style.cssText = 'position:fixed;opacity:0;';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }
    copied.value = { id: inv.id, type };
    setTimeout(() => { copied.value = null; }, 2000);
}

function isCopied(inv: Invitation, type: 'home' | 'register') {
    return copied.value?.id === inv.id && copied.value?.type === type;
}

// ── Helpers ───────────────────────────────────────────────────
const activeCount = computed(() => props.invitations.filter(i => i.status === 'active').length);

const statusStyle: Record<string, string> = {
    active:  'background:#ecfdf5;color:#059669;',
    expired: 'background:#fef2f2;color:#dc2626;',
};

const inputMd = 'width:100%;height:40px;border-radius:10px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#0f172a;font-size:14px;padding:0 12px;outline:none;box-sizing:border-box;';
</script>

<template>
    <Head title="Registrations" />

    <div class="space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Registration</h1>
            <p class="text-sm text-slate-500 mt-1">Control who can sign up for KitchenLog.</p>
        </div>

        <!-- Mode card -->
        <div class="bg-white rounded-2xl border border-slate-100 p-6" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <h2 class="font-bold text-slate-800 mb-1">Registration mode</h2>
            <p class="text-sm text-slate-500 mb-5">Switch between invite-only and open registration at any time.</p>

            <div class="grid grid-cols-2 gap-3">
                <button
                    @click="setMode('invite_only')"
                    class="rounded-xl p-4 text-left transition-all border-2"
                    :style="mode === 'invite_only'
                        ? 'border-color:#059669;background:#ecfdf5;'
                        : 'border-color:#e2e8f0;background:white;'"
                >
                    <div class="flex items-center gap-2 mb-2">
                        <LockKeyhole style="width:18px;height:18px;" :style="mode === 'invite_only' ? 'color:#059669;' : 'color:#94a3b8;'" />
                        <span class="font-semibold text-sm" :style="mode === 'invite_only' ? 'color:#059669;' : 'color:#374151;'">Invite Only</span>
                        <span v-if="mode === 'invite_only'" class="ml-auto text-xs font-bold" style="color:#059669;">● Active</span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">Only users with an invitation link can register. Zero spam.</p>
                </button>

                <button
                    @click="setMode('open')"
                    class="rounded-xl p-4 text-left transition-all border-2"
                    :style="mode === 'open'
                        ? 'border-color:#2563eb;background:#eff6ff;'
                        : 'border-color:#e2e8f0;background:white;'"
                >
                    <div class="flex items-center gap-2 mb-2">
                        <Globe style="width:18px;height:18px;" :style="mode === 'open' ? 'color:#2563eb;' : 'color:#94a3b8;'" />
                        <span class="font-semibold text-sm" :style="mode === 'open' ? 'color:#2563eb;' : 'color:#374151;'">Open</span>
                        <span v-if="mode === 'open'" class="ml-auto text-xs font-bold" style="color:#2563eb;">● Active</span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">Anyone can register. Cloudflare Turnstile blocks bots automatically.</p>
                </button>
            </div>
        </div>

        <!-- Invitations card -->
        <div class="bg-white rounded-2xl border border-slate-100" style="box-shadow: 0 2px 12px rgba(0,0,0,0.05);">
            <div class="flex items-center justify-between p-5 border-b border-slate-100">
                <div>
                    <h2 class="font-bold text-slate-800">Invitation links</h2>
                    <p class="text-sm text-slate-500 mt-0.5">{{ activeCount }} active link{{ activeCount !== 1 ? 's' : '' }} — expire by date, reusable until then</p>
                </div>
                <button
                    @click="showNewForm = !showNewForm"
                    class="flex items-center gap-2 h-9 px-4 rounded-xl text-sm font-semibold text-white"
                    style="background: linear-gradient(135deg, #059669, #047857);"
                >
                    <Plus style="width:15px;height:15px;" />
                    New link
                </button>
            </div>

            <!-- New invitation form -->
            <div v-if="showNewForm" class="p-5 border-b border-slate-100 bg-slate-50">
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Recipient (optional)</label>
                        <input v-model="newForm.email" type="text" placeholder="e.g. john@restaurant.com" :style="inputMd" />
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Note (optional)</label>
                        <input v-model="newForm.note" type="text" placeholder="e.g. Amsterdam pilot group" :style="inputMd" />
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px;">Expires (optional)</label>
                        <input v-model="newForm.expires_at" type="date" :style="inputMd" />
                    </div>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="createInvitation"
                        :disabled="newForm.processing"
                        class="h-9 px-4 rounded-xl text-sm font-semibold text-white disabled:opacity-60"
                        style="background: linear-gradient(135deg, #059669, #047857);"
                    >
                        <Check v-if="newForm.recentlySuccessful" style="width:14px;height:14px;display:inline;" />
                        {{ newForm.processing ? 'Generating…' : 'Generate link' }}
                    </button>
                    <button
                        @click="showNewForm = false; newForm.reset()"
                        class="h-9 px-4 rounded-xl text-sm font-semibold text-slate-600"
                        style="background:#f1f5f9;"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="invitations.length === 0" class="p-8 text-center">
                <Link2 style="width:28px;height:28px;color:#cbd5e1;margin:0 auto 8px;" />
                <p class="text-sm text-slate-400">No invitation links yet. Create one above.</p>
            </div>

            <!-- Invitation rows -->
            <div v-for="inv in invitations" :key="inv.id"
                class="px-5 py-4 border-b border-slate-100 last:border-0"
                :style="inv.status === 'expired' ? 'opacity:0.5;' : ''"
            >
                <!-- Top row: status, email, note, clicks, expiry -->
                <div class="flex items-center gap-2 flex-wrap mb-2">
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full" :style="statusStyle[inv.status]">
                        {{ inv.status }}
                    </span>
                    <span v-if="inv.email" class="text-sm font-medium text-slate-700">{{ inv.email }}</span>
                    <span v-if="inv.note" class="text-xs text-slate-400">{{ inv.note }}</span>
                    <span class="ml-auto flex items-center gap-1 text-xs text-slate-400">
                        <MousePointerClick style="width:12px;height:12px;" />
                        {{ inv.clicks }} click{{ inv.clicks !== 1 ? 's' : '' }}
                    </span>
                </div>

                <!-- Bottom row: expiry + copy buttons + delete -->
                <div class="flex items-center gap-2 flex-wrap">
                    <span v-if="inv.expires_at" class="text-xs text-slate-400 mr-auto">
                        Expires {{ inv.expires_at }}
                    </span>
                    <span v-else class="mr-auto" />

                    <!-- Copy homepage URL -->
                    <button
                        v-if="inv.status === 'active'"
                        @click="copyUrl(inv, 'home')"
                        class="flex items-center gap-1.5 h-8 px-3 rounded-lg text-xs font-semibold transition-colors"
                        :style="isCopied(inv, 'home')
                            ? 'background:#ecfdf5;color:#059669;'
                            : 'background:#f1f5f9;color:#374151;'"
                    >
                        <Check v-if="isCopied(inv, 'home')" style="width:12px;height:12px;" />
                        <Home v-else style="width:12px;height:12px;" />
                        {{ isCopied(inv, 'home') ? 'Copied!' : 'Home' }}
                    </button>

                    <!-- Copy register URL -->
                    <button
                        v-if="inv.status === 'active'"
                        @click="copyUrl(inv, 'register')"
                        class="flex items-center gap-1.5 h-8 px-3 rounded-lg text-xs font-semibold transition-colors"
                        :style="isCopied(inv, 'register')
                            ? 'background:#ecfdf5;color:#059669;'
                            : 'background:#f1f5f9;color:#374151;'"
                    >
                        <Check v-if="isCopied(inv, 'register')" style="width:12px;height:12px;" />
                        <Copy v-else style="width:12px;height:12px;" />
                        {{ isCopied(inv, 'register') ? 'Copied!' : 'Register' }}
                    </button>

                    <button
                        @click="deleteInvitation(inv.id)"
                        class="size-8 rounded-lg flex items-center justify-center transition-colors hover:bg-red-50"
                        style="color:#dc2626;"
                    >
                        <Trash2 style="width:14px;height:14px;" />
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
