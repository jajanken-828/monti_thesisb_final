<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ShieldCheck, Sparkles, Lock, ArrowUpRight, ChevronRight, Users, KeyRound } from 'lucide-vue-next';
// Placeholder component – implement access control logic as needed
</script>

<template>
    <Head title="Procurement Access Control" />
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
                                <Sparkles class="h-3.5 w-3.5" /> PRO · Access Control
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Procurement Access Control</h1>
                            <p class="text-sm text-blue-100/90">CEO-only permission management for the PRO module</p>
                        </div>
                        <span class="rounded-full bg-amber-300/90 px-3 py-1.5 text-xs font-black text-amber-950 flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-900 animate-pulse" /> Restricted
                        </span>
                    </div>
                </div>

                <!-- Placeholder card -->
                <TransitionGroup name="card" tag="div" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div key="main" class="lg:col-span-2 group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 p-8 overflow-hidden text-center">
                        <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                        <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 dark:to-violet-900/20 rounded-full w-fit mx-auto mb-4 animate-bounce-soft">
                            <Lock class="h-9 w-9 text-indigo-400" />
                        </div>
                        <p class="text-sm font-black text-gray-900 dark:text-white tracking-tight">This page is under construction</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">Procurement role permissions (dashboard, quotations, receipt) will be manageable here. Use the manager routes below in the meantime.</p>
                        <div class="mt-6 flex flex-wrap justify-center gap-2">
                            <Link :href="route('pro.manager.material-requests')" class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-blue-700 via-indigo-700 to-violet-700 text-white text-[11px] font-black uppercase tracking-widest shadow-lg shadow-indigo-500/25 active:scale-95 transition">Material Requests</Link>
                            <Link :href="route('pro.manager.supplier-quotations')" class="px-4 py-2.5 rounded-2xl bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-200 text-[11px] font-black uppercase tracking-widest hover:bg-gray-200 transition active:scale-95">Quotations</Link>
                            <Link :href="route('pro.manager.receipt')" class="px-4 py-2.5 rounded-2xl bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-200 text-[11px] font-black uppercase tracking-widest hover:bg-gray-200 transition active:scale-95">Receipts</Link>
                        </div>
                    </div>
                    <div key="side" style="transition-delay:80ms" class="group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1 transition-all duration-300 p-6 overflow-hidden">
                        <div class="absolute left-0 top-5 bottom-5 w-1 bg-gradient-to-b from-indigo-500 to-fuchsia-500 rounded-r-full scale-y-0 group-hover:scale-y-100 transition-transform duration-300" />
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 flex items-center gap-1.5"><Users class="w-3.5 h-3.5 text-indigo-400" /> Planned scopes</p>
                        <div class="mt-3 space-y-2">
                            <div v-for="s in ['Dashboard view', 'Quotations approve', 'Receipts + payments']" :key="s" class="flex items-center gap-2 text-xs font-bold text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-zinc-800/60 rounded-2xl px-3 py-2.5 border border-gray-100 dark:border-zinc-800">
                                <KeyRound class="w-3.5 h-3.5 text-indigo-400" /> {{ s }}
                                <ArrowUpRight class="w-3.5 h-3.5 ml-auto text-gray-300" />
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">CEO only</span>
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-400 group-hover:bg-indigo-600 group-hover:text-white transition-all"><ChevronRight class="h-4 w-4" /></span>
                        </div>
                    </div>
                </TransitionGroup>

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
.modal-enter-active .modal-panel, .modal-leave-active .modal-panel { transition: transform 0.3s cubic-bezier(0.22,1,0.36,1), opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal-panel, .modal-leave-to .modal-panel { opacity: 0; transform: translateY(16px) scale(0.97); }
</style>
