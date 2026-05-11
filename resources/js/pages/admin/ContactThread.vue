<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { nextTick, onMounted, ref } from 'vue';
import { ArrowLeft, Send } from 'lucide-vue-next';

type Message = { id: number; body: string; is_admin_reply: boolean; created_at: string };
type User = { id: number; name: string; email: string };
type Conversation = { id: number; subject: string; user: User };

const props = defineProps<{ conversation: Conversation; messages: Message[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Contact', href: '/admin/contact' },
            { title: 'Thread' },
        ],
    },
});

const SUBJECT_LABELS: Record<string, string> = {
    question: 'Question', feedback: 'Feedback',
    complaint: 'Complaint', other: 'Other',
};

const SUBJECT_COLORS: Record<string, string> = {
    question:  'background:#dbeafe; color:#1d4ed8;',
    feedback:  'background:#dcfce7; color:#059669;',
    complaint: 'background:#fee2e2; color:#dc2626;',
    other:     'background:#f1f5f9; color:#475569;',
};

const chatEnd = ref<HTMLElement | null>(null);
const form = useForm({ body: '' });

function reply() {
    form.post(`/admin/contact/${props.conversation.id}/reply`, {
        onSuccess: () => {
            form.reset('body');
            nextTick(() => chatEnd.value?.scrollIntoView({ behavior: 'smooth' }));
        },
    });
}

function fmtDate(d: string): string {
    return new Date(d).toLocaleString('en-GB', {
        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
    });
}

function initials(name: string): string {
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

onMounted(() => nextTick(() => chatEnd.value?.scrollIntoView()));
</script>

<template>
    <Head :title="`${conversation.user.name} — Contact`" />

    <div class="flex flex-col gap-4 p-6 max-w-2xl">

        <!-- User header -->
        <div class="rounded-xl border bg-card px-5 py-4 flex items-center gap-4">
            <div class="size-10 rounded-full bg-emerald-100 flex items-center justify-center text-sm font-bold text-emerald-700 shrink-0">
                {{ initials(conversation.user.name) }}
            </div>
            <div class="flex-1">
                <p class="font-semibold text-sm">{{ conversation.user.name }}</p>
                <p class="text-xs text-muted-foreground">{{ conversation.user.email }}</p>
            </div>
            <span
                class="text-[10px] font-bold px-2.5 py-1 rounded-full"
                :style="SUBJECT_COLORS[conversation.subject] ?? SUBJECT_COLORS.other"
            >{{ SUBJECT_LABELS[conversation.subject] ?? conversation.subject }}</span>
            <Link
                :href="`/admin/users/${conversation.user.id}`"
                class="text-xs font-semibold text-emerald-600 hover:underline"
            >View profile →</Link>
        </div>

        <!-- Messages -->
        <div class="flex flex-col gap-4">
            <div v-if="messages.length === 0" class="rounded-xl border bg-card p-10 text-center text-sm text-muted-foreground">
                No messages yet.
            </div>

            <div
                v-for="msg in messages"
                :key="msg.id"
                class="flex items-end gap-2"
                :class="msg.is_admin_reply ? '' : 'flex-row-reverse'"
            >
                <!-- Avatar -->
                <div
                    class="size-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                    :class="msg.is_admin_reply ? 'bg-slate-200 text-slate-600' : 'bg-emerald-100 text-emerald-700'"
                >{{ msg.is_admin_reply ? 'KL' : initials(conversation.user.name) }}</div>

                <!-- Bubble -->
                <div :class="msg.is_admin_reply ? '' : 'items-end'" class="flex flex-col max-w-[75%]">
                    <div
                        class="px-4 py-2.5 text-sm leading-relaxed whitespace-pre-wrap wrap-break-word"
                        :class="msg.is_admin_reply
                            ? 'bg-white border border-slate-200 text-slate-800 rounded-2xl rounded-bl-sm'
                            : 'text-white rounded-2xl rounded-br-sm'"
                        :style="msg.is_admin_reply ? '' : 'background: #059669;'"
                    >{{ msg.body }}</div>
                    <p class="text-xs text-muted-foreground mt-1" :class="msg.is_admin_reply ? 'text-left pl-1' : 'text-right pr-1'">
                        {{ msg.is_admin_reply ? 'You (admin) · ' : '' }}{{ fmtDate(msg.created_at) }}
                    </p>
                </div>
            </div>

            <div ref="chatEnd" />
        </div>

        <!-- Reply form -->
        <div class="rounded-xl border bg-card p-5">
            <p class="text-xs font-bold text-muted-foreground uppercase tracking-wide mb-3">Reply to {{ conversation.user.name }}</p>
            <form @submit.prevent="reply">
                <textarea
                    v-model="form.body"
                    placeholder="Type your reply…"
                    rows="4"
                    class="w-full border border-input rounded-xl px-4 py-3 text-sm resize-none outline-none bg-muted/30 focus:border-emerald-500 transition-colors"
                />
                <p v-if="form.errors.body" class="text-xs text-red-500 mt-1">{{ form.errors.body }}</p>
                <button
                    type="submit"
                    :disabled="form.processing || !form.body.trim()"
                    class="mt-3 w-full h-11 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition-colors"
                    :class="form.processing || !form.body.trim() ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'text-white cursor-pointer'"
                    :style="form.processing || !form.body.trim() ? '' : 'background: #059669;'"
                >
                    <Send class="size-4" />
                    {{ form.processing ? 'Sending…' : 'Send reply' }}
                </button>
            </form>
        </div>

    </div>
</template>
