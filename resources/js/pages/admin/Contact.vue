<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Contact' },
        ],
    },
});

type User = { id: number; name: string; email: string };
type Conversation = {
    id: number;
    subject: string;
    unread: boolean;
    last_message_at: string;
    messages_count: number;
    preview: string | null;
    user: User;
};

defineProps<{ conversations: Conversation[] }>();

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

function fmtDate(d: string): string {
    return new Date(d).toLocaleString('en-GB', {
        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
    });
}

function initials(name: string): string {
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}
</script>

<template>
    <Head title="Contact" />

    <div class="p-6 max-w-3xl">
        <div v-if="conversations.length === 0" class="rounded-xl border bg-card p-12 text-center text-sm text-muted-foreground">
            No support conversations yet.
        </div>

        <div class="flex flex-col gap-3">
            <Link
                v-for="conv in conversations"
                :key="conv.id"
                :href="`/admin/contact/${conv.id}`"
                class="block rounded-xl border bg-card px-5 py-4 transition-shadow hover:shadow-sm no-underline"
                :class="conv.unread ? 'border-emerald-400 ring-1 ring-emerald-200' : ''"
            >
                <div class="flex items-start gap-4">
                    <!-- User avatar -->
                    <div class="size-10 rounded-full bg-emerald-100 flex items-center justify-center text-sm font-bold text-emerald-700 shrink-0">
                        {{ initials(conv.user.name) }}
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="text-sm font-semibold">{{ conv.user.name }}</span>
                            <span class="text-xs text-muted-foreground">{{ conv.user.email }}</span>
                            <span
                                class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                :style="SUBJECT_COLORS[conv.subject] ?? SUBJECT_COLORS.other"
                            >{{ SUBJECT_LABELS[conv.subject] ?? conv.subject }}</span>
                            <span v-if="conv.unread" class="size-2 rounded-full bg-emerald-500 shrink-0" />
                        </div>
                        <p v-if="conv.preview" class="text-sm text-muted-foreground truncate">{{ conv.preview }}</p>
                        <p v-else class="text-sm text-muted-foreground italic">No messages yet</p>
                    </div>

                    <!-- Meta -->
                    <div class="shrink-0 text-right">
                        <p class="text-xs text-muted-foreground">{{ fmtDate(conv.last_message_at) }}</p>
                        <p class="text-xs text-muted-foreground/60 mt-0.5">{{ conv.messages_count }} msg</p>
                    </div>
                </div>
            </Link>
        </div>
    </div>
</template>
