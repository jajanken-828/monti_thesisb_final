<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { FlaskConical, Sparkles, PlusCircle, ClipboardList, Beaker, ArrowRight, X } from 'lucide-vue-next';
import { usePageAccess } from '@/composables/usePageAccess';

const { canEdit } = usePageAccess();
const canEditProduction = computed(() => canEdit('MAN', 'production'));

const props = defineProps({ dips: Array, jobOrders: Array });

const showDipForm = ref(false);
const dipForm = useForm({ sales_order_id: '', customer_ref: '', pantone_code: '', rgb_lab_values: '', swatch_id: '', fabric_details: '', urgency: 'normal', remarks: '' });
const submitDip = () => {
    dipForm.transform((d) => ({ ...d, sales_order_id: d.sales_order_id || null }))
        .post(route('man.staff.dyeing-lab-chemist.store-dip'), { preserveScroll: true, onSuccess: () => { showDipForm.value = false; dipForm.reset(); } });
};

const expanded = ref(null);
const trialForm = useForm({ dip_request_id: null, dyestuffs: [{ name: '', pct: '' }], auxiliaries: [{ name: '', gpl: '' }], liquor_ratio: '1:10', curve: '', adjustments: '', delta_e: '', status: 'pending' });
const openTrial = (dip) => { expanded.value = dip.id; trialForm.dip_request_id = dip.id; trialForm.reset(); trialForm.dip_request_id = dip.id; };
const addRow = (list) => list.push(list === trialForm.dyestuffs ? { name: '', pct: '' } : { name: '', gpl: '' });
const removeRow = (list, i) => list.splice(i, 1);
const submitTrial = () => trialForm.transform((data) => {
    let curve = [];
    if (typeof data.curve === 'string' && data.curve.trim() !== '') {
        try { curve = JSON.parse(data.curve); } catch (e) { curve = [{ note: data.curve }]; }
    } else if (Array.isArray(data.curve)) {
        curve = data.curve;
    }
    return { ...data, curve };
}).post(route('man.staff.dyeing-lab-chemist.store-trial'), { preserveScroll: true, onSuccess: () => { expanded.value = null; } });

const advanceDip = (dip, next) => router.patch(route('man.staff.dyeing-lab-chemist.update-dip-status', dip.id), { status: next }, { preserveScroll: true });

