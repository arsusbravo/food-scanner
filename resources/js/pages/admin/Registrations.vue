<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Link2, Trash2, Plus, Copy, Check, LockKeyhole, Globe } from 'lucide-vue-next';
import { mode as modeRoute } from '@/routes/admin/registrations';
import { store as storeInvitation, destroy as destroyInvitation } from '@/routes/admin/registrations/invitations';

type Invitation = {
    id: number;
    email: string | null;
    note: string | null;
    created_by: string;
    expires_at: string | null;
    accepted_at: string | null;
    accepted_by: string | null;
    status: 'pending' | 'used' | 'expired';
    url: string;
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
const copiedId = ref<number | null>(null);

function copyLink(inv: Invitation) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(inv.url);
    } else {
        const el = document.createElement('textarea');
        el.value = inv.url;
        el.style.position = 'fixed';
        el.style.opacity = '0';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }
    copiedId.value = inv.id;
    setTimeout(() => { copiedId.value = null; }, 2000);
}

// ── Helpers ───────────────────────────────────────────────────
const pendingCount = computed(() => props.invitations.filter(i => i.status === 'pending').length);

const statusStyle: Record<string, string> = {
    pending:  'background:#ecfdf5;color:#059669;',
    used:     'background:#f1f5f9;color:#64748b;',
    expired:  'background:#fef2f2;color:#dc2626;',
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
                <!-- Invite only -->
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

                <!-- Open -->
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
                    <p class="text-sm text-slate-500 mt-0.5">{{ pendingCount }} unused link{{ pendingCount !== 1 ? 's' : '' }}</p>
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

            <!-- Invitations list -->
            <div v-if="invitations.length === 0" class="p-8 text-center">
                <Link2 style="width:28px;height:28px;color:#cbd5e1;margin:0 auto 8px;" />
                <p class="text-sm text-slate-400">No invitation links yet. Create one above.</p>
            </div>

            <div v-for="inv in invitations" :key="inv.id"
                class="flex items-center gap-4 px-5 py-4 border-b border-slate-100 last:border-0"
                :style="inv.status !== 'pending' ? 'opacity:0.5;' : ''"
            >
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span
                            class="text-xs font-bold px-2 py-0.5 rounded-full"
                            :style="statusStyle[inv.status]"
                        >{{ inv.status }}</span>
                        <span v-if="inv.email" class="text-sm font-medium text-slate-700 truncate">{{ inv.email }}</span>
                        <span v-if="inv.note" class="text-xs text-slate-400 truncate">{{ inv.note }}</span>
                    </div>
                    <div class="flex gap-3 mt-1">
                        <span v-if="inv.expires_at" class="text-xs text-slate-400">Expires {{ inv.expires_at }}</span>
                        <span v-if="inv.accepted_at" class="text-xs text-slate-400">Used by {{ inv.accepted_by }}</span>
                        <span class="text-xs text-slate-300 truncate font-mono hidden sm:block">{{ inv.url.replace(/^https?:\/\/[^/]+/, '') }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button
                        v-if="inv.status === 'pending'"
                        @click="copyLink(inv)"
                        class="flex items-center gap-1.5 h-8 px-3 rounded-lg text-xs font-semibold transition-colors"
                        :style="copiedId === inv.id
                            ? 'background:#ecfdf5;color:#059669;'
                            : 'background:#f1f5f9;color:#374151;'"
                    >
                        <Check v-if="copiedId === inv.id" style="width:13px;height:13px;" />
                        <Copy v-else style="width:13px;height:13px;" />
                        {{ copiedId === inv.id ? 'Copied!' : 'Copy' }}
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
