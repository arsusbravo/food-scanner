<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { ArrowLeft, Send } from 'lucide-vue-next';

type Conversation = { id: number; subject: string };
type Message = { id: number; body: string; is_admin_reply: boolean; created_at: string };

const props = defineProps<{ conversation: Conversation; messages: Message[] }>();
const { t } = useI18n();
const chatEnd = ref<HTMLElement | null>(null);

const SUBJECT_LABELS = computed<Record<string, string>>(() => ({
    question:  t('contact.subject_question'),
    feedback:  t('contact.subject_feedback'),
    complaint: t('contact.subject_complaint'),
    other:     t('contact.subject_other'),
}));

const form = useForm({ body: '' });

function send() {
    form.post(`/waste/contact/${props.conversation.id}/reply`, {
        onSuccess: () => {
            form.reset('body');
            nextTick(() => chatEnd.value?.scrollIntoView({ behavior: 'smooth' }));
        },
    });
}

function fmtDate(d: string): string {
    return new Date(d).toLocaleString(undefined, {
        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
    });
}

onMounted(() => nextTick(() => chatEnd.value?.scrollIntoView()));
</script>

<template>
    <Head :title="SUBJECT_LABELS[conversation.subject] ?? conversation.subject" />

    <div style="max-width: 600px; margin: 0 auto; padding: 24px 16px 32px;">

        <!-- Back + subject -->
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <Link href="/waste/contact" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #059669; text-decoration: none;">
                <ArrowLeft style="width: 15px; height: 15px;" />
                {{ t('contact.back') }}
            </Link>
            <span style="font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: #dcfce7; color: #059669;">
                {{ SUBJECT_LABELS[conversation.subject] ?? conversation.subject }}
            </span>
        </div>

        <!-- Messages -->
        <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 28px;">

            <div v-if="messages.length === 0" style="text-align: center; padding: 48px 24px; color: #94a3b8; background: white; border-radius: 18px; border: 1px solid #f1f5f9;">
                <p style="font-size: 14px; font-weight: 500; margin: 0;">{{ t('contact.empty') }}</p>
            </div>

            <div
                v-for="msg in messages"
                :key="msg.id"
                style="display: flex; align-items: flex-end; gap: 8px;"
                :style="msg.is_admin_reply ? '' : 'flex-direction: row-reverse;'"
            >
                <!-- Avatar -->
                <div
                    style="width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800; flex-shrink: 0;"
                    :style="msg.is_admin_reply ? 'background: #e2e8f0; color: #475569;' : 'background: #059669; color: white;'"
                >{{ msg.is_admin_reply ? 'KL' : 'ME' }}</div>

                <!-- Bubble + meta -->
                <div style="max-width: 78%;">
                    <div
                        style="padding: 11px 15px; font-size: 14px; line-height: 1.6; white-space: pre-wrap; word-break: break-word;"
                        :style="msg.is_admin_reply
                            ? 'background: white; color: #1e293b; border: 1px solid #e2e8f0; border-radius: 18px; border-bottom-left-radius: 4px;'
                            : 'background: #059669; color: white; border-radius: 18px; border-bottom-right-radius: 4px;'"
                    >{{ msg.body }}</div>
                    <div
                        style="font-size: 11px; color: #94a3b8; margin-top: 4px;"
                        :style="msg.is_admin_reply ? 'text-align: left; padding-left: 4px;' : 'text-align: right; padding-right: 4px;'"
                    >
                        <span v-if="msg.is_admin_reply">{{ t('contact.support') }} · </span>{{ fmtDate(msg.created_at) }}
                    </div>
                </div>
            </div>

            <div ref="chatEnd" />
        </div>

        <!-- Reply form -->
        <div style="background: white; border-radius: 20px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #f1f5f9;">
            <form @submit.prevent="send">
                <textarea
                    v-model="form.body"
                    :placeholder="t('contact.placeholder')"
                    rows="4"
                    style="width: 100%; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 12px 14px; font-size: 14px; font-family: inherit; resize: none; outline: none; color: #0f172a; background: #f8fafc; box-sizing: border-box; transition: border-color 0.15s;"
                    @focus="($event.target as HTMLElement).style.borderColor='#059669'"
                    @blur="($event.target as HTMLElement).style.borderColor='#e2e8f0'"
                />
                <p v-if="form.errors.body" style="font-size: 12px; color: #dc2626; margin: 4px 0 0;">{{ form.errors.body }}</p>

                <button
                    type="submit"
                    :disabled="form.processing || !form.body.trim()"
                    style="margin-top: 12px; width: 100%; height: 48px; border-radius: 14px; border: none; font-size: 15px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; transition: opacity 0.15s;"
                    :style="form.processing || !form.body.trim()
                        ? 'background: #cbd5e1; color: white; cursor: not-allowed;'
                        : 'background: #059669; color: white; cursor: pointer;'"
                >
                    <Send style="width: 15px; height: 15px;" />
                    {{ form.processing ? '…' : t('contact.send') }}
                </button>
            </form>
        </div>

    </div>
</template>