const statusBadge = (s) => s === 'approved'
    ? 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30'
    : s === 'rejected' ? 'bg-rose-100 text-rose-700 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-500/30'
    : s === 'pending' ? 'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30'
    : 'bg-indigo-100 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/30';
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Shade Development" />
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 pb-16">

                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                    <div class="relative flex flex-wrap items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg animate-pop"><FlaskConical class="h-7 w-7" /></div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100"><Sparkles class="h-3.5 w-3.5" /> MAN · Lab Chemist</p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Shade Development</h1>
                            <span v-if="!canEditProduction" class="mt-2 inline-flex w-fit items-center rounded-full bg-amber-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-amber-800">View only</span>
                            <p class="text-sm text-blue-100/90">{{ dips?.length ?? 0 }} open dip requests · trial iterations below</p>
                        </div>
                        <button v-if="canEditProduction" @click="showDipForm = !showDipForm"
                            class="rounded-2xl bg-white px-4 py-2.5 text-xs font-black uppercase tracking-wide text-indigo-700 shadow-lg hover:scale-105 active:scale-95 transition flex items-center gap-1.5">
                            <PlusCircle class="h-4 w-4" /> New Dip Request
                        </button>
                    </div>
                </div>

                <Transition name="modal">
                    <div v-if="showDipForm" class="bg-white/80 dark:bg-zinc-900/80 rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 px-6 py-4"><h2 class="text-lg font-black text-white">Log Lab Dip Request</h2></div>
                        <form @submit.prevent="submitDip" class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-6">
                            <div><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Job Order (optional)</label>
                                <select v-model="dipForm.sales_order_id" class="w-full rounded-2xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">None</option>
                                    <option v-for="o in jobOrders" :key="o.id" :value="o.id">{{ o.jo_number }} · {{ o.color }}</option>
                                </select></div>
                            <div><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Urgency *</label>
                                <select v-model="dipForm.urgency" class="w-full rounded-2xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option>
                                </select></div>
                            <div><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Customer Ref</label>
                                <input v-model="dipForm.customer_ref" class="w-full rounded-2xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500" /></div>
                            <div><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Pantone Code</label>
                                <input v-model="dipForm.pantone_code" placeholder="e.g. PANTONE 19-4052" class="w-full rounded-2xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500" /></div>
                            <div><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">RGB / Lab Values</label>
                                <input v-model="dipForm.rgb_lab_values" placeholder="e.g. L* 32.4 a* 4.1 b* -28.9" class="w-full rounded-2xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500" /></div>
                            <div><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Swatch ID</label>
                                <input v-model="dipForm.swatch_id" class="w-full rounded-2xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500" /></div>
                            <div class="sm:col-span-2"><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Fabric Details</label>
                                <input v-model="dipForm.fabric_details" class="w-full rounded-2xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500" /></div>
                            <div class="sm:col-span-2"><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Remarks</label>
                                <textarea v-model="dipForm.remarks" rows="2" class="w-full rounded-2xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500"></textarea></div>
                            <div class="sm:col-span-2 flex gap-2">
                                <button v-if="canEditProduction" type="submit" :disabled="dipForm.processing || !canEditProduction" class="rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-black uppercase text-white shadow-lg hover:bg-indigo-700 active:scale-95 transition disabled:opacity-50">Save Request</button>
                                <button type="button" @click="showDipForm = false" class="rounded-2xl bg-gray-100 dark:bg-zinc-800 px-4 py-2.5 text-xs font-black uppercase text-gray-600 dark:text-gray-300 transition active:scale-95">Cancel</button>
                            </div>
                        </form>
                    </div>
                </Transition>

                <div v-for="dip in dips" :key="dip.id" class="bg-white/80 dark:bg-zinc-900/80 rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
                    <div class="flex flex-wrap items-center gap-3 px-6 py-4 border-b border-gray-100 dark:border-zinc-800">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 text-white shadow-lg"><Beaker class="h-4 w-4" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-xs font-bold">{{ dip.code }} <span class="font-sans text-[11px] text-gray-400">· {{ dip.urgency }} · {{ dip.pantone_code ?? dip.customer_ref ?? 'no ref' }}</span></p>
                            <p class="text-xs text-gray-500">{{ dip.trials?.length ?? 0 }} trial(s)</p>
                        </div>
                        <span :class="statusBadge(dip.status)" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase ring-1">{{ dip.status.replace('_', ' ') }}</span>
                        <div class="flex gap-1.5">
                            <button v-if="dip.status === 'pending' && canEditProduction" @click="advanceDip(dip, 'in_progress')" class="text-[11px] font-black text-indigo-700 hover:underline">Start</button>
                            <button v-if="dip.status === 'in_progress' && canEditProduction" @click="advanceDip(dip, 'awaiting_spectro')" class="text-[11px] font-black text-indigo-700 hover:underline">To Spectro</button>
                            <button v-if="dip.status === 'awaiting_spectro' && canEditProduction" @click="advanceDip(dip, 'approved')" class="text-[11px] font-black text-emerald-700 hover:underline">Approve</button>
                            <button v-if="['in_progress', 'awaiting_spectro'].includes(dip.status) && canEditProduction" @click="advanceDip(dip, 'rejected')" class="text-[11px] font-black text-rose-500 hover:underline">Reject</button>
                            <button v-if="canEditProduction" @click="openTrial(dip)" class="inline-flex items-center gap-1 text-[11px] font-black text-indigo-700 hover:underline">Trial <ArrowRight class="h-3 w-3" /></button>
                        </div>
                    </div>
                    <div class="px-6 py-3 space-y-2">
                        <div v-for="t in dip.trials" :key="t.id" class="rounded-2xl border border-gray-100 dark:border-zinc-800 px-4 py-2.5 text-sm flex flex-wrap gap-x-4 gap-y-1 items-center">
                            <span class="font-mono font-bold text-xs">Trial {{ t.trial_no }}</span>
                            <span class="text-xs text-gray-500">{{ (t.formula?.dyestuffs ?? []).map(d => `${d.name} ${d.pct ?? ''}%`).join(' · ') || 'no formula' }}</span>
                            <span v-if="t.delta_e" class="text-xs font-bold text-indigo-600">ΔE {{ t.delta_e }}</span>
                            <span :class="statusBadge(t.status === 'passed' ? 'approved' : t.status === 'failed' ? 'rejected' : 'pending')" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase ring-1">{{ t.status }}</span>
                            <span v-if="t.adjustments" class="text-xs text-gray-400 w-full">{{ t.adjustments }}</span>
                        </div>
                        <p v-if="!dip.trials?.length" class="text-xs text-gray-400 py-1">No trials yet — record the first one.</p>
                    </div>
                    <Transition name="modal">
                        <div v-if="expanded === dip.id" class="border-t border-gray-100 dark:border-zinc-800 bg-gray-50/60 dark:bg-zinc-800/40 p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <h3 class="text-sm font-black">Record Trial {{ (dip.trials?.length ?? 0) + 1 }}</h3>
                                <button @click="expanded = null" class="ml-auto text-gray-400 hover:text-gray-600"><X class="h-4 w-4" /></button>
                            </div>
                            <form @submit.prevent="submitTrial" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Dyestuffs (%OWF)</label>
                                    <div v-for="(row, i) in trialForm.dyestuffs" :key="i" class="flex gap-2 mb-2">
                                        <input v-model="row.name" placeholder="Dye name" class="flex-1 rounded-xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500" />
                                        <input v-model="row.pct" type="number" step="0.01" min="0" placeholder="%" class="w-24 rounded-xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500" />
                                        <button type="button" @click="removeRow(trialForm.dyestuffs, i)" class="text-rose-500 px-2"><X class="h-4 w-4" /></button>
                                    </div>
                                    <button type="button" @click="addRow(trialForm.dyestuffs)" class="text-[11px] font-black text-indigo-700 hover:underline">+ Add dyestuff</button>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Auxiliaries (g/L)</label>
                                    <div v-for="(row, i) in trialForm.auxiliaries" :key="i" class="flex gap-2 mb-2">
                                        <input v-model="row.name" placeholder="Chemical name" class="flex-1 rounded-xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500" />
                                        <input v-model="row.gpl" type="number" step="0.01" min="0" placeholder="g/L" class="w-24 rounded-xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500" />
                                        <button type="button" @click="removeRow(trialForm.auxiliaries, i)" class="text-rose-500 px-2"><X class="h-4 w-4" /></button>
                                    </div>
                                    <button type="button" @click="addRow(trialForm.auxiliaries)" class="text-[11px] font-black text-indigo-700 hover:underline">+ Add auxiliary</button>
                                </div>
                                <div><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Liquor Ratio</label>
                                    <input v-model="trialForm.liquor_ratio" placeholder="1:10" class="w-full rounded-xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500" /></div>
                                <div><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">ΔE (measured)</label>
                                    <input v-model="trialForm.delta_e" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500" /></div>
                                <div class="sm:col-span-2"><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Dyeing Curve (steps: temp, rate °C/min, hold, dosing)</label>
                                    <textarea v-model="trialForm.curve" rows="2" placeholder='[{"step":"heat","temp":60,"rate":1.5,"hold":10,"dosing":"salt first"}]' class="w-full font-mono rounded-xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2.5 text-xs outline-none focus:ring-2 focus:ring-indigo-500"></textarea></div>
                                <div class="sm:col-span-2"><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Adjustments vs Previous Trial</label>
                                    <textarea v-model="trialForm.adjustments" rows="2" class="w-full rounded-xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500"></textarea></div>
                                <div><label class="block text-xs font-black uppercase tracking-wide text-gray-500 mb-1.5">Trial Result</label>
                                    <select v-model="trialForm.status" class="w-full rounded-xl border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="pending">Pending</option><option value="passed">Passed</option><option value="failed">Failed</option>
                                    </select></div>
                                <div class="flex items-end"><button v-if="canEditProduction" type="submit" :disabled="trialForm.processing || !canEditProduction" class="rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-black uppercase text-white shadow-lg hover:bg-indigo-700 active:scale-95 transition disabled:opacity-50">Save Trial</button></div>
                            </form>
                        </div>
                    </Transition>
                </div>
                <div v-if="!dips?.length" class="text-center text-sm text-gray-400 py-8">No open dip requests.</div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
@keyframes pop { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
.animate-fade-up { animation: fadeUp 0.6s cubic-bezier(0.22,1,0.36,1) both; }
.animate-float { animation: float 7s ease-in-out infinite; }
.animate-pop { animation: pop 0.5s cubic-bezier(0.22,1,0.36,1) both; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
