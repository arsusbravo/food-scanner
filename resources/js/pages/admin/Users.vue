<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Pencil, Power, Trash2, ScanLine } from 'lucide-vue-next';
import { users as adminUsers } from '@/routes/admin';
import { show as showUser, destroy as destroyUserFn, toggleActive as toggleActiveFn } from '@/routes/admin/users';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Users', href: adminUsers() },
        ],
    },
});

type UserRow = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    created_at: string;
    total_entries: number;
    ai_scan_count: number;
    last_entry_at: string | null;
};

defineProps<{ users: UserRow[] }>();

const deleteConfirmId = ref<number | null>(null);

function initials(name: string): string {
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

function fmtDate(d: string): string {
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function doToggleActive(id: number) {
    router.patch(toggleActiveFn(id).url, {}, { preserveScroll: true });
}

function doDelete(id: number) {
    deleteConfirmId.value = null;
    router.delete(destroyUserFn(id).url);
}
</script>

<template>
    <Head title="Users" />

    <div class="flex flex-col gap-6 p-6">

        <div>
            <h1 class="text-xl font-bold">Users</h1>
            <p class="text-sm text-muted-foreground mt-0.5">{{ users.length }} registered users</p>
        </div>

        <div class="rounded-xl border bg-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">User</th>
                            <th class="text-center px-3 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Status</th>
                            <th class="text-right px-3 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden sm:table-cell">Joined</th>
                            <th class="text-right px-3 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Entries</th>
                            <th class="text-right px-3 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden md:table-cell">AI Scans</th>
                            <th class="text-right px-3 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden lg:table-cell">Last Active</th>
                            <th class="text-right px-5 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="users.length === 0">
                            <td colspan="7" class="text-center py-10 text-sm text-muted-foreground">No users yet.</td>
                        </tr>

                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="border-t hover:bg-muted/10 transition-colors"
                        >
                            <!-- User -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="size-9 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                        :style="user.is_active ? 'background:#dcfce7;color:#16a34a;' : 'background:#f1f5f9;color:#94a3b8;'"
                                    >{{ initials(user.name) }}</div>
                                    <div class="min-w-0">
                                        <p class="font-semibold truncate">{{ user.name }}</p>
                                        <p class="text-xs text-muted-foreground truncate">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-3 py-4 text-center">
                                <span
                                    class="text-xs font-bold px-2.5 py-1 rounded-full whitespace-nowrap"
                                    :style="user.is_active ? 'background:#dcfce7;color:#16a34a;' : 'background:#f1f5f9;color:#64748b;'"
                                >{{ user.is_active ? 'Active' : 'Inactive' }}</span>
                            </td>

                            <!-- Joined -->
                            <td class="px-3 py-4 text-right hidden sm:table-cell">
                                <span class="text-xs text-muted-foreground whitespace-nowrap">{{ fmtDate(user.created_at) }}</span>
                            </td>

                            <!-- Entries -->
                            <td class="px-3 py-4 text-right">
                                <span class="font-bold tabular-nums">{{ user.total_entries }}</span>
                            </td>

                            <!-- AI scans -->
                            <td class="px-3 py-4 hidden md:table-cell">
                                <div class="flex items-center justify-end gap-1.5">
                                    <ScanLine class="size-3 text-emerald-500" />
                                    <span class="font-semibold tabular-nums">{{ user.ai_scan_count }}</span>
                                </div>
                            </td>

                            <!-- Last active -->
                            <td class="px-3 py-4 text-right hidden lg:table-cell">
                                <span class="text-xs text-muted-foreground whitespace-nowrap">
                                    {{ user.last_entry_at ? fmtDate(user.last_entry_at) : '—' }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4">
                                <div v-if="deleteConfirmId === user.id" class="flex items-center justify-end gap-2">
                                    <span class="text-xs text-muted-foreground whitespace-nowrap">Delete?</span>
                                    <button
                                        @click="doDelete(user.id)"
                                        class="text-xs font-bold px-2.5 py-1 rounded-lg"
                                        style="background:#fee2e2;color:#dc2626;"
                                    >Yes</button>
                                    <button
                                        @click="deleteConfirmId = null"
                                        class="text-xs font-bold px-2.5 py-1 rounded-lg"
                                        style="background:#f1f5f9;color:#64748b;"
                                    >No</button>
                                </div>

                                <div v-else class="flex items-center justify-end gap-1">
                                    <Link
                                        :href="showUser(user.id).url"
                                        class="size-8 rounded-lg flex items-center justify-center hover:bg-muted transition-colors"
                                        title="Edit user"
                                    >
                                        <Pencil class="size-3.5 text-muted-foreground" />
                                    </Link>

                                    <button
                                        @click="doToggleActive(user.id)"
                                        class="size-8 rounded-lg flex items-center justify-center hover:bg-muted transition-colors"
                                        :title="user.is_active ? 'Deactivate' : 'Activate'"
                                    >
                                        <Power
                                            class="size-3.5"
                                            :style="user.is_active ? 'color:#16a34a;' : 'color:#94a3b8;'"
                                        />
                                    </button>

                                    <button
                                        @click="deleteConfirmId = user.id"
                                        class="size-8 rounded-lg flex items-center justify-center hover:bg-red-50 transition-colors"
                                        title="Delete user"
                                    >
                                        <Trash2 class="size-3.5 text-red-400" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</template>
