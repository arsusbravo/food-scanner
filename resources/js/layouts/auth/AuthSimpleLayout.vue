<script setup lang="ts">
import { ref, onMounted } from 'vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const showBanner = ref(false);
const year = new Date().getFullYear();

onMounted(() => {
    if (!localStorage.getItem('cookie_consent')) {
        showBanner.value = true;
    }
});

function acceptCookies() {
    localStorage.setItem('cookie_consent', '1');
    showBanner.value = false;
}
</script>

<template>
    <div style="min-height: 100dvh; background: #f8fafc;">

        <!-- Emerald hero -->
        <div style="background: linear-gradient(135deg, #065f46 0%, #059669 50%, #0d9488 100%); padding: 48px 24px 72px; text-align: center;">
            <a href="/" style="display: inline-flex; flex-direction: column; align-items: center; gap: 10px; text-decoration: none;">
                <img src="/images/doc/logo-dark.png" alt="KitchenLog" style="width: 56px; height: 56px; object-fit: contain;" />
                <div>
                    <p style="color: rgba(167,243,208,0.85); font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin: 0;">
                        Food Waste Tracker
                    </p>
                    <p style="color: white; font-size: 26px; font-weight: 800; letter-spacing: -0.02em; margin: 2px 0 0;">
                        KitchenLog
                    </p>
                </div>
            </a>
        </div>

        <!-- Card overlapping hero -->
        <div style="max-width: 420px; margin: -40px auto 0; padding: 0 20px 0; position: relative; z-index: 10;">
            <div style="background: white; border-radius: 20px; padding: 28px 24px 32px; box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
                <div style="text-align: center; margin-bottom: 24px;">
                    <h1 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 6px;">{{ title }}</h1>
                    <p style="font-size: 13px; color: #64748b; margin: 0;">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 24px 20px 40px; text-align: center;">
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 16px; font-size: 12px;">
                <a href="/privacy" style="color: #64748b; font-weight: 600;">Privacy Policy</a>
                <a href="/terms" style="color: #64748b; font-weight: 600;">Terms &amp; Conditions</a>
                <a href="/cookies" style="color: #64748b; font-weight: 600;">Cookie Policy</a>
            </div>
            <p style="font-size: 11px; color: #94a3b8; margin: 10px 0 0;">&copy; {{ year }} KitchenLog</p>
        </div>

        <!-- Cookie consent banner -->
        <div v-if="showBanner" style="position:fixed; bottom:0; left:0; right:0; z-index:9999; background:white; border-top:1px solid #e2e8f0; padding:16px 20px; box-shadow:0 -4px 24px rgba(0,0,0,0.08);">
            <div style="max-width:680px; margin:0 auto; display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                <p style="flex:1; font-size:13px; color:#374151; margin:0; min-width:200px;">
                    We use essential cookies for login and language preference. No tracking cookies.
                    <a href="/cookies" style="color:#059669; font-weight:600;">Cookie Policy</a>
                </p>
                <button @click="acceptCookies" style="background:#059669; color:white; border:none; padding:9px 20px; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer;">Accept</button>
            </div>
        </div>

    </div>
</template>
