<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Activity, BookOpen, LayoutGrid, Leaf, MessageSquare, Users, UserPlus } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { users as adminUsers, registrations as adminRegistrations, contact as adminContactRoute } from '@/routes/admin';
import type { NavItem } from '@/types';

const page = usePage<{ adminContactUnread: number }>();

const mainNavItems = computed<NavItem[]>(() => [
    { title: 'Dashboard',      href: dashboard(),              icon: LayoutGrid },
    // Hard-coded href because Wayfinder regenerates `@/routes/admin` only on
    // the next build; using the string keeps the sidebar working immediately
    // and matches the actual URL behind `route('admin.demo')`.
    { title: 'Demo',           href: '/admin/demo',            icon: Activity },
    { title: 'Users',          href: adminUsers(),             icon: Users },
    { title: 'Registrations',  href: adminRegistrations(),     icon: UserPlus },
    { title: 'Contact',        href: adminContactRoute.url(),  icon: MessageSquare, badge: page.props.adminContactUnread || 0 },
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Kitchen App',
        href: '/waste',
        icon: Leaf,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
