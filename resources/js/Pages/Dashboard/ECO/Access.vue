<template>
    <Head title="ECO Access Control" />
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
                            <Shield class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <Sparkles class="h-3.5 w-3.5" /> ECO · Permission Management
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">ECO Access</h1>
                            <p class="text-sm text-blue-100/90">Grant or revoke E-Commerce module access for Secretaries and Special Officers.</p>
                            <span v-if="!canEditAccess" class="mt-2 inline-block text-xs font-bold text-amber-700 bg-amber-50 px-3 py-1.5 rounded-full">View only</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 animate-pulse" /> {{ users.length }} users
                            </span>
                            <button @click="refreshData" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/25 backdrop-blur hover:bg-white/25 transition active:scale-95">
                                <RefreshCw class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info Banner -->
                <div class="animate-fade-up flex items-center gap-3 rounded-3xl border border-blue-200 dark:border-blue-800/50 bg-blue-50/70 dark:bg-blue-900/10 p-5 hover:shadow-lg transition-shadow" style="animation-delay:80ms">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white shadow-lg shrink-0"><Info class="h-5 w-5" /></span>
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <span class="font-black">CEO</span> has full access by default. Assign access below to other high-rank users.
                    </p>
                </div>

                <!-- Users Table -->
                <div class="animate-fade-up bg-white dark:bg-zinc-900 rounded-3xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden hover:shadow-xl transition-shadow duration-300" style="animation-delay:160ms">
                    <div class="p-6 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-indigo-50 to-transparent dark:from-indigo-900/20 flex items-center gap-2">
                        <User class="h-5 w-5 text-indigo-600" />
                        <h2 class="text-sm font-black uppercase tracking-widest">Eligible Users</h2>
                        <span class="ml-auto rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-[11px] font-black px-2.5 py-1">{{ users.length }} total</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-[10px] font-black uppercase text-gray-400 tracking-widest border-b border-gray-100 dark:border-zinc-800">
                                <tr>
                                    <th class="px-6 py-4">User</th>
                                    <th class="px-6 py-4">Role</th>
                                    <th class="px-6 py-4 text-center">Access Status</th>
                                    <th class="px-6 py-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <TransitionGroup name="card" tag="tbody" class="divide-y divide-gray-50 dark:divide-zinc-800">
                                <tr v-for="user in users" :key="user.id" class="hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 flex items-center justify-center text-white font-black shadow-lg uppercase flex-shrink-0">
                                                {{ (user.name ?? '?').charAt(0) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-black text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                                                <p class="text-[10px] font-bold text-gray-400 truncate">{{ user.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase ring-1"
                                            :class="user.role === 'CEO' ? 'bg-violet-100 text-violet-700 ring-violet-200 dark:bg-violet-500/15 dark:text-violet-300 dark:ring-violet-500/30' : user.role === 'Secretary' ? 'bg-blue-100 text-blue-700 ring-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:ring-blue-500/30' : 'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-zinc-800 dark:text-gray-300 dark:ring-zinc-700'">
                                            {{ user.role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="user.role === 'CEO'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full ring-1 text-[9px] font-black uppercase bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" /><CheckCircle class="h-3 w-3" /> Full Access
                                        </span>
                                        <span v-else-if="user.can_access_eco" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full ring-1 text-[9px] font-black uppercase bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" /><CheckCircle class="h-3 w-3" /> Granted
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full ring-1 text-[9px] font-black uppercase bg-gray-100 text-gray-500 ring-gray-200 dark:bg-zinc-800 dark:text-gray-400 dark:ring-zinc-700">
                                            <XCircle class="h-3 w-3" /> Revoked
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button v-if="user.role !== 'CEO' && canEditAccess" @click="toggleAccess(user)" :disabled="processing[user.id]"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none disabled:opacity-50"
                                            :class="user.can_access_eco ? 'bg-gradient-to-r from-indigo-600 to-violet-700 shadow-lg shadow-indigo-500/25' : 'bg-gray-300 dark:bg-zinc-700'">
                                            <span :class="user.can_access_eco ? 'translate-x-6' : 'translate-x-1'"
                                                class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
                                        </button>
                                        <span v-else-if="user.role === 'CEO'" class="text-xs text-gray-400 font-bold">N/A</span>
                                        <span v-else class="text-[10px] font-black uppercase text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">View only</span>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0" key="empty">
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <User class="mx-auto h-10 w-10 text-gray-200 dark:text-zinc-700 animate-bounce-soft" />
                                        <p class="mt-2 text-sm font-bold text-gray-400">No eligible users found (Secretaries or Special Officers).</p>
                                    </td>
                                </tr>
                            </TransitionGroup>
                        </table>
                    </div>
                </div>

                <!-- Help Card -->
                <div class="animate-fade-up group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 p-6 hover:shadow-xl hover:-translate-y-0.5 transition-all overflow-hidden" style="animation-delay:240ms">
                    <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                    <h3 class="relative text-sm font-black uppercase tracking-widest mb-3 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-900/20"><Info class="h-4 w-4 text-indigo-600" /></span> How it works
                    </h3>
                    <ul class="relative list-disc list-inside space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                        <li>Only the <span class="font-bold text-indigo-600 dark:text-indigo-400">CEO</span> can access this page.</li>
                        <li>Toggle the switch to grant or revoke ECO module access.</li>
                        <li>Granted users will see the ECO module in their dashboard sidebar.</li>
                        <li>Revoked users lose all ECO module access immediately.</li>
                    </ul>
                </div>

                <!-- Toast Notification -->
                <Transition name="toast">
                    <div v-if="toast.show" class="fixed bottom-8 right-8 z-50 px-6 py-3 rounded-2xl shadow-lg text-white font-bold text-sm"
                        :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
                        {{ toast.message }}
                    </div>
                </Transition>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { usePageAccess } from '@/composables/usePageAccess';
import { Shield, RefreshCw, User, CheckCircle, XCircle, Info, Sparkles } from 'lucide-vue-next';

const { canEdit } = usePageAccess();
const canEditAccess = computed(() => canEdit('ECO', 'access'));

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    accesses: {
        type: Object,
        default: () => ({})
    }
});

const processing = ref({});
const toast = ref({ show: false, type: 'success', message: '' });

// Merge access status into user objects for easier rendering
const usersWithAccess = props.users.map(user => ({
    ...user,
    can_access_eco: props.accesses[user.id] || false
}));

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const toggleAccess = async (user) => {
    processing.value[user.id] = true;
    try {
        await router.post(route('eco.access.update'), {
            user_id: user.id,
            can_access: !user.can_access_eco
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showToast('success', `${user.name} access ${!user.can_access_eco ? 'granted' : 'revoked'}.`);
            },
            onError: (errors) => {
                showToast('error', Object.values(errors)[0] || 'Update failed.');
            }
        });
    } catch (e) {
        showToast('error', 'An error occurred.');
    } finally {
        processing.value[user.id] = false;
    }
};

const refreshData = () => {
    router.reload({ only: ['users', 'accesses'] });
};
</script>

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
.modal-enter-active, .modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-active > div, .modal-leave-active > div { transition: transform 0.3s cubic-bezier(0.22,1,0.36,1), opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div, .modal-leave-to > div { opacity: 0; transform: scale(0.95) translateY(10px); }
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>
