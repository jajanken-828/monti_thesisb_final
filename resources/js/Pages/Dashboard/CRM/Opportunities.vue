<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { usePageAccess } from '@/composables/usePageAccess';
import { KanbanSquare, Plus, X, ArrowRight, Trophy, ThumbsDown } from 'lucide-vue-next';

const { canEdit } = usePageAccess();
const canEditOpp = computed(() => canEdit('CRM', 'opportunities'));

const props = defineProps({
    opportunities: Array, byStage: Object, forecast: Number,
    clients: Array, openLeads: Array,
});

const stages = [
    { key: 'qualification', label: 'Qualification' },
    { key: 'sampling', label: 'Sampling' },
    { key: 'quotation', label: 'Quotation' },
    { key: 'negotiation', label: 'Negotiation' },
    { key: 'won', label: 'Won' },
    { key: 'lost', label: 'Lost' },
];

const inStage = (s) => (props.opportunities || []).filter((o) => o.stage === s);
const peso = (v) => '₱' + Number(v || 0).toLocaleString('en-PH', { maximumFractionDigits: 0 });

const showNew = ref(false);
const createForm = useForm({ client_id: '', lead_id: '', title: '', value: 0, expected_close: '' });
const submitNew = () => {
    createForm.post(route('crm.opportunities.store'), {
        preserveScroll: true,
        onSuccess: () => { showNew.value = false; createForm.reset(); },
    });
};

