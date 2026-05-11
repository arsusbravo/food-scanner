<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { ArrowLeft, Send } from 'lucide-vue-next';

const { t } = useI18n();

const SUBJECTS = computed(() => [
    { value: 'question',  label: t('contact.subject_question') },
    { value: 'feedback',  label: t('contact.subject_feedback') },
    { value: 'complaint', label: t('contact.subject_complaint') },
    { value: 'other',     label: t('contact.subject_other') },
]);

const form = useForm({ subject: 'question', body: '' });

function send() {
    form.post('/waste/contact');
}
</script>

<template>
    <Head :title="t('contact.new_conversation')" />

    <div style="max-width: 600px; margin: 0 auto; padding: 24px 16px 32px;">

        <!-- Back -->
        <Link href="/waste/contact" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #059669; text-decoration: none; margin-bottom: 20px;">
            <ArrowLeft style="width: 15px; height: 15px;" />
            {{ t('contact.back') }}
        </Link>

        <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 20px;">{{ t('contact.new_conversation') }}</h1>

        <div style="background: white; border-radius: 20px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #f1f5f9;">
            <form @submit.prevent="send">

                <!-- Subject chips -->
                <div style="margin-bottom: 20px;">
                    <p style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 10px;">
                        {{ t('contact.subject_label') }}
                    </p>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button
                            v-for="s in SUBJECTS"
                            :key="s.value"
                            type="button"
                            @click="form.subject = s.value"
                            style="font-size: 13px; font-weight: 600; padding: 7px 16px; border-radius: 999px; border: 1.5px solid; cursor: pointer; transition: all 0.15s;"
                            :style="form.subject === s.value
                                ? 'background: #059669; color: white; border-color: #059669;'
                                : 'background: white; color: #64748b; border-color: #e2e8f0;'"
                        >{{ s.label }}</button>
                    </div>
                </div>

                <!-- Message -->
                <div style="margin-bottom: 20px;">
                    <p style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 10px;">
                        {{ t('contact.message_label') }}
                    </p>
                    <textarea
                        v-model="form.body"
                        :placeholder="t('contact.placeholder')"
                        rows="6"
                        style="width: 100%; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 14px 16px; font-size: 14px; font-family: inherit; resize: none; outline: none; color: #0f172a; background: #f8fafc; box-sizing: border-box; transition: border-color 0.15s;"
                        @focus="($event.target as HTMLElement).style.borderColor='#059669'"
                        @blur="($event.target as HTMLElement).style.borderColor='#e2e8f0'"
                    />
                    <p v-if="form.errors.body" style="font-size: 12px; color: #dc2626; margin: 4px 0 0;">{{ form.errors.body }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing || !form.body.trim()"
                    style="width: 100%; height: 50px; border-radius: 14px; border: none; font-size: 15px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; transition: opacity 0.15s;"
                    :style="form.processing || !form.body.trim()
                        ? 'background: #cbd5e1; color: white; cursor: not-allowed;'
                        : 'background: #059669; color: white; cursor: pointer;'"
                >
                    <Send style="width: 16px; height: 16px;" />
                    {{ form.processing ? '…' : t('contact.send') }}
                </button>
            </form>
        </div>

    </div>
</template>
