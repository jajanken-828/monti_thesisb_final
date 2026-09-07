<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Plus, DollarSign, Calendar, X, CheckCircle2, AlertCircle,
    HelpCircle, ArrowRight, UserCheck, Building2, FileText, Upload, Clock,
    MessageSquare, Video, MapPin, Download, Eye, Trash2, ChevronDown, ChevronUp,
    ArrowUpRight, Filter, Search, ChevronRight, ArrowRightCircle, MoveRight,
    Check, XCircle
} from 'lucide-vue-next';

const props = defineProps({
    leads: Array,
    permissions: { type: Object, default: () => ({}) }
});

const canEdit = computed(() => props.permissions?.leads === 'edit');

// ─────────────────────────────────────────────────
// Tab State & Lead Grouping
// ─────────────────────────────────────────────────
const activeTab = ref('Inquiry');
const searchQuery = ref('');

const stages = [
    { status: 'Inquiry',      label: 'New Inquiry',   icon: MessageSquare },
    { status: 'Negotiation',  label: 'Negotiation',   icon: Video },
    { status: 'Approval Sent',label: 'Approval Sent', icon: FileText },
    { status: 'Closed-Won',   label: 'Closed-Won',    icon: CheckCircle2 },
];

const leadsByStatus = computed(() => {
    const groups = {};
    stages.forEach(s => groups[s.status] = []);
    (props.leads || []).forEach(lead => {
        if (groups[lead.status] !== undefined) groups[lead.status].push(lead);
    });
    for (const status in groups) {
        groups[status].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    }
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        for (const status in groups) {
            groups[status] = groups[status].filter(lead =>
                lead.company_name.toLowerCase().includes(query) ||
                lead.contact_person.toLowerCase().includes(query) ||
                lead.email.toLowerCase().includes(query)
            );
        }
    }
    return groups;
});

const currentLeads = computed(() => leadsByStatus.value[activeTab.value] || []);

// ─────────────────────────────────────────────────
// Modal & Form State
// ─────────────────────────────────────────────────
const showCreateModal           = ref(false);
const showClientConversionModal = ref(false);
const showNoteModal             = ref(false);
const showInterviewModal        = ref(false);
const showFinalizeModal         = ref(false);
const showMoveConfirmModal      = ref(false);
const currentLead               = ref(null);
const pendingMoveNextStage      = ref(null);

const finalizeFile   = ref(null);
const finalizeAction = ref(null);
const rejectReason   = ref('');
const isUploading    = ref(false);
const isSubmitting   = ref(false);

const form = useForm({
    company_name: '', contact_person: '', email: '', phone: '',
    interest_fabric: 'Cotton', estimated_value: '', logo: null,
});

const conversionForm = useForm({
    lead_id: null, company_name: '', contact_person: '', email: '', phone: '',
    business_type: 'wholesaler', tin_number: '', company_address: '', password: 'password123', logo: null,
});

const logoPreview = ref(null);
const conversionLogoPreview = ref(null);

const handleLogoChange = (e) => {
    const file = e.target.files[0] || null;
    form.logo = file;
    logoPreview.value = file ? URL.createObjectURL(file) : null;
};

const handleConversionLogoChange = (e) => {
    const file = e.target.files[0] || null;
    conversionForm.logo = file;
    conversionLogoPreview.value = file ? URL.createObjectURL(file) : null;
};

const noteForm      = useForm({ note: '' });
const interviewForm = useForm({ scheduled_at: '', location: '', notes: '' });
const rejectForm    = useForm({ reject_reason: '' });

// ─────────────────────────────────────────────────
// Stage Movement
// ─────────────────────────────────────────────────
const getNextStage = (currentStatus) => {
    const order = ['Inquiry', 'Negotiation', 'Approval Sent', 'Closed-Won'];
    const idx = order.indexOf(currentStatus);
    return (idx === -1 || idx === order.length - 1) ? null : order[idx + 1];
};

const openMoveConfirm = (lead) => {
    if (!canEdit.value) return;
    currentLead.value = lead;
    pendingMoveNextStage.value = getNextStage(lead.status);
    showMoveConfirmModal.value = true;
};

const confirmMove = () => {
    if (!currentLead.value || !pendingMoveNextStage.value) return;
    router.patch(route('crm.lead.status', currentLead.value.id), { status: pendingMoveNextStage.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showMoveConfirmModal.value = false;
            currentLead.value = null;
            pendingMoveNextStage.value = null;
            router.reload({ only: ['leads'] });
        }
    });
};

// ─────────────────────────────────────────────────
// Finalization
// ─────────────────────────────────────────────────
const openFinalizeModal = (lead) => {
    if (!canEdit.value) return;
    currentLead.value = lead;
    finalizeFile.value = null;
    finalizeAction.value = null;
    rejectReason.value = '';
    showFinalizeModal.value = true;
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) { alert('File size must be less than 2MB'); return; }
        finalizeFile.value = file;
    }
};