const moving = ref(null);
const moveNotes = ref('');
const lostReason = ref('');
const startMove = (opp, stage) => { moving.value = { opp, stage }; moveNotes.value = ''; lostReason.value = ''; };
const confirmMove = () => {
    if (!moving.value) return;
    router.post(route('crm.opportunities.move', moving.value.opp.id), {
        stage: moving.value.stage, notes: moveNotes.value, lost_reason: lostReason.value,
    }, { preserveScroll: true, onSuccess: () => { moving.value = null; } });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Opportunities" />
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="p-4 sm:p-6 max-w-[1500px] mx-auto space-y-6 pb-16">

                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                    <div class="relative flex flex-wrap items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg animate-pop">
                            <KanbanSquare class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">CRM · Deal Pipeline</p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Opportunities</h1>
                            <p class="text-sm text-blue-100/90">Weighted forecast <strong>{{ peso(forecast) }}</strong> across open deals.</p>
                            <span v-if="!canEditOpp" class="mt-2 inline-block text-xs font-bold text-amber-700 bg-amber-50 px-3 py-1.5 rounded-full">View only</span>
                        </div>
                        <button v-if="canEditOpp" @click="showNew = true"
                            class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-2.5 text-xs font-black uppercase tracking-wide text-indigo-700 shadow-lg hover:scale-105 active:scale-95 transition">
                            <Plus class="h-4 w-4" /> New Deal
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 items-start">
                    <div v-for="s in stages" :key="s.key"
                        class="rounded-3xl border border-gray-100 dark:border-zinc-800 bg-white/70 dark:bg-zinc-900/70 backdrop-blur p-3 min-h-[220px]">
                        <div class="px-1 pb-3">
                            <p class="text-[11px] font-black uppercase tracking-widest">{{ s.label }}</p>
                            <p class="text-[10px] font-bold text-gray-400">
                                {{ byStage?.[s.key]?.count ?? 0 }} deals · {{ peso(byStage?.[s.key]?.value) }}
                                <span class="text-indigo-500">· {{ byStage?.[s.key]?.probability }}%</span>
                            </p>
                        </div>
                        <div class="space-y-2.5">
                            <div v-for="o in inStage(s.key)" :key="o.id"
                                class="rounded-2xl border border-gray-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3.5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all">
                                <Link :href="route('crm.opportunities.show', o.id)" class="block font-black text-sm hover:text-indigo-600 truncate">{{ o.title }}</Link>
                                <p class="text-[11px] font-bold text-gray-400 truncate mt-0.5">{{ o.client?.company_name || o.lead?.company_name || '—' }}</p>
                                <p class="mt-1.5 text-sm font-black text-indigo-600">{{ peso(o.value) }}</p>
                                <p class="text-[10px] font-bold text-gray-400">{{ o.owner || 'Unassigned' }}{{ o.expected_close ? ' · closes ' + o.expected_close : '' }}</p>
                                <div v-if="canEditOpp && o.allowed?.length" class="mt-2.5 flex flex-wrap gap-1.5">
                                    <button v-for="nx in o.allowed" :key="nx" @click="startMove(o, nx)"
                                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 text-[9px] font-black uppercase text-indigo-700 dark:text-indigo-300 hover:bg-indigo-600 hover:text-white transition">
                                        <template v-if="nx === 'won'"><Trophy class="h-3 w-3" /> Won</template>
                                        <template v-else-if="nx === 'lost'"><ThumbsDown class="h-3 w-3" /> Lost</template>
                                        <template v-else>{{ nx }} <ArrowRight class="h-3 w-3" /></template>
                                    </button>
                                </div>
                            </div>
                            <p v-if="!inStage(s.key).length" class="text-center text-[10px] font-bold uppercase tracking-widest text-gray-300 dark:text-zinc-600 py-6">Empty</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- New deal modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showNew" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showNew = false">
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-violet-800 px-6 py-5 text-white flex justify-between items-center">
                            <h3 class="font-black text-lg">Open Deal</h3>
                            <button @click="showNew = false"><X class="h-5 w-5" /></button>
                        </div>
                        <form @submit.prevent="submitNew" class="p-6 space-y-4">
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Title *</label>
                                <input v-model="createForm.title" required placeholder="e.g. Holiday collection — 2,000kg"
                                    class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 outline-none text-sm dark:text-white" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Account</label>
                                    <select v-model="createForm.client_id" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent outline-none text-sm dark:text-white">
                                        <option value="">—</option>
                                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company_name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Lead</label>
                                    <select v-model="createForm.lead_id" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent outline-none text-sm dark:text-white">
                                        <option value="">—</option>
                                        <option v-for="l in openLeads" :key="l.id" :value="l.id">{{ l.company_name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Value (₱) *</label>
                                    <input v-model.number="createForm.value" type="number" min="0" step="0.01" required
                                        class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent outline-none text-sm dark:text-white" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Expected close</label>
                                    <input v-model="createForm.expected_close" type="date"
                                        class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent outline-none text-sm dark:text-white" />
                                </div>
                            </div>
                            <p v-if="createForm.errors.client_id" class="text-[11px] font-bold text-rose-500">{{ createForm.errors.client_id }}</p>
                            <button :disabled="createForm.processing" class="w-full py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-xs font-black uppercase shadow-lg disabled:opacity-50 hover:scale-[1.01] active:scale-95 transition-all">
                                Open in Qualification
                            </button>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Move confirm modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="moving" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="moving = null">
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
                        <div class="px-6 pt-6 pb-2 text-center">
                            <h3 class="font-black">Move to {{ moving.stage }}?</h3>
                            <p class="text-xs text-gray-400 mt-1">{{ moving.opp.title }} · {{ peso(moving.opp.value) }}</p>
                        </div>
                        <div class="p-6 space-y-3">
                            <input v-if="moving.stage === 'lost'" v-model="lostReason" placeholder="Lost reason (required)…"
                                class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent outline-none text-sm dark:text-white" />
                            <input v-model="moveNotes" placeholder="Note (optional)…"
                                class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent outline-none text-sm dark:text-white" />
                            <div class="flex gap-2">
                                <button @click="moving = null" class="flex-1 py-3 rounded-xl bg-gray-100 dark:bg-zinc-800 text-xs font-black uppercase text-gray-500">Cancel</button>
                                <button @click="confirmMove" :disabled="moving.stage === 'lost' && !lostReason.trim()"
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
