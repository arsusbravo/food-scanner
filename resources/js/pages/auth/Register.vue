<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Eye, EyeOff } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to get started',
    },
});

const showPassword = ref(false);
const showConfirm = ref(false);

const inputStyle = 'width: 100%; height: 48px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #0f172a; font-size: 15px; padding: 0 14px; outline: none; box-sizing: border-box; transition: border-color 0.15s;';
</script>

<template>
    <Head title="Register" />

    <Form
        :action="store.url()"
        method="post"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <!-- Name -->
        <div class="flex flex-col gap-1.5">
            <label for="name" style="font-size: 13px; font-weight: 700; color: #374151;">Full name</label>
            <input
                id="name"
                type="text"
                name="name"
                required
                autofocus
                autocomplete="name"
                placeholder="Your name"
                :tabindex="1"
                :style="inputStyle"
            />
            <InputError :message="errors.name" />
        </div>

        <!-- Email -->
        <div class="flex flex-col gap-1.5">
            <label for="email" style="font-size: 13px; font-weight: 700; color: #374151;">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
                :tabindex="2"
                :style="inputStyle"
            />
            <InputError :message="errors.email" />
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1.5">
            <label for="password" style="font-size: 13px; font-weight: 700; color: #374151;">Password</label>
            <div style="position: relative;">
                <input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Password"
                    :tabindex="3"
                    :style="inputStyle + 'padding-right: 44px;'"
                />
                <button
                    type="button"
                    :tabindex="-1"
                    style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; background: none; border: none; cursor: pointer; padding: 0; display: flex;"
                    @click="showPassword = !showPassword"
                >
                    <EyeOff v-if="showPassword" style="width: 18px; height: 18px;" />
                    <Eye v-else style="width: 18px; height: 18px;" />
                </button>
            </div>
            <InputError :message="errors.password" />
        </div>

        <!-- Confirm password -->
        <div class="flex flex-col gap-1.5">
            <label for="password_confirmation" style="font-size: 13px; font-weight: 700; color: #374151;">Confirm password</label>
            <div style="position: relative;">
                <input
                    id="password_confirmation"
                    :type="showConfirm ? 'text' : 'password'"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    :tabindex="4"
                    :style="inputStyle + 'padding-right: 44px;'"
                />
                <button
                    type="button"
                    :tabindex="-1"
                    style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; background: none; border: none; cursor: pointer; padding: 0; display: flex;"
                    @click="showConfirm = !showConfirm"
                >
                    <EyeOff v-if="showConfirm" style="width: 18px; height: 18px;" />
                    <Eye v-else style="width: 18px; height: 18px;" />
                </button>
            </div>
            <InputError :message="errors.password_confirmation" />
        </div>

        <!-- Submit -->
        <button
            type="submit"
            tabindex="5"
            :disabled="processing"
            class="flex items-center justify-center gap-2 transition-opacity disabled:opacity-60"
            style="width: 100%; height: 50px; border-radius: 14px; border: none; background: linear-gradient(135deg, #059669, #047857); color: white; font-size: 16px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 16px rgba(5,150,105,0.3);"
        >
            <Spinner v-if="processing" class="text-white" />
            {{ processing ? 'Creating account…' : 'Create account' }}
        </button>

        <!-- Log in link -->
        <p style="text-align: center; font-size: 14px; color: #64748b; margin: 0;">
            Already have an account?
            <Link :href="login()" :tabindex="6" style="font-weight: 700; color: #059669; text-decoration: none;">
                Log in
            </Link>
        </p>
    </Form>
</template>