const uploadFinalizeFile = () => {
    if (!finalizeFile.value) return;
    isUploading.value = true;
    const formData = new FormData();
    formData.append('file', finalizeFile.value);
    router.post(route('crm.lead.upload-file', currentLead.value.id), formData, {
        preserveScroll: true,
        onSuccess: () => { finalizeFile.value = null; isUploading.value = false; router.reload({ only: ['leads'] }); },
        onError:   () => { isUploading.value = false; alert('Upload failed'); }
    });
};

const submitFinalize = () => {
    if (!finalizeAction.value) return;
    if (finalizeAction.value === 'accept') {
        router.post(route('crm.lead.accept', currentLead.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => { showFinalizeModal.value = false; currentLead.value = null; router.reload({ only: ['leads'] }); }
        });
    } else if (finalizeAction.value === 'reject') {
        if (!rejectReason.value.trim()) { alert('Please provide a reason for rejection'); return; }
        rejectForm.reject_reason = rejectReason.value;
        rejectForm.post(route('crm.lead.reject', currentLead.value.id), {
            preserveScroll: true,
            onSuccess: () => { showFinalizeModal.value = false; currentLead.value = null; rejectForm.reset(); router.reload({ only: ['leads'] }); }
        });
    }
};

// ─────────────────────────────────────────────────
// Other Modals
// ─────────────────────────────────────────────────
const openNoteModal = (lead) => {
    if (!canEdit.value) return;
    currentLead.value = lead;
    noteForm.note = '';
    showNoteModal.value = true;
};

const openInterviewModal = (lead) => {
    if (!canEdit.value) return;
    currentLead.value = lead;
    interviewForm.reset();
    showInterviewModal.value = true;
};

const closeAllModals = () => {
    showNoteModal.value = false;
    showInterviewModal.value = false;
    showFinalizeModal.value = false;
    showMoveConfirmModal.value = false;
    currentLead.value = null;
    finalizeFile.value = null;
    finalizeAction.value = null;
    rejectReason.value = '';
};

const addNote = () => {
    noteForm.post(route('crm.lead.add-note', currentLead.value.id), {
        preserveScroll: true,
        onSuccess: () => { closeAllModals(); noteForm.reset(); router.reload({ only: ['leads'] }); },
    });
};

const scheduleInterview = () => {
    interviewForm.post(route('crm.lead.schedule-interview', currentLead.value.id), {
        preserveScroll: true,
        onSuccess: () => { closeAllModals(); interviewForm.reset(); router.reload({ only: ['leads'] }); },
    });
};

const openConversionModal = (lead) => {
    if (!canEdit.value) return;
    conversionForm.lead_id        = lead.id;
    conversionForm.company_name   = lead.company_name;
    conversionForm.contact_person = lead.contact_person;
    conversionForm.email          = lead.email;
    conversionForm.phone          = lead.phone;
    showClientConversionModal.value = true;
};

const submitConversion = () => {
    conversionForm.post(route('crm.lead.convert'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { showClientConversionModal.value = false; conversionForm.reset(); conversionLogoPreview.value = null; router.reload({ only: ['leads'] }); },
    });
};

const submit = () => {
    form.post(route('crm.lead.store'), {
        forceFormData: true,
        onSuccess: () => { showCreateModal.value = false; form.reset(); logoPreview.value = null; },
    });
};

// ─────────────────────────────────────────────────
// UI Helpers
// ─────────────────────────────────────────────────
const formatCurrency = (value) =>
    new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value || 0);

const formatDateTime = (date) => new Date(date).toLocaleString();

const showNotes      = ref({});
const showInterviews = ref({});
const toggleNotes      = (id) => { showNotes.value[id]      = !showNotes.value[id]; };
const toggleInterviews = (id) => { showInterviews.value[id] = !showInterviews.value[id]; };

// Stage accent colors
const stageAccent = (status) => ({
    'Inquiry':       { bg: 'bg-blue-600',   light: 'bg-blue-50',   text: 'text-blue-700',   ring: 'ring-blue-200',   dot: 'bg-blue-500'   },
    'Negotiation':   { bg: 'bg-amber-400',  light: 'bg-amber-50',  text: 'text-amber-700',  ring: 'ring-amber-200',  dot: 'bg-amber-400'  },
    'Approval Sent': { bg: 'bg-slate-700',  light: 'bg-slate-50',  text: 'text-slate-700',  ring: 'ring-slate-200',  dot: 'bg-slate-500'  },
    'Closed-Won':    { bg: 'bg-blue-600',   light: 'bg-blue-50',   text: 'text-blue-700',   ring: 'ring-blue-200',   dot: 'bg-blue-500'   },
}[status] || { bg: 'bg-slate-400', light: 'bg-slate-50', text: 'text-slate-600', ring: 'ring-slate-200', dot: 'bg-slate-400' });
</script>

