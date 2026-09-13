<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePageAccess } from '@/composables/usePageAccess';
import { Search, Plus, X, Send, Trash2, Clock } from 'lucide-vue-next';

const { canEdit } = usePageAccess();
const canEditTickets = computed(() => canEdit('IT', 'tickets'));

const props = defineProps({
    tickets: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
    itStaff: { type: Array, default: () => [] },
    isManager: { type: Boolean, default: false },
});

const showCreate = ref(false);
const selected = ref(null);
const commentBody = ref('');
const commentInternal = ref(false);

const form = useForm({
    title: '', description: '', category: 'incident',
    system_area: 'erp', priority: 'P3', location: '',
    requester_name: '', assignee_id: '',
});

const editForm = useForm({ status: '', assignee_id: '', priority: '' });

const openDetail = (ticket) => {
    selected.value = ticket;
    editForm.status = ticket.status;
    editForm.assignee_id = ticket.assignee_id || '';
    editForm.priority = ticket.priority;
    commentBody.value = '';
};

const submitTicket = () => {
    form.post(route('it.tickets.store'), {
        preserveScroll: true,
        onSuccess: () => { showCreate.value = false; form.reset(); },
    });
};

const saveTicket = () => {
    if (!selected.value) return;
    editForm.put(route('it.tickets.update', selected.value.id), {
        preserveScroll: true,
        onSuccess: () => { selected.value = null; },
    });
};

const postComment = () => {
    if (!selected.value || !commentBody.value.trim()) return;
    router.post(route('it.tickets.comment', selected.value.id), {
        body: commentBody.value,
        is_internal: commentInternal.value,
    }, { preserveScroll: true, onSuccess: () => { commentBody.value = ''; } });
};

const deleteTicket = (id) => {
    if (!confirm('Delete this ticket? Comments will be removed too.')) return;
    router.delete(route('it.tickets.destroy', id), { preserveScroll: true });
};

const applyFilters = (patch) => {
    router.get(route('it.tickets'), { ...props.filters, ...patch }, { preserveScroll: true, replace: true });
};

const priorityClass = (p) => ({
    P1: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    P2: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    P3: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    P4: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
}[p]);

const statusClass = (s) => ({
    open: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    assigned: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
    in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    resolved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    closed: 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400',
    reopened: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
}[s]);

const rows = computed(() => props.tickets.data || []);
const selectedFresh = computed(() => rows.value.find(t => selected.value && t.id === selected.value.id) || selected.value);
</script>

