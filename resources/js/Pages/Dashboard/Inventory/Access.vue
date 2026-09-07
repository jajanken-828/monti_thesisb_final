<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ShieldCheck,
    UserCog,
    Database,
    Package,
    Boxes,
    ClipboardList,
    Search,
    Save,
    X,
    Sparkles,
} from 'lucide-vue-next';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    permissions: {
        type: Object,
        default: () => ({}), // { user_id: { dashboard: true, materials: true, products: true, bom: true, checker: true } }
    },
    auth: Object,
});

// UI State
const searchQuery = ref('');
const saving = ref(false);
const savingUserId = ref(null);

// Local copy of permissions
const userPerms = ref({});
const initPerms = () => {
    const perms = {};
    props.users.forEach(user => {
        perms[user.id] = {
            dashboard: props.permissions[user.id]?.dashboard || false,
            materials: props.permissions[user.id]?.materials || false,
            products: props.permissions[user.id]?.products || false,
            bom: props.permissions[user.id]?.bom || false,
            checker: props.permissions[user.id]?.checker || false,
        };
    });
    userPerms.value = perms;
};
initPerms();

// Filter users (only secretary, general_manager, manager, supervisor)
const eligibleUsers = computed(() => {
    let users = props.users.filter(u =>
        ['secretary', 'general_manager', 'manager', 'supervisor'].includes(u.position)
    );
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        users = users.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
    }
    return users;
});

// Toggle a specific permission for a user
const togglePermission = (userId, permKey) => {
    userPerms.value[userId][permKey] = !userPerms.value[userId][permKey];
};

// Save all permissions for a specific user
const saveUserPermissions = (userId) => {
    savingUserId.value = userId;
    saving.value = true;
    router.post(route('inv.access.update'), {
        user_id: userId,
        permissions: userPerms.value[userId],
    }, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
            savingUserId.value = null;
        },
    });
};

// Permission labels and icons
const permissionList = [
    { key: 'dashboard', label: 'Dashboard', icon: Database },
    { key: 'materials', label: 'Materials', icon: Package },
    { key: 'products', label: 'Products', icon: Boxes },
    { key: 'bom', label: 'Bill of Materials', icon: ClipboardList },
    { key: 'checker', label: 'Stock Checker', icon: ShieldCheck },
];
</script>

<template>
    <Head title="Inventory Access Control | Monti Textile" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 pb-16">
                <!-- Hero header -->
                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl animate-float-delayed" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                    <div class="relative flex flex-wrap items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg animate-pop">
                            <ShieldCheck class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <Sparkles class="h-3.5 w-3.5" /> Inventory · Permissions
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Access Control</h1>
                            <p class="text-sm text-blue-100/90">Grant Inventory module access for secretaries, general managers, managers, and supervisors. · {{ eligibleUsers.length }} users</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur">
                                {{ eligibleUsers.length }} eligible
                            </span>
                        </div>
                    </div>

                    <div class="relative mt-6 flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <Search class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search users..."
                                class="w-full rounded-2xl border-0 bg-white/95 py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-lg placeholder:text-gray-400 focus:ring-2 focus:ring-white/70 outline-none transition"
                            />
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="animate-fade-up bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden" style="animation-delay: 80ms">
                    <div v-if="eligibleUsers.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
                        <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-4 animate-bounce-soft">
                            <UserCog class="h-9 w-9 text-indigo-400" />
                        </div>
                        <p class="text-sm font-black text-gray-700 dark:text-gray-200">No eligible users found.</p>
                        <p class="text-xs text-gray-400 mt-1">Try a different search.</p>
                        <button v-if="searchQuery" @click="searchQuery=''" class="mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 transition active:scale-95">Clear filters</button>
                    </div>
                    <TransitionGroup v-else name="card" tag="div" class="overflow-x-auto">
                        <table key="access-table" class="w-full text-left text-sm">
                            <thead class="bg-slate-50/80 dark:bg-zinc-800/60 border-b border-gray-100 dark:border-zinc-800">
                                <tr>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-gray-400">User</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-gray-400">Position</th>
                                    <th v-for="perm in permissionList" :key="perm.key" class="px-3 py-3.5 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-gray-400 text-center">
                                        {{ perm.label }}
                                    </th>
                                    <th class="px-5 py-3.5 text-center text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-gray-400 w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                <tr v-for="user in eligibleUsers" :key="user.id" class="hover:bg-indigo-50/40 dark:hover:bg-indigo-950/10 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 text-white text-sm font-black shadow-lg uppercase flex-shrink-0">
                                                {{ (user.name ?? '?').charAt(0) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-bold text-sm text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                                                <p class="text-[11px] text-slate-400 truncate">{{ user.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center gap-1 capitalize text-[11px] font-black px-2.5 py-1 rounded-full ring-1 bg-indigo-100 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/30"><span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />{{ user.position?.replace('_', ' ') }}</span>
                                    </td>
                                    <td v-for="perm in permissionList" :key="perm.key" class="px-3 py-4 text-center">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input
                                                type="checkbox"
                                                :checked="userPerms[user.id]?.[perm.key] || false"
                                                @change="togglePermission(user.id, perm.key)"
                                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30"
                                            />
                                        </label>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button
                                            @click="saveUserPermissions(user.id)"
                                            :disabled="saving && savingUserId === user.id"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-[10px] font-black uppercase tracking-wide rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 hover:scale-105 active:scale-95 transition-all shadow-lg shadow-indigo-500/20 disabled:opacity-50"
                                        >
                                            <Save class="w-3.5 h-3.5" />
                                            {{ saving && savingUserId === user.id ? 'Saving...' : 'Save' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TransitionGroup>
                    <div class="px-5 py-3 border-t border-gray-100 dark:border-zinc-800 text-xs text-slate-400 dark:text-gray-500 flex items-center gap-2 font-medium">
                        <ShieldCheck class="w-3.5 h-3.5 text-indigo-400" />
                        Users without any permission cannot access the Inventory module.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
@keyframes pop { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
@keyframes bounceSoft { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
.animate-fade-up { animation: fadeUp 0.6s cubic-bezier(0.22,1,0.36,1) both; }
.animate-float { animation: float 7s ease-in-out infinite; }
.animate-float-delayed { animation: float 8s ease-in-out 1.2s infinite; }
.animate-pop { animation: pop 0.5s cubic-bezier(0.22,1,0.36,1) both; }
.animate-bounce-soft { animation: bounceSoft 2.4s ease-in-out infinite; }
.card-enter-active { transition: opacity 0.45s ease, transform 0.45s cubic-bezier(0.22,1,0.36,1); }
.card-enter-from { opacity: 0; transform: translateY(18px) scale(0.98); }
.card-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; position: absolute; }
.card-leave-to { opacity: 0; transform: scale(0.96); }
.card-move { transition: transform 0.4s ease; }
</style>