<template>
    <AuthenticatedLayout title="Lead & Deal Workspace">
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 pb-16">

                <!-- ── Hero header ── -->
                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl animate-float-delayed" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                    <div class="relative flex flex-wrap items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg animate-pop">
                            <ArrowUpRight class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <MoveRight class="h-3.5 w-3.5" /> CRM · Lead Pipeline
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Lead Pipeline</h1>
                            <p class="text-sm text-blue-100/90">Structured queue — oldest first · {{ currentLeads.length }} in {{ activeTab }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="!canEdit && permissions.leads === 'view'"
                                class="rounded-full bg-amber-300/90 px-3 py-1.5 text-xs font-black text-amber-950">View only</span>
                            <span v-else-if="canEdit"
                                class="rounded-full bg-emerald-400/90 px-3 py-1.5 text-xs font-black text-emerald-950">Full access</span>
                        </div>
                    </div>

                    <!-- Search + create -->
                    <div class="relative mt-6 flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <Search class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search company, contact, email..."
                                class="w-full rounded-2xl border-0 bg-white/95 py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-lg placeholder:text-gray-400 focus:ring-2 focus:ring-white/70 outline-none transition"
                            />
                        </div>
                        <button
                            v-if="canEdit"
                            @click="showCreateModal = true"
                            class="inline-flex items-center justify-center gap-1.5 rounded-2xl bg-white px-4 py-2.5 text-xs font-black uppercase tracking-wide text-indigo-700 shadow-lg transition-all duration-200 hover:scale-105 active:scale-95"
                        >
                            <Plus class="w-4 h-4" /> New Deal
                        </button>
                    </div>

                    <!-- Stage tabs as pills -->
                    <div class="relative mt-4 flex gap-2 overflow-x-auto no-scrollbar pb-1">
                        <button
                            v-for="stage in stages"
                            :key="stage.status"
                            @click="activeTab = stage.status"
                            :class="activeTab === stage.status ? 'bg-white text-indigo-700 shadow-lg scale-105' : 'bg-white/15 text-white hover:bg-white/25'"
                            class="flex items-center gap-2 rounded-2xl px-4 py-2.5 text-xs font-black uppercase tracking-wide backdrop-blur transition-all duration-200 active:scale-95 whitespace-nowrap flex-shrink-0"
                        >
                            <component :is="stage.icon" class="w-3.5 h-3.5" />
                            {{ stage.label }}
                            <span :class="activeTab === stage.status ? 'text-indigo-400' : 'text-white/70'">
                                · {{ leadsByStatus[stage.status]?.length || 0 }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="currentLeads.length === 0"
                    class="animate-fade-up flex flex-col items-center justify-center py-20 text-center bg-white dark:bg-zinc-900 rounded-3xl border border-dashed border-gray-200 dark:border-zinc-800 shadow-sm">
                    <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-4 animate-bounce-soft">
                        <AlertCircle class="h-9 w-9 text-indigo-400" />
                    </div>
                    <p class="text-sm font-black text-gray-700 dark:text-gray-200">No leads in this stage</p>
                    <p class="text-xs text-gray-400 mt-1">Create a new deal to start the pipeline.</p>
                </div>

                <!-- Lead Cards -->
                <TransitionGroup v-else name="card" tag="div" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div
                        v-for="(lead, i) in currentLeads"
                        :key="lead.id"
                        :style="{ transitionDelay: `${Math.min(i * 40, 400)}ms` }"
                        class="group relative flex flex-col bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1.5 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 overflow-hidden"
                    >
                        <!-- hover glow -->
                        <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                        <!-- Colored left accent bar -->
                        <div class="flex relative">
                            <div :class="stageAccent(lead.status).bg" class="w-1.5 flex-shrink-0 rounded-l-3xl"></div>

                            <div class="flex-1 p-5">
                                <!-- Top row -->
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <!-- Avatar -->
                                        <div :class="['h-12 w-12 rounded-2xl overflow-hidden flex items-center justify-center flex-shrink-0 text-white text-lg font-black shadow-lg uppercase group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300', lead.logo?.logo_url ? 'bg-white' : 'bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700']">
                                            <img v-if="lead.logo?.logo_url" :src="lead.logo.logo_url" :alt="lead.company_name" class="h-full w-full object-cover" />
                                            <span v-else>{{ lead.company_name?.charAt(0)?.toUpperCase() }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-black text-gray-900 dark:text-white leading-tight truncate tracking-tight group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors">{{ lead.company_name }}</h3>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate font-medium">{{ lead.contact_person }} · {{ lead.email }}</p>
                                            <span class="mt-1.5 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase ring-1"
                                                :class="stageAccent(lead.status).light + ' ' + stageAccent(lead.status).text + ' ' + stageAccent(lead.status).ring">
                                                <span :class="['h-1.5 w-1.5 rounded-full animate-pulse', stageAccent(lead.status).dot]" /> {{ lead.status }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Primary action button -->
                                    <div class="flex-shrink-0">
                                        <button
                                            v-if="canEdit && (lead.status === 'Inquiry' || lead.status === 'Negotiation')"
                                            @click="openMoveConfirm(lead)"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:shadow-lg hover:shadow-indigo-500/30 hover:scale-105 text-white rounded-xl text-xs font-black uppercase tracking-wide transition-all active:scale-95"
                                        >
                                            <MoveRight class="w-3.5 h-3.5" /> Move to {{ getNextStage(lead.status) }}
                                        </button>
                                        <button
                                            v-if="canEdit && lead.status === 'Approval Sent'"
                                            @click="openFinalizeModal(lead)"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:shadow-lg hover:shadow-amber-500/30 hover:scale-105 text-white rounded-xl text-xs font-black uppercase tracking-wide transition-all active:scale-95"
                                        >
                                            <CheckCircle2 class="w-3.5 h-3.5" /> Finalize
                                        </button>
                                    </div>
                                </div>

                                <!-- Value / Fabric row -->
                                <div class="mt-4 flex items-center gap-2 rounded-2xl bg-gray-50 dark:bg-zinc-800/70 px-4 py-3">
                                    <DollarSign class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                                    <span class="text-sm font-black text-gray-900 dark:text-white">{{ formatCurrency(lead.estimated_value) }}</span>
                                    <span class="text-[10px] font-black uppercase tracking-wide text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-1 rounded-full">{{ lead.interest_fabric }}</span>
                                    <span class="text-[10px] font-bold text-gray-400 ml-auto">#{{ lead.id }}</span>
                                </div>

                                <!-- ── Stage-specific sections ── -->

                                <!-- INQUIRY: Add Note + Notes list -->
                                <div v-if="lead.status === 'Inquiry'" class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800 space-y-2">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-if="canEdit"
                                            @click="openNoteModal(lead)"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-50 dark:bg-zinc-800 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-gray-600 dark:text-gray-300 hover:text-indigo-700 dark:hover:text-indigo-300 border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800 rounded-xl text-xs font-bold transition-all active:scale-95"
                                        >
                                            <MessageSquare class="w-3.5 h-3.5" /> Add Note
                                        </button>
                                    </div>
                                    <div v-if="lead.notes && lead.notes.length">
                                        <button @click="toggleNotes(lead.id)" class="flex items-center gap-1 text-xs font-bold text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-300 transition-colors">
                                            <ChevronDown v-if="!showNotes[lead.id]" class="w-3.5 h-3.5" />
                                            <ChevronUp v-else class="w-3.5 h-3.5" />
                                            {{ lead.notes.length }} note{{ lead.notes.length !== 1 ? 's' : '' }}
                                        </button>
                                        <div v-if="showNotes[lead.id]" class="mt-2 space-y-2 pl-3 border-l-2 border-indigo-200 dark:border-indigo-800">
                                            <div v-for="note in lead.notes" :key="note.id" class="bg-gray-50 dark:bg-zinc-800 rounded-2xl p-3 hover:shadow-md transition-shadow">
                                                <p class="text-xs text-gray-700 dark:text-gray-200">{{ note.note }}</p>
                                                <p class="text-[10px] text-gray-400 mt-1 font-medium">{{ note.user.name }} · {{ new Date(note.created_at).toLocaleString() }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- NEGOTIATION: Schedule Interview + Interviews list -->
                                <div v-if="lead.status === 'Negotiation'" class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800 space-y-2">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-if="canEdit"
                                            @click="openInterviewModal(lead)"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-50 dark:bg-zinc-800 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-gray-600 dark:text-gray-300 hover:text-indigo-700 dark:hover:text-indigo-300 border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800 rounded-xl text-xs font-bold transition-all active:scale-95"
                                        >
                                            <Video class="w-3.5 h-3.5" /> Schedule Interview
                                        </button>
                                    </div>
                                    <div v-if="lead.interviews && lead.interviews.length">
                                        <button @click="toggleInterviews(lead.id)" class="flex items-center gap-1 text-xs font-bold text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-300 transition-colors">
                                            <ChevronDown v-if="!showInterviews[lead.id]" class="w-3.5 h-3.5" />
                                            <ChevronUp v-else class="w-3.5 h-3.5" />
                                            {{ lead.interviews.length }} interview{{ lead.interviews.length !== 1 ? 's' : '' }}
                                        </button>
                                        <div v-if="showInterviews[lead.id]" class="mt-2 space-y-2 pl-3 border-l-2 border-indigo-200 dark:border-indigo-800">
                                            <div v-for="iv in lead.interviews" :key="iv.id" class="bg-gray-50 dark:bg-zinc-800 rounded-2xl p-3 space-y-1 hover:shadow-md transition-shadow">
                                                <div class="flex items-center gap-1.5 text-xs font-bold text-gray-700 dark:text-gray-200">
                                                    <Calendar class="w-3 h-3 text-indigo-400" /> {{ formatDateTime(iv.scheduled_at) }}
                                                </div>
                                                <div v-if="iv.location" class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                    <MapPin class="w-3 h-3 text-violet-400" /> {{ iv.location }}
                                                </div>
                                                <p v-if="iv.notes" class="text-xs text-gray-500 dark:text-gray-400 italic">{{ iv.notes }}</p>
                                                <p class="text-[10px] text-gray-400">{{ iv.user.name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- APPROVAL SENT: file count -->
                                <div v-if="lead.status === 'Approval Sent' && lead.approval_files?.length" class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 rounded-full bg-amber-50 dark:bg-amber-900/20 px-3 py-1.5 ring-1 ring-amber-200 dark:ring-amber-800">
                                        <FileText class="w-3.5 h-3.5 text-amber-500" />
                                        {{ lead.approval_files.length }} file{{ lead.approval_files.length !== 1 ? 's' : '' }} attached
                                    </span>
                                </div>

                                <!-- CLOSED-WON: Convert to Client -->
                                <div v-if="lead.status === 'Closed-Won'" class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800">
                                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-emerald-50/70 dark:bg-emerald-900/10 p-3">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse shadow-lg shadow-emerald-500/40" /> Qualified
                                        </span>
                                        <button
                                            v-if="canEdit"
                                            @click="openConversionModal(lead)"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:shadow-lg hover:shadow-emerald-500/30 hover:scale-105 text-white rounded-xl text-xs font-black uppercase tracking-wide transition-all active:scale-95"
                                        >
                                            <UserCheck class="w-3.5 h-3.5" /> Create Client Account
                                        </button>
                                    </div>
                                </div>

                                <!-- Date footer -->
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-300 dark:text-zinc-600 mt-4 text-right">
                                    Created {{ new Date(lead.created_at).toLocaleDateString() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
             MODALS — shared shell pattern
        ══════════════════════════════════════════ -->
        <Teleport to="body">

            <!-- ── 1. Move Confirm ── -->
            <Transition name="modal">
                <div v-if="showMoveConfirmModal" class="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeAllModals">
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 via-blue-600 to-violet-600 p-5 flex items-center gap-3 relative overflow-hidden">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/15 blur-2xl" />
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30">
                                <MoveRight class="w-5 h-5 text-white" />
                            </div>
                            <div class="relative min-w-0 flex-1">
                                <h3 class="text-white font-black">Confirm Stage Change</h3>
                                <p class="text-xs text-blue-100 truncate">{{ currentLead?.company_name }}</p>
                            </div>
                            <button @click="closeAllModals" class="relative flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white hover:bg-white/35 hover:rotate-90 transition-all">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="p-6">
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-2xl p-4 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                Move <span class="font-black text-gray-900 dark:text-white">{{ currentLead?.company_name }}</span> from
                                <span class="font-black text-indigo-600">{{ currentLead?.status }}</span> to
                                <span class="font-black text-violet-600">{{ pendingMoveNextStage }}</span>?
                            </div>
                        </div>
                        <!-- Footer -->
                        <div class="px-6 pb-6 flex gap-3">
                            <button @click="closeAllModals" class="flex-1 py-2.5 rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-500 font-bold text-sm hover:bg-gray-200 active:scale-95 transition">Cancel</button>
                            <button @click="confirmMove" class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-black text-sm shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-[1.02] active:scale-95 transition-all">
                                <MoveRight class="w-4 h-4" /> Confirm Move
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ── 2. Note Modal ── -->
            <Transition name="modal">
                <div v-if="showNoteModal" class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeAllModals">
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 via-blue-600 to-violet-600 p-5 flex items-center gap-3 relative overflow-hidden">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/15 blur-2xl" />
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30">
                                <MessageSquare class="w-5 h-5 text-white" />
                            </div>
                            <div class="relative min-w-0 flex-1">
                                <h3 class="text-white font-black">Add Note</h3>
                                <p class="text-xs text-blue-100 truncate">{{ currentLead?.company_name }}</p>
                            </div>
                            <button @click="closeAllModals" class="relative flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white hover:bg-white/35 hover:rotate-90 transition-all">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="p-6">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Note <span class="text-rose-500">*</span></label>
                            <textarea
                                v-model="noteForm.note"
                                rows="4"
                                placeholder="Write your notes here..."
                                required
                                class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition resize-none dark:text-white"
                            ></textarea>
                        </div>
                        <!-- Footer -->
                        <div class="px-6 pb-6 flex gap-3">
                            <button @click="closeAllModals" class="flex-1 py-2.5 rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-500 font-bold text-sm hover:bg-gray-200 active:scale-95 transition">Cancel</button>
                            <button @click="addNote" :disabled="noteForm.processing || !noteForm.note.trim()"
                                class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-black text-sm shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-60">
                                <MessageSquare class="w-4 h-4" /> {{ noteForm.processing ? 'Saving…' : 'Save Note' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ── 3. Interview Modal ── -->
            <Transition name="modal">
                <div v-if="showInterviewModal" class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeAllModals">
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 via-orange-600 to-rose-600 p-5 flex items-center gap-3 relative overflow-hidden">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/15 blur-2xl" />
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30">
                                <Video class="w-5 h-5 text-white" />
                            </div>
                            <div class="relative min-w-0 flex-1">
                                <h3 class="text-white font-black">Schedule Interview</h3>
                                <p class="text-xs text-orange-100 truncate">{{ currentLead?.company_name }}</p>
                            </div>
                            <button @click="closeAllModals" class="relative flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white hover:bg-white/35 hover:rotate-90 transition-all">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Date & Time <span class="text-rose-500">*</span></label>
                                <input
                                    type="datetime-local"
                                    v-model="interviewForm.scheduled_at"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Location <span class="font-medium normal-case">(optional)</span></label>
                                <input
                                    type="text"
                                    v-model="interviewForm.location"
                                    placeholder="e.g. Zoom, Office, etc."
                                    class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition dark:text-white placeholder:text-gray-400"
                                />
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Notes <span class="font-medium normal-case">(optional)</span></label>
                                <textarea
                                    v-model="interviewForm.notes"
                                    rows="3"
                                    placeholder="Additional context..."
                                    class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition resize-none dark:text-white placeholder:text-gray-400"
                                ></textarea>
                            </div>
                        </div>
                        <!-- Footer -->
                        <div class="px-6 pb-6 flex gap-3">
                            <button @click="closeAllModals" class="flex-1 py-2.5 rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-500 font-bold text-sm hover:bg-gray-200 active:scale-95 transition">Cancel</button>
                            <button @click="scheduleInterview" :disabled="interviewForm.processing || !interviewForm.scheduled_at"
                                class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-black text-sm shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-60">
                                <Calendar class="w-4 h-4" /> {{ interviewForm.processing ? 'Scheduling…' : 'Schedule' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ── 4. Finalize Modal (Approval Sent) ── -->
            <Transition name="modal">
                <div v-if="showFinalizeModal" class="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeAllModals">
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden max-h-[92vh] flex flex-col">
                        <div class="bg-gradient-to-r from-amber-500 via-orange-600 to-rose-600 p-5 flex items-center gap-3 relative overflow-hidden flex-shrink-0">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/15 blur-2xl" />
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30">
                                <FileText class="w-5 h-5 text-white" />
                            </div>
                            <div class="relative min-w-0 flex-1">
                                <h3 class="text-white font-black">Finalize Lead</h3>
                                <p class="text-xs text-orange-100 truncate">{{ currentLead?.company_name }}</p>
                            </div>
                            <button @click="closeAllModals" class="relative flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white hover:bg-white/35 hover:rotate-90 transition-all">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-4">
                            <!-- Info banner -->
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-2xl p-4 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                Complete the approval process for <span class="font-black text-gray-900 dark:text-white">{{ currentLead?.company_name }}</span>.
                            </div>

                            <!-- File Upload -->
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">
                                    Upload Approval File <span class="font-medium normal-case">(optional, max 2MB)</span>
                                </label>
                                <div class="border border-dashed border-gray-200 dark:border-zinc-700 rounded-2xl p-4 space-y-3 bg-gray-50/50 dark:bg-zinc-800/50">
                                    <input
                                        type="file"
                                        @change="handleFileChange"
                                        accept="image/*,application/pdf"
                                        class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
                                    />
                                    <button
                                        v-if="finalizeFile"
                                        @click="uploadFinalizeFile"
                                        :disabled="isUploading"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:shadow-lg hover:shadow-indigo-500/30 disabled:opacity-50 text-white rounded-xl text-xs font-black uppercase tracking-wide transition-all active:scale-95"
                                    >
                                        <Upload class="w-3.5 h-3.5" />
                                        {{ isUploading ? 'Uploading…' : 'Upload File' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Accept / Reject toggle -->
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Decision <span class="text-rose-500">*</span></label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        @click="finalizeAction = 'accept'"
                                        :class="finalizeAction === 'accept'
                                            ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-500/30 ring-2 ring-emerald-500'
                                            : 'bg-gray-50 dark:bg-zinc-800 text-gray-600 dark:text-gray-300 hover:ring-2 hover:ring-emerald-300'"
                                        class="flex items-center justify-center gap-2 py-3 rounded-2xl text-sm font-black transition-all active:scale-95"
                                    >
                                        <Check class="w-4 h-4" /> Accept
                                    </button>
                                    <button
                                        @click="finalizeAction = 'reject'"
                                        :class="finalizeAction === 'reject'
                                            ? 'bg-gradient-to-r from-rose-600 to-red-600 text-white shadow-lg shadow-rose-500/30 ring-2 ring-rose-500'
                                            : 'bg-gray-50 dark:bg-zinc-800 text-gray-600 dark:text-gray-300 hover:ring-2 hover:ring-rose-300'"
                                        class="flex items-center justify-center gap-2 py-3 rounded-2xl text-sm font-black transition-all active:scale-95"
                                    >
                                        <XCircle class="w-4 h-4" /> Reject
                                    </button>
                                </div>
                            </div>

                            <!-- Rejection reason -->
                            <div v-if="finalizeAction === 'reject'">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Reason for Rejection <span class="text-rose-500">*</span></label>
                                <textarea
                                    v-model="rejectReason"
                                    rows="3"
                                    placeholder="Please provide a reason..."
                                    class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-rose-400 focus:ring-2 focus:ring-rose-200 outline-none text-sm transition resize-none dark:text-white placeholder:text-gray-400"
                                ></textarea>
                            </div>
                        </div>
                        <!-- Footer -->
                        <div class="px-6 pb-6 flex-shrink-0 flex gap-3">
                            <button @click="closeAllModals" class="flex-1 py-2.5 rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-500 font-bold text-sm hover:bg-gray-200 active:scale-95 transition">Cancel</button>
                            <button
                                @click="submitFinalize"
                                :disabled="!finalizeAction || (finalizeAction === 'reject' && !rejectReason.trim())"
                                :class="finalizeAction === 'reject' ? 'from-rose-600 to-red-600 shadow-rose-500/30' : 'from-indigo-600 to-violet-600 shadow-indigo-500/30'"
                                class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-gradient-to-r text-white font-black text-sm shadow-lg hover:shadow-xl hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                <CheckCircle2 class="w-4 h-4" />
                                {{ finalizeAction === 'accept' ? 'Confirm & Move to Closed-Won' : finalizeAction === 'reject' ? 'Confirm & Archive' : 'Confirm' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ── 5. Client Conversion Modal ── -->
            <Transition name="modal">
                <div v-if="showClientConversionModal" class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showClientConversionModal = false">
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden max-h-[92vh] flex flex-col">
                        <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 p-5 flex items-center gap-3 relative overflow-hidden flex-shrink-0">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/15 blur-2xl" />
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30">
                                <UserCheck class="w-5 h-5 text-white" />
                            </div>
                            <div class="relative min-w-0 flex-1">
                                <h3 class="text-white font-black">Promote to Business Client</h3>
                                <p class="text-xs text-emerald-100 truncate">{{ conversionForm.company_name }}</p>
                            </div>
                            <button @click="showClientConversionModal = false" class="relative flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white hover:bg-white/35 hover:rotate-90 transition-all">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-4">
                            <!-- Info -->
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl p-4 text-sm text-emerald-800 dark:text-emerald-200">
                                Finalizing the partnership for <span class="font-black">{{ conversionForm.company_name }}</span>. Fill in the official business details below.
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Business Type</label>
                                    <select
                                        v-model="conversionForm.business_type"
                                        class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition dark:text-white"
                                    >
                                        <option value="wholesaler">Wholesaler</option>
                                        <option value="retailer">Retailer</option>
                                        <option value="manufacturer">Manufacturer</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">TIN Number <span class="text-rose-500">*</span></label>
                                    <input
                                        v-model="conversionForm.tin_number"
                                        type="text"
                                        placeholder="000-000-000"
                                        required
                                        class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition dark:text-white placeholder:text-gray-400"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Official Company Address <span class="text-rose-500">*</span></label>
                                <textarea
                                    v-model="conversionForm.company_address"
                                    rows="3"
                                    placeholder="Complete business address"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition resize-none dark:text-white placeholder:text-gray-400"
                                ></textarea>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Company Logo (optional)</label>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gray-100 dark:bg-zinc-800">
                                        <img v-if="conversionLogoPreview" :src="conversionLogoPreview" alt="Logo preview" class="h-full w-full object-cover" />
                                        <Building2 v-else class="h-6 w-6 text-gray-300" />
                                    </div>
                                    <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                                        @change="handleConversionLogoChange"
                                        class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-xl file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-emerald-700 file:transition" />
                                </div>
                                <p class="mt-1 text-[11px] text-gray-400">PNG, JPG, WEBP or SVG · max 5MB.</p>
                            </div>
                        </div>
                        <!-- Footer -->
                        <div class="px-6 pb-6 flex-shrink-0 flex gap-3">
                            <button @click="showClientConversionModal = false" class="flex-1 py-2.5 rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-500 font-bold text-sm hover:bg-gray-200 active:scale-95 transition">Cancel</button>
                            <button
                                @click="submitConversion"
                                :disabled="conversionForm.processing"
                                class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-black text-sm shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-60"
                            >
                                <Building2 class="w-4 h-4" />
                                {{ conversionForm.processing ? 'Converting…' : 'Finalize Client' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ── 6. Create Deal Modal ── -->
            <Transition name="modal">
                <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showCreateModal = false">
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden max-h-[92vh] flex flex-col">
                        <div class="bg-gradient-to-r from-indigo-600 via-blue-600 to-violet-600 p-5 flex items-center gap-3 relative overflow-hidden flex-shrink-0">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/15 blur-2xl" />
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30">
                                <Plus class="w-5 h-5 text-white" />
                            </div>
                            <div class="relative min-w-0 flex-1">
                                <h3 class="text-white font-black">New Textile Deal</h3>
                                <p class="text-xs text-blue-100">Fill in the lead details</p>
                            </div>
                            <button @click="showCreateModal = false" class="relative flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white hover:bg-white/35 hover:rotate-90 transition-all">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-4">
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Company Name <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.company_name"
                                    type="text"
                                    placeholder="Company name"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition dark:text-white placeholder:text-gray-400"
                                />
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Contact Person <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.contact_person"
                                    type="text"
                                    placeholder="Full name"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition dark:text-white placeholder:text-gray-400"
                                />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Email <span class="text-rose-500">*</span></label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="email@company.com"
                                        required
                                        class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition dark:text-white placeholder:text-gray-400"
                                    />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Phone <span class="text-rose-500">*</span></label>
                                    <input
                                        v-model="form.phone"
                                        type="text"
                                        placeholder="+63 000 0000"
                                        required
                                        class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition dark:text-white placeholder:text-gray-400"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Estimated Value (₱) <span class="text-rose-500">*</span></label>
                                    <input
                                        v-model="form.estimated_value"
                                        type="number"
                                        placeholder="0.00"
                                        required
                                        class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition dark:text-white placeholder:text-gray-400"
                                    />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Fabric Interest</label>
                                    <select
                                        v-model="form.interest_fabric"
                                        class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition dark:text-white"
                                    >
                                        <option>Cotton</option>
                                        <option>Wool</option>
                                        <option>Nylon</option>
                                        <option>Polyester</option>
                                        <option>Silk</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Company Logo (optional)</label>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gray-100 dark:bg-zinc-800">
                                        <img v-if="logoPreview" :src="logoPreview" alt="Logo preview" class="h-full w-full object-cover" />
                                        <Building2 v-else class="h-6 w-6 text-gray-300" />
                                    </div>
                                    <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                                        @change="handleLogoChange"
                                        class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-xl file:border-0 file:bg-indigo-600 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-indigo-700 file:transition" />
                                </div>
                                <p class="mt-1 text-[11px] text-gray-400">PNG, JPG, WEBP or SVG · max 5MB. Carried over if this deal becomes a client.</p>
                            </div>
                        </div>
                        <!-- Footer -->
                        <div class="px-6 pb-6 flex-shrink-0 flex gap-3">
                            <button @click="showCreateModal = false" class="flex-1 py-2.5 rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-500 font-bold text-sm hover:bg-gray-200 active:scale-95 transition">Cancel</button>
                            <button
                                @click="submit"
                                :disabled="form.processing"
                                class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-black text-sm shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-60"
                            >
                                <Plus class="w-4 h-4" />
                                {{ form.processing ? 'Creating…' : 'Create Deal' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
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
.modal-enter-active { transition: opacity 0.25s ease; }
.modal-enter-active > div { transition: transform 0.3s cubic-bezier(0.22,1,0.36,1), opacity 0.3s ease; }
.modal-enter-from { opacity: 0; }
.modal-enter-from > div { transform: scale(0.92) translateY(14px); opacity: 0; }
.modal-leave-active { transition: opacity 0.2s ease; }
.modal-leave-to { opacity: 0; }
</style>