<template>
    <Head title="Service Desk | IT Department" />
    <AuthenticatedLayout>
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    Service <span class="text-blue-600">Desk</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Incidents & service requests with SLA tracking.</p>
            </div>
            <button v-if="canEditTickets" @click="showCreate = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-500/30 active:scale-95">
                <Plus class="w-4 h-4" /> Log Ticket
            </button>
            <span v-else class="text-xs font-bold text-amber-600 bg-amber-50 px-3 py-1.5 rounded-full">View only</span>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex flex-col lg:flex-row gap-3 bg-slate-50/50 dark:bg-slate-800/50">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input :value="filters.search || ''" @input="applyFilters({ search: $event.target.value })" type="text"
                        placeholder="Search ticket no, title, location..."
                        class="w-full pl-9 pr-4 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 placeholder-slate-400" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <select :value="filters.status || ''" @change="applyFilters({ status: $event.target.value })"
                        class="px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200">
                        <option value="">All statuses</option>
                        <option value="open">Open queue</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="pending">Pending</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                    <select :value="filters.priority || ''" @change="applyFilters({ priority: $event.target.value })"
                        class="px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200">
                        <option value="">All priorities</option>
                        <option value="P1">P1 Critical</option>
                        <option value="P2">P2 High</option>
                        <option value="P3">P3 Normal</option>
                        <option value="P4">P4 Low</option>
                    </select>
                    <select :value="filters.category || ''" @change="applyFilters({ category: $event.target.value })"
                        class="px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200">
                        <option value="">Incidents + Requests</option>
                        <option value="incident">Incidents</option>
                        <option value="service_request">Service Requests</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/40 border-b border-slate-100 dark:border-slate-700">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Ticket</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Priority</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Assignee</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">SLA Due</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr v-for="t in rows" :key="t.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60">
                            <td class="px-6 py-4">
                                <button @click="openDetail(t)" class="text-left">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white hover:text-blue-600">{{ t.ticket_no }} — {{ t.title }}</p>
                                    <p class="text-[11px] text-slate-400">{{ t.category.replace('_', ' ') }} · {{ t.system_area }} · 💬 {{ t.comments_count ?? 0 }}</p>
                                </button>
                            </td>
                            <td class="px-6 py-4"><span :class="['px-2 py-0.5 rounded-full text-[10px] font-black', priorityClass(t.priority)]">{{ t.priority }}</span></td>
                            <td class="px-6 py-4"><span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase', statusClass(t.status)]">{{ t.status.replace('_', ' ') }}</span></td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ t.assignee?.name || '—' }}</td>
                            <td class="px-6 py-4">
                                <span v-if="t.sla_due_at" class="inline-flex items-center gap-1 text-xs font-mono"
                                    :class="t.sla_due_at && new Date(t.sla_due_at) < new Date() && ['open','assigned','in_progress','pending','reopened'].includes(t.status) ? 'text-red-500 font-bold' : 'text-slate-500'">
                                    <Clock class="w-3.5 h-3.5" /> {{ new Date(t.sla_due_at).toLocaleString() }}
                                </span>
                                <span v-else class="text-xs text-slate-400">—</span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <button @click="openDetail(t)" class="px-3 py-1.5 text-xs font-bold text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg">Open</button>
                                <button v-if="isManager && canEditTickets" @click="deleteTicket(t.id)" class="p-1.5 text-slate-400 hover:text-red-500"><Trash2 class="w-4 h-4" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="rows.length === 0" class="py-12 text-center text-sm text-slate-400 font-bold">No tickets match.</div>
            </div>
        </div>

        <!-- Create modal -->
        <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Log Ticket</h2>
                    <button @click="showCreate = false" class="p-1.5 text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="submitTicket" class="space-y-4">
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Title</label>
                        <input v-model="form.title" type="text" required placeholder="e.g. Label printer offline at Packaging Stn 2"
                            class="mt-1 w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/30 text-slate-700 dark:text-slate-200" />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Description</label>
                        <textarea v-model="form.description" required rows="3" placeholder="What happened, since when, business impact..."
                            class="mt-1 w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/30 text-slate-700 dark:text-slate-200"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Category</label>
                            <select v-model="form.category" class="mt-1 w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200">
                                <option value="incident">Incident</option>
                                <option value="service_request">Service Request</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Priority</label>
                            <select v-model="form.priority" class="mt-1 w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200">
                                <option value="P1">P1 — Critical / plant-stopping</option>
                                <option value="P2">P2 — High</option>
                                <option value="P3">P3 — Normal</option>
                                <option value="P4">P4 — Low</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">System Area</label>
                            <select v-model="form.system_area" class="mt-1 w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200">
                                <option value="erp">ERP / MontiERP</option>
                                <option value="network">Network / WiFi</option>
                                <option value="hardware">Hardware</option>
                                <option value="software">Software</option>
                                <option value="plant_ot">Plant OT / Floor Systems</option>
                                <option value="peripheral">Printers / Scanners</option>
                                <option value="access">Access / Accounts</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Location</label>
                            <input v-model="form.location" type="text" placeholder="e.g. Dyeing floor, Lab terminal 2"
                                class="mt-1 w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Caller (if phone-in)</label>
                            <input v-model="form.requester_name" type="text" placeholder="Name of plant caller"
                                class="mt-1 w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200" />
                        </div>
                        <div v-if="isManager">
                            <label class="text-xs font-bold text-slate-500 uppercase">Assign To</label>
                            <select v-model="form.assignee_id" class="mt-1 w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200">
                                <option value="">Unassigned</option>
                                <option v-for="u in itStaff" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" :disabled="form.processing"
                        class="w-full py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition active:scale-95 disabled:opacity-50">
                        Log Ticket
                    </button>
                </form>
            </div>
        </div>

        <!-- Detail drawer -->
        <div v-if="selectedFresh" class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-sm" @click.self="selected = null">
            <div class="bg-white dark:bg-slate-800 w-full max-w-md h-full overflow-y-auto p-6 border-l border-slate-200 dark:border-slate-700">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="text-[11px] font-mono font-bold text-slate-400">{{ selectedFresh.ticket_no }}</p>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">{{ selectedFresh.title }}</h2>
                    </div>
                    <button @click="selected = null" class="p-1.5 text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line mb-4">{{ selectedFresh.description }}</p>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Status</label>
                        <select v-model="editForm.status" :disabled="!canEditTickets" class="mt-1 w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 disabled:opacity-60">
                            <option value="open">Open</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                            <option value="reopened">Reopened</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase">Priority</label>
                        <select v-model="editForm.priority" :disabled="!canEditTickets" class="mt-1 w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 disabled:opacity-60">
                            <option value="P1">P1</option><option value="P2">P2</option>
                            <option value="P3">P3</option><option value="P4">P4</option>
                        </select>
                    </div>
                    <div class="col-span-2" v-if="isManager">
                        <label class="text-xs font-bold text-slate-500 uppercase">Assignee</label>
                        <select v-model="editForm.assignee_id" :disabled="!canEditTickets" class="mt-1 w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 disabled:opacity-60">
                            <option value="">Unassigned</option>
                            <option v-for="u in itStaff" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                </div>
                <button v-if="canEditTickets" @click="saveTicket" :disabled="editForm.processing"
                    class="w-full py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition active:scale-95 disabled:opacity-50 mb-6">
                    Save Changes
                </button>
                <p v-else class="mb-6 text-xs font-bold text-amber-600 bg-amber-50 px-3 py-2 rounded-xl">View only — status changes disabled.</p>

                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Work Log</h3>
                <div class="space-y-2 mb-3">
                    <div v-for="c in (selectedFresh.comments || [])" :key="c.id"
                        :class="['p-3 rounded-xl text-sm', c.is_internal ? 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' : 'bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700']">
                        <p class="text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ c.body }}</p>
                        <p class="text-[10px] text-slate-400 mt-1">{{ c.user?.name }} · {{ new Date(c.created_at).toLocaleString() }}{{ c.is_internal ? ' · internal' : '' }}</p>
                    </div>
                    <p v-if="!(selectedFresh.comments || []).length" class="text-xs text-slate-400">No updates yet.</p>
                </div>
                <div v-if="canEditTickets" class="flex gap-2">
                    <input v-model="commentBody" type="text" placeholder="Post an update..." @keyup.enter="postComment"
                        class="flex-1 px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200" />
                    <button @click="postComment" class="p-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700"><Send class="w-4 h-4" /></button>
                </div>
                <label v-if="isManager" class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                    <input type="checkbox" v-model="commentInternal" class="rounded" /> Internal note
                </label>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
