<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePageAccess } from '@/composables/usePageAccess';
import { KanbanSquare, ChevronLeft, ArrowRight, Trophy, ThumbsDown, History, ListChecks } from 'lucide-vue-next';

const { canEdit } = usePageAccess();
import { computed } from 'vue';
const canEditOpp = computed(() => canEdit('CRM', 'opportunities'));

const props = defineProps({ opportunity: Object });
const o = props.opportunity;

const peso = (v) => '₱' + Number(v || 0).toLocaleString('en-PH', { maximumFractionDigits: 0 });
const fdate = (d) => d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';

const moving = ref(null);
const moveNotes = ref('');
const lostReason = ref('');
const startMove = (stage) => { moving.value = stage; moveNotes.value = ''; lostReason.value = ''; };
const confirmMove = () => {
    router.post(route('crm.opportunities.move', o.id), {
        stage: moving.value, notes: moveNotes.value, lost_reason: lostReason.value,
    }, { preserveScroll: true, onSuccess: () => { moving.value = null; } });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="o.title" />
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="p-4 sm:p-6 max-w-5xl mx-auto space-y-6 pb-16">

                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                    <div class="relative flex flex-wrap items-center gap-4">
                        <Link :href="route('crm.opportunities')" class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 hover:bg-white/25 transition"><ChevronLeft class="h-5 w-5" /></Link>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">Deal · {{ o.stage }}</p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight truncate">{{ o.title }}</h1>
                            <p class="text-sm text-blue-100/90">{{ o.client?.company_name || o.lead?.company_name || '—' }} · owner {{ o.owner?.name || '—' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-black">{{ peso(o.value) }}</p>
                            <p class="text-xs font-bold text-blue-100">weighted {{ peso(o.weighted) }} · {{ o.probability }}%</p>
                        </div>
                    </div>
                    <div v-if="canEditOpp && o.allowed?.length" class="relative mt-5 flex flex-wrap gap-2">
                        <button v-for="nx in o.allowed" :key="nx" @click="startMove(nx)"
                            class="inline-flex items-center gap-1.5 rounded-2xl bg-white px-4 py-2.5 text-xs font-black uppercase text-indigo-700 shadow-lg hover:scale-105 active:scale-95 transition">
                            <Trophy v-if="nx === 'won'" class="h-3.5 w-3.5" />
                            <ThumbsDown v-else-if="nx === 'lost'" class="h-3.5 w-3.5" />
                            <ArrowRight v-else class="h-3.5 w-3.5" /> {{ nx }}
                        </button>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="rounded-3xl border border-gray-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 shadow-sm">
                        <h3 class="flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3"><KanbanSquare class="h-4 w-4 text-indigo-500" /> Deal facts</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-400 font-bold">Stage</dt><dd class="font-black uppercase">{{ o.stage }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-400 font-bold">Expected close</dt><dd class="font-black">{{ fdate(o.expected_close) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-400 font-bold">Opened</dt><dd class="font-black">{{ fdate(o.created_at) }}</dd></div>
                            <div v-if="o.lost_reason" class="rounded-xl bg-rose-50 dark:bg-rose-900/10 p-3"><dt class="text-[10px] font-black uppercase text-rose-500">Lost reason</dt><dd class="font-bold text-rose-700 dark:text-rose-300">{{ o.lost_reason }}</dd></div>
                        </dl>
                    </div>
                    <div class="rounded-3xl border border-gray-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 shadow-sm">
                        <h3 class="flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3"><ListChecks class="h-4 w-4 text-emerald-500" /> Linked activities</h3>
                        <div v-if="o.activities?.length" class="space-y-2">
                            <div v-for="a in o.activities" :key="a.id" class="rounded-xl bg-slate-50 dark:bg-zinc-800 px-3 py-2 text-xs">
                                <p class="font-black">{{ a.subject }}</p>
                                <p class="text-gray-400">{{ a.type }} · {{ a.owner?.name }} · {{ a.done_at ? 'done' : 'open' }}</p>
                            </div>
                        </div>
                        <p v-else class="text-xs font-bold text-gray-400">No activities yet — log them from Activities.</p>
                    </div>
                </div>

                <div class="rounded-3xl border border-gray-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 shadow-sm">
                    <h3 class="flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3"><History class="h-4 w-4 text-amber-500" /> Stage history</h3>
                    <ol class="relative ml-2 space-y-4 border-l-2 border-indigo-100 dark:border-indigo-900/40 pl-5">
                        <li v-for="h in o.histories" :key="h.id" class="relative text-xs">
                            <span class="absolute -left-[27px] top-1 h-3 w-3 rounded-full bg-indigo-500 ring-4 ring-white dark:ring-zinc-900" />
                            <p class="font-black">{{ h.from_stage || 'opened' }} → {{ h.to_stage }}</p>
                            <p class="text-gray-400">{{ h.changer?.name }} · {{ fdate(h.created_at) }}{{ h.notes ? ` — ${h.notes}` : '' }}</p>
                        </li>
                        <li v-if="!o.histories?.length" class="text-xs font-bold text-gray-400">No moves recorded.</li>
                    </ol>
                </div>

            </div>
        </div>

        <Teleport to="body">
            <Transition name="modal">
                <div v-if="moving" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="moving = null">
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
                        <div class="px-6 pt-6 pb-2 text-center">
                            <h3 class="font-black">Move to {{ moving }}?</h3>
                            <p class="text-xs text-gray-400 mt-1">{{ o.title }}</p>
                        </div>
                        <div class="p-6 space-y-3">
                            <input v-if="moving === 'lost'" v-model="lostReason" placeholder="Lost reason (required)…"
                                class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent outline-none text-sm dark:text-white" />
                            <input v-model="moveNotes" placeholder="Note (optional)…"
                                class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent outline-none text-sm dark:text-white" />
                            <div class="flex gap-2">
                                <button @click="moving = null" class="flex-1 py-3 rounded-xl bg-gray-100 dark:bg-zinc-800 text-xs font-black uppercase text-gray-500">Cancel</button>
                                <button @click="confirmMove" :disabled="moving === 'lost' && !lostReason.trim()"
                                    class="flex-1 py-3 rounded-xl bg-indigo-600 text-white text-xs font-black uppercase disabled:opacity-50 hover:bg-indigo-700">Confirm</button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-active > div, .modal-leave-active > div { transition: transform 0.3s cubic-bezier(0.22,1,0.36,1), opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div, .modal-leave-to > div { opacity: 0; transform: scale(0.95) translateY(10px); }
</style>
