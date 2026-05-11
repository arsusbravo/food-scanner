<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Plus, MessageSquare } from 'lucide-vue-next';

type Conversation = {
    id: number;
    subject: string;
    unread: boolean;
    last_message_at: string;
    messages_count: number;
    preview: string | null;
};

const props = defineProps<{ conversations: Conversation[] }>();
const { t } = useI18n();

const SUBJECT_LABELS = computed<Record<string, string>>(() => ({
    question:  t('contact.subject_question'),
    feedback:  t('contact.subject_feedback'),
    complaint: t('contact.subject_complaint'),
    other:     t('contact.subject_other'),
}));

const SUBJECT_COLORS: Record<string, string> = {
    question:  'background: #dbeafe; color: #1d4ed8;',
    feedback:  'background: #dcfce7; color: #059669;',
    complaint: 'background: #fee2e2; color: #dc2626;',
    other:     'background: #f1f5f9; color: #475569;',
};

function fmtDate(d: string): string {
    return new Date(d).toLocaleString(undefined, {
        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="t('contact.title')" />

    <div style="max-width: 600px; margin: 0 auto; padding: 24px 16px 32px;">

        <!-- Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <div>
                <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 4px;">{{ t('contact.title') }}</h1>
                <p style="font-size: 13px; color: #64748b; margin: 0;">{{ t('contact.subtitle') }}</p>
            </div>
            <Link
                href="/waste/contact/new"
                style="display: inline-flex; align-items: center; gap: 6px; height: 40px; padding: 0 16px; border-radius: 12px; background: #059669; color: white; font-size: 13px; font-weight: 700; text-decoration: none;"
            >
                <Plus style="width: 16px; height: 16px;" />
                {{ t('contact.new_conversation') }}
            </Link>
        </div>

        <!-- Empty state -->
        <div
            v-if="conversations.length === 0"
            style="text-align: center; padding: 56px 24px; background: white; border-radius: 20px; border: 1px solid #f1f5f9;"
        >
            <div style="font-size: 40px; margin-bottom: 12px;">💬</div>
            <p style="font-size: 15px; font-weight: 600; color: #0f172a; margin: 0 0 6px;">{{ t('contact.empty') }}</p>
            <p style="font-size: 13px; color: #94a3b8; margin: 0;">{{ t('contact.empty_sub') }}</p>
        </div>

        <!-- Conversation list -->
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <Link
                v-for="conv in conversations"
                :key="conv.id"
                :href="`/waste/contact/${conv.id}`"
                style="display: block; background: white; border-radius: 16px; padding: 16px; border: 1.5px solid; text-decoration: none; transition: box-shadow 0.15s;"
                :style="conv.unread ? 'border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,0.08);' : 'border-color: #f1f5f9;'"
            >
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <!-- Icon -->
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #f0fdf4; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <MessageSquare style="width: 18px; height: 18px; color: #059669;" />
                    </div>

                    <!-- Content -->
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span
                                style="font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 999px;"
                                :style="SUBJECT_COLORS[conv.subject] ?? SUBJECT_COLORS.other"
                            >{{ SUBJECT_LABELS[conv.subject] ?? conv.subject }}</span>
                            <span v-if="conv.unread" style="width: 8px; height: 8px; border-radius: 50%; background: #059669; flex-shrink: 0;" />
                        </div>
                        <p
                            v-if="conv.preview"
                            style="font-size: 13px; color: #475569; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                        >{{ conv.preview }}</p>
                        <p v-else style="font-size: 13px; color: #94a3b8; margin: 0; font-style: italic;">{{ t('contact.no_preview') }}</p>
                    </div>

                    <!-- Meta -->
                    <div style="flex-shrink: 0; text-align: right;">
                        <p style="font-size: 11px; color: #94a3b8; margin: 0 0 2px;">{{ fmtDate(conv.last_message_at) }}</p>
                        <p style="font-size: 11px; color: #cbd5e1; margin: 0;">{{ conv.messages_count }} msg</p>
                    </div>
                </div>
            </Link>
        </div>

    </div>
</template>
