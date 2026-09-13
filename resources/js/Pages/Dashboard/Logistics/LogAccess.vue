<template>
    <Head title="Logistics Access Control" />
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
                                <ShieldCheck class="h-3.5 w-3.5" /> Logistics · Security &amp; Permissions
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Access Control <span v-if="!canEditAccess" class="ml-2 inline-block rounded-full bg-amber-400/90 px-3 py-1 align-middle text-xs font-black uppercase tracking-widest text-amber-950">View only</span></h1>
                            <p class="text-sm text-blue-100/90">Grant or revoke Logistics module access for Secretary and Special Officers.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 animate-pulse" /> {{ users.length }} user{{ users.length !== 1 ? 's' : '' }}
                            </span>
                            <button @click="refreshData" class="rounded-2xl bg-white px-4 py-2.5 text-xs font-black uppercase tracking-wide text-indigo-700 shadow-lg transition-all duration-200 hover:scale-105 active:scale-95">
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="animate-fade-up group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-blue-100 dark:border-blue-800/50 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1 transition-all duration-300 p-5 overflow-hidden flex items-start gap-3" style="animation-delay:80ms">
                    <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-600 to-violet-700 text-white shadow-lg"><Info class="h-5 w-5" /></span>
                    <div class="relative">
                        <p class="text-sm font-black text-gray-900 dark:text-white">CEO‑Only Section</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Only the CEO can manage Logistics access. Secretary and Special Officers need explicit permission to view the Logistics module.
                        </p>
                    </div>
                </div>

                <!-- Access Table -->
                <div class="animate-fade-up bg-white dark:bg-zinc-900 rounded-3xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden hover:shadow-xl transition-shadow duration-300" style="animation-delay:120ms">
                    <div class="p-6 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-indigo-50 to-transparent dark:from-indigo-900/20 flex items-center gap-2">
                        <ShieldCheck class="h-5 w-5 text-indigo-600" />
                        <div>
                            <h2 class="text-sm font-black uppercase tracking-widest">User Permissions</h2>
                            <p class="text-xs text-gray-500">Toggle access for the Logistics Dashboard, Load, Dispatch, Fleet, and all sub‑pages.</p>
                        </div>
                        <span class="ml-auto rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-[11px] font-black px-2.5 py-1">{{ users.length }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-zinc-800">
                                <tr>
                                    <th class="px-8 py-4">User</th>
                                    <th class="px-8 py-4">Role / Position</th>
                                    <th class="px-8 py-4 text-center">Logistics Access</th>
                                    <th class="px-8 py-4 text-right"></th>
                                </tr>
                            </thead>
                            <TransitionGroup name="card" tag="tbody" class="divide-y divide-gray-50 dark:divide-zinc-800">
                                <tr v-for="(user, i) in users" :key="user.id" :style="{ transitionDelay: `${Math.min(i * 40, 400)}ms` }" class="group hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-all">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 flex items-center justify-center text-white text-sm font-black uppercase shadow-lg group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                                                {{ user.name.charAt(0) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-black text-gray-900 dark:text-white truncate group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors">{{ user.name }}</p>
                                                <p class="text-[10px] text-gray-400 truncate">{{ user.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="px-2.5 py-1 rounded-full bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-zinc-700 text-[9px] font-black uppercase inline-flex items-center gap-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                            {{ user.role }} · {{ user.position }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <div class="flex justify-center">
                                            <label v-if="canEditAccess" class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox"
                                                    :checked="accessState[user.id] || false"
                                                    @change="toggleAccess(user)"
                                                    class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-indigo-600"></div>
                                                <span class="ml-3 text-xs font-bold" :class="accessState[user.id] ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400'">
                                                    {{ accessState[user.id] ? 'Enabled' : 'Disabled' }}
                                                </span>
                                            </label>
                                            <span v-else class="text-xs font-bold" :class="accessState[user.id] ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400'">
                                                {{ accessState[user.id] ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div v-if="updatingUserId === user.id" class="flex justify-end">
                                            <Loader2 class="h-4 w-4 animate-spin text-indigo-600" />
                                        </div>
                                    </td>
                                 </tr>
                            </TransitionGroup>
                        </table>
                        <div v-if="users.length === 0" class="px-8 py-20 text-center">
                            <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-4 inline-flex animate-bounce-soft">
                                <ShieldCheck class="h-9 w-9 text-indigo-400" />
                            </div>
                            <p class="text-sm font-black text-gray-700 dark:text-gray-200">No eligible users found.</p>
                            <p class="text-xs text-gray-400 mt-1">Only Secretary and Special Officer accounts appear here.</p>
                        </div>
                    </div>
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
import { ref, reactive, computed } from 'vue';
import { ShieldCheck, RefreshCw, Info, Loader2 } from 'lucide-vue-next';
import { usePageAccess } from '@/composables/usePageAccess';

const { canEdit } = usePageAccess();
const canEditAccess = computed(() => canEdit('LOG', 'access'));

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    access: {
        type: Object, // { user_id: boolean }
        default: () => ({})
    }
});

// Local copy of access states
const accessState = reactive({ ...props.access });
const updatingUserId = ref(null);
const toast = ref({ show: false, type: 'success', message: '' });

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const refreshData = () => {
    router.reload({ only: ['users', 'access'] });
};

const toggleAccess = async (user) => {
    const newValue = !accessState[user.id];
    updatingUserId.value = user.id;

    try {
        await router.post(route('logistics.access.update'), {
            user_id: user.id,
            can_access: newValue
        }, {
            preserveScroll: true,
            onSuccess: () => {
                accessState[user.id] = newValue;
                showToast('success', `${user.name} access ${newValue ? 'granted' : 'revoked'}.`);
            },
            onError: (errors) => {
                showToast('error', Object.values(errors)[0] || 'Update failed.');
                // revert local state
                accessState[user.id] = !newValue;
            },
            onFinish: () => {
                updatingUserId.value = null;
            }
        });
    } catch (error) {
        showToast('error', 'Network error. Please try again.');
        accessState[user.id] = !newValue;
        updatingUserId.value = null;
    }
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
.modal-enter-active, .modal-leave-active { transition: all 0.3s cubic-bezier(0.22,1,0.36,1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95) translateY(12px); }
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>
