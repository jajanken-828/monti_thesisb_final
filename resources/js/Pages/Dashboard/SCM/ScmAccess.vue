<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ShieldCheck, Sparkles, Users, Clock } from 'lucide-vue-next';

const props = defineProps({ users: Array });

const toggleAccess = (userId, currentAccess) => {
    router.post(route('scm.access.update'), { user_id: userId, can_access_scm: !currentAccess }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="SCM Access Control" />
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="p-4 sm:p-6 max-w-4xl mx-auto space-y-6 pb-16">

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
                                <Sparkles class="h-3.5 w-3.5" /> SCM · Permissions
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Module Access Control</h1>
                            <p class="text-sm text-blue-100/90">{{ users.filter(u => u.can_access_scm).length }} of {{ users.length }} users with SCM access</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 animate-pulse" /> {{ users.length }} users
                            </span>
                        </div>
                    </div>
                </div>

                <div class="animate-fade-up bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 transition-all duration-300 overflow-hidden" style="animation-delay:120ms">
                    <div class="p-6 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-indigo-50 to-transparent dark:from-indigo-900/20 flex items-center gap-2">
                        <Users class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                        <h2 class="text-sm font-black uppercase tracking-widest text-gray-900 dark:text-white">User Access</h2>
                        <span class="ml-auto rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-[11px] font-black px-2.5 py-1">{{ users.length }}</span>
                    </div>

                    <div v-if="!users.length" class="flex flex-col items-center justify-center py-20 text-center">
                        <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-4 animate-bounce-soft">
                            <Users class="h-9 w-9 text-indigo-400" />
                        </div>
                        <p class="text-sm font-black text-gray-700 dark:text-gray-200">No users found</p>
                        <p class="text-xs text-gray-400 mt-1">Users will appear here once registered.</p>
                    </div>

                    <!-- Desktop table -->
                    <div v-else class="overflow-x-auto hidden sm:block">
                        <table class="w-full text-sm text-left">
                            <thead class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-zinc-800">
                                <tr>
                                    <th class="px-6 py-4">Name</th>
                                    <th class="px-6 py-4">Position</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-center">Access</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                                <tr v-for="(user, i) in users" :key="user.id" class="hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-blue-600 to-cyan-600 text-white text-sm font-black shadow-lg uppercase shrink-0">
                                                {{ (user.name ?? '?').charAt(0) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-bold text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ user.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400 font-medium">{{ user.position }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="user.can_access_scm ? 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30' : 'bg-gray-100 text-gray-500 ring-gray-200 dark:bg-zinc-800 dark:text-gray-400 dark:ring-zinc-700'"
                                            class="px-2.5 py-1 rounded-full ring-1 text-[9px] font-black uppercase inline-flex items-center gap-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" /> {{ user.can_access_scm ? 'Granted' : 'Denied' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button @click="toggleAccess(user.id, user.can_access_scm)" :class="user.can_access_scm ? 'bg-emerald-500 shadow-emerald-500/30' : 'bg-gray-300 dark:bg-zinc-700'" class="relative inline-flex h-6 w-11 items-center rounded-full transition shadow-lg">
                                            <span :class="user.can_access_scm ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile cards -->
                    <TransitionGroup name="card" tag="div" class="sm:hidden divide-y divide-gray-50 dark:divide-zinc-800">
                        <div v-for="(user, i) in users" :key="user.id" :style="{ transitionDelay: `${Math.min(i * 40, 400)}ms` }"
                            class="group flex items-center gap-3 p-4 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-blue-600 to-cyan-600 text-white text-sm font-black shadow-lg uppercase shrink-0 group-hover:scale-110 transition-transform">
                                {{ (user.name ?? '?').charAt(0) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-sm text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                                <p class="text-[11px] text-gray-400 truncate">{{ user.position }}</p>
                                <span :class="user.can_access_scm ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-gray-100 text-gray-500 dark:bg-zinc-800 dark:text-gray-400'"
                                    class="mt-1 px-2 py-0.5 rounded-full text-[9px] font-black uppercase inline-flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" /> {{ user.can_access_scm ? 'Granted' : 'Denied' }}
                                </span>
                            </div>
                            <button @click="toggleAccess(user.id, user.can_access_scm)" :class="user.can_access_scm ? 'bg-emerald-500 shadow-emerald-500/30' : 'bg-gray-300 dark:bg-zinc-700'" class="relative inline-flex h-6 w-11 items-center rounded-full transition shadow-lg shrink-0">
                                <span :class="user.can_access_scm ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition" />
                            </button>
                        </div>
                    </TransitionGroup>
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
.modal-enter-active, .modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
