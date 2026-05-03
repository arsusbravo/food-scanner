<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { UtensilsCrossed } from 'lucide-vue-next';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div style="min-height: 100dvh; background: #f8fafc;">

        <!-- Emerald hero -->
        <div style="background: linear-gradient(135deg, #065f46 0%, #059669 50%, #0d9488 100%); padding: 48px 24px 72px; text-align: center;">
            <Link :href="home()" style="display: inline-flex; flex-direction: column; align-items: center; gap: 10px; text-decoration: none;">
                <div style="width: 56px; height: 56px; background: rgba(255,255,255,0.18); border-radius: 18px; display: flex; align-items: center; justify-content: center; border: 1.5px solid rgba(255,255,255,0.28);">
                    <UtensilsCrossed style="width: 26px; height: 26px; color: white;" />
                </div>
                <div>
                    <p style="color: rgba(167,243,208,0.85); font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin: 0;">
                        Food Waste Tracker
                    </p>
                    <p style="color: white; font-size: 26px; font-weight: 800; letter-spacing: -0.02em; margin: 2px 0 0;">
                        KitchenLog
                    </p>
                </div>
            </Link>
        </div>

        <!-- Card overlapping hero -->
        <div style="max-width: 420px; margin: -40px auto 0; padding: 0 20px 48px; position: relative; z-index: 10;">
            <div style="background: white; border-radius: 20px; padding: 28px 24px 32px; box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
                <div style="text-align: center; margin-bottom: 24px;">
                    <h1 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 6px;">{{ title }}</h1>
                    <p style="font-size: 13px; color: #64748b; margin: 0;">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>

    </div>
</template>
