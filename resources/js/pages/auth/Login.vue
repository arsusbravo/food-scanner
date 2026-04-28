<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Eye, EyeOff } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Welcome back',
        description: 'Enter your email and password to log in',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const showPassword = ref(false);

const inputStyle = 'width: 100%; height: 48px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #0f172a; font-size: 15px; padding: 0 14px; outline: none; box-sizing: border-box; transition: border-color 0.15s;';
</script>

<template>
    <Head title="Log in" />

    <div v-if="status" class="mb-4 text-center text-sm font-semibold" style="color: #059669;">
        {{ status }}
    </div>

    <Form
        :action="store.url()"
        method="post"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <!-- Email -->
        <div class="flex flex-col gap-1.5">
            <label for="email" style="font-size: 13px; font-weight: 700; color: #374151;">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
                :tabindex="1"
                :style="inputStyle"
            />
            <InputError :message="errors.email" />
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center justify-between">
                <label for="password" style="font-size: 13px; font-weight: 700; color: #374151;">Password</label>
                <Link
                    v-if="canResetPassword"
                    :href="request()"
                    :tabindex="5"
                    style="font-size: 13px; font-weight: 600; color: #059669; text-decoration: none;"
                >
                    Forgot password?
                </Link>
            </div>
            <div style="position: relative;">
                <input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                    :tabindex="2"
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

        <!-- Remember me -->
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <input type="checkbox" name="remember" :tabindex="3" style="width: 16px; height: 16px; accent-color: #059669; cursor: pointer;" />
            <span style="font-size: 14px; font-weight: 500; color: #374151;">Remember me</span>
        </label>

        <!-- Submit -->
        <button
            type="submit"
            :tabindex="4"
            :disabled="processing"
            class="flex items-center justify-center gap-2 transition-opacity disabled:opacity-60"
            style="width: 100%; height: 50px; border-radius: 14px; border: none; background: linear-gradient(135deg, #059669, #047857); color: white; font-size: 16px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 16px rgba(5,150,105,0.3);"
        >
            <Spinner v-if="processing" class="text-white" />
            {{ processing ? 'Logging in…' : 'Log in' }}
        </button>

        <!-- Sign up link -->
        <p v-if="canRegister" style="text-align: center; font-size: 14px; color: #64748b; margin: 0;">
            Don't have an account?
            <Link :href="register()" :tabindex="6" style="font-weight: 700; color: #059669; text-decoration: none;">
                Sign up
            </Link>
        </p>
    </Form>
</template>
