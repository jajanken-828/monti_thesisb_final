<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ShieldCheck,
    UserCog,
    Warehouse,
    CheckCircle,
    XCircle,
    Save,
    RefreshCw,
    Search,
    ChevronDown,
} from 'lucide-vue-next';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    warehouses: {
        type: Array,
        default: () => [],
    },
    userWarehouseAccess: {
        type: Object,
        default: () => ({}),
    },
    auth: Object,
});

// State
const searchQuery = ref('');
const saving = ref(false);
const savingUserId = ref(null);

// Filtered users (only secretary, special_officer, manager, supervisor)
const eligibleUsers = computed(() => {
    let users = props.users.filter(u =>
        ['secretary', 'special_officer', 'manager', 'supervisor'].includes(u.position)
    );
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        users = users.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
    }
    return users;
});

// Local copy of access grants (warehouse ids per user)
const userAccess = ref({});
const initAccess = () => {
    const access = {};
    eligibleUsers.value.forEach(user => {
        access[user.id] = {
            granted: !!props.userWarehouseAccess[user.id]?.can_access,
            warehouse_ids: props.userWarehouseAccess[user.id]?.warehouse_ids || [],
        };
    });
    userAccess.value = access;
};
initAccess();

// Toggle grant for a user
const toggleGrant = (userId, granted) => {
    userAccess.value[userId].granted = granted;
    if (!granted) {
        userAccess.value[userId].warehouse_ids = [];
    }
};

// Update warehouse selection (multiselect)
const updateWarehouses = (userId, warehouseId, event) => {
    const current = userAccess.value[userId].warehouse_ids;
    if (event.target.checked) {
        if (!current.includes(warehouseId)) {
            current.push(warehouseId);
        }
    } else {
        const index = current.indexOf(warehouseId);
        if (index !== -1) current.splice(index, 1);
    }
};

// Save permissions for a specific user
const saveUserAccess = (userId) => {
    const data = userAccess.value[userId];
    savingUserId.value = userId;
    saving.value = true;
    router.post(route('warehouse.access.update'), {
        user_id: userId,
        grant: data.granted,
        warehouse_ids: data.warehouse_ids,
    }, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
            savingUserId.value = null;
        },
    });
};

// Get warehouse name by id
const getWarehouseName = (id) => {
    const wh = props.warehouses.find(w => w.id === id);
    return wh ? wh.name : 'Unknown';
};
</script>

<template>
    <Head title="Warehouse Access Control | Monti Textile" />
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
                                <UserCog class="h-3.5 w-3.5" /> Warehouse · Permissions
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Access Control</h1>
                            <p class="text-sm text-blue-100/90">{{ eligibleUsers.length }} eligible user{{ eligibleUsers.length !== 1 ? 's' : '' }} · secretaries, general managers, managers, supervisors</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur">
                                {{ warehouses.length }} warehouses
                            </span>
                            <span class="rounded-full bg-emerald-400/90 px-3 py-1.5 text-xs font-black text-emerald-950">{{ eligibleUsers.length }} users</span>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="relative mt-6">
                        <Search class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input v-model="searchQuery" type="text" placeholder="Search users by name or email..."
                            class="w-full rounded-2xl border-0 bg-white/95 py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-lg placeholder:text-gray-400 focus:ring-2 focus:ring-white/70 outline-none transition" />
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="eligibleUsers.length === 0"
                    class="animate-fade-up flex flex-col items-center justify-center py-20 text-center bg-white dark:bg-zinc-900 rounded-3xl border border-dashed border-gray-200 dark:border-zinc-800 shadow-sm">
                    <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-4 animate-bounce-soft">
                        <UserCog class="h-9 w-9 text-indigo-400" />
                    </div>
                    <p class="text-sm font-black text-gray-700 dark:text-gray-200">No eligible users found</p>
                    <p class="text-xs text-gray-400 mt-1">Try a different search.</p>
                    <button v-if="searchQuery" @click="searchQuery=''"
                        class="mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 transition active:scale-95">Clear search</button>
                </div>

                <!-- Users table -->
                <div v-else class="animate-fade-up relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 transition-all duration-300 overflow-hidden">
                    <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl" />
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50/80 dark:bg-zinc-800/60 border-b border-gray-100 dark:border-zinc-800">
                                <tr>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">User</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Position</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Access</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Assigned Warehouses</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest text-center">Action</th>
                                </tr>
                            </thead>
                            <TransitionGroup name="card" tag="tbody" class="divide-y divide-slate-100 dark:divide-zinc-800">
                                <tr v-for="(user, i) in eligibleUsers" :key="user.id" :style="{ transitionDelay: `${Math.min(i * 40, 400)}ms` }" class="group hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2.5">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 text-white text-sm font-black shadow flex-shrink-0 uppercase group-hover:scale-110 transition-transform">{{ (user.name ?? '?').charAt(0) }}</span>
                                            <div class="min-w-0">
                                                <p class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ user.name }}</p>
                                                <p class="text-[10px] text-slate-400 truncate">{{ user.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/30 capitalize">{{ user.position?.replace('_', ' ') }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                :checked="userAccess[user.id]?.granted || false"
                                                @change="toggleGrant(user.id, $event.target.checked)"
                                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <span :class="['inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full ring-1', userAccess[user.id]?.granted ? 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30' : 'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-zinc-800 dark:text-gray-300 dark:ring-zinc-700']">
                                                <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />
                                                {{ userAccess[user.id]?.granted ? 'Granted' : 'Denied' }}
                                            </span>
                                        </label>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div v-if="userAccess[user.id]?.granted" class="space-y-1.5">
                                            <div v-for="wh in warehouses" :key="wh.id" class="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    :id="`wh_${user.id}_${wh.id}`"
                                                    :value="wh.id"
                                                    :checked="userAccess[user.id]?.warehouse_ids.includes(wh.id)"
                                                    @change="updateWarehouses(user.id, wh.id, $event)"
                                                    class="w-3.5 h-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <label :for="`wh_${user.id}_${wh.id}`" class="flex items-center gap-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 cursor-pointer">
                                                    <Warehouse class="w-3 h-3 text-indigo-400" /> {{ wh.name }} ({{ wh.location }})
                                                </label>
                                            </div>
                                            <div v-if="warehouses.length === 0" class="text-xs text-slate-400 italic">No warehouses available.</div>
                                        </div>
                                        <div v-else class="text-xs text-slate-400 italic">Access denied – no warehouses assigned.</div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button
                                            @click="saveUserAccess(user.id)"
                                            :disabled="saving && savingUserId === user.id"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-[10px] font-black uppercase tracking-wide rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:opacity-90 hover:scale-105 active:scale-95 transition-all shadow-lg shadow-indigo-500/25 disabled:opacity-50"
                                        >
                                            <Save class="w-3.5 h-3.5" />
                                            {{ saving && savingUserId === user.id ? 'Saving...' : 'Save' }}
                                        </button>
                                    </td>
                                </tr>
                            </TransitionGroup>
                        </table>
                    </div>

                    <!-- Footer note -->
                    <div class="px-5 py-3.5 border-t border-gray-100 dark:border-zinc-800 text-xs text-slate-400 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/20"><ShieldCheck class="w-3 h-3 text-emerald-500" /></span>
                        <span class="font-medium">Only users with granted access can view and manage the selected warehouses.</span>
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
.card-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.card-leave-to { opacity: 0; transform: scale(0.96); }
.card-move { transition: transform 0.4s ease; }
</style>
