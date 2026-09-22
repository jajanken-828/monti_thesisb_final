<template>
    <AuthenticatedLayout>
        <Head title="My Onboarding" />
        <div class="h-full flex flex-col">
            <!-- ============ ACTIVE ONBOARDING ============ -->
            <template v-if="active && onboarding">
                <!-- HEADER -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shrink-0">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Welcome, <span class="text-blue-600">{{ firstName }}</span>
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ onboarding.position }} · {{ onboarding.department }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                            :class="onboardingStatusBadge(onboarding.status)">
                            {{ onboarding.status }}
                        </span>
                        <span class="inline-flex px-3 py-1.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                            {{ onboarding.progress }}% complete
                        </span>
                    </div>
                </div>

                <!-- PROGRESS BAR -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden p-4 mb-4 shrink-0">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">Onboarding Progress</span>
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ onboarding.progress }}%</span>
                    </div>
                    <div class="w-full h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full transition-all duration-700" :style="{ width: onboarding.progress + '%' }"></div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Complete your required items and wait for HR to verify each one. You will be notified when everything is done.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 flex-1 min-h-0">
                    <!-- LEFT: Employment details -->
                    <div class="space-y-4 overflow-y-auto pr-1">
                        <!-- EMPLOYMENT DETAILS -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Employment Details</h3>
                            </div>
                            <div class="p-4 space-y-3">
                                <div class="flex justify-between gap-3 border-b border-slate-100 dark:border-slate-700/50 pb-2">
                                    <span class="text-[11px] text-slate-400">Position</span>
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white text-right">{{ onboarding.position }}</span>
                                </div>
                                <div class="flex justify-between gap-3 border-b border-slate-100 dark:border-slate-700/50 pb-2">
                                    <span class="text-[11px] text-slate-400">Department</span>
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white text-right">{{ onboarding.department }}</span>
                                </div>
                                <div class="flex justify-between gap-3 border-b border-slate-100 dark:border-slate-700/50 pb-2">
                                    <span class="text-[11px] text-slate-400">Onboarding Template</span>
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white text-right">{{ onboarding.template || '—' }}</span>
                                </div>
                                <div class="flex justify-between gap-3 border-b border-slate-100 dark:border-slate-700/50 pb-2">
                                    <span class="text-[11px] text-slate-400">Start Date</span>
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white text-right">{{ formatDate(onboarding.start_date) }}</span>
                                </div>
                                <div class="flex justify-between gap-3 border-b border-slate-100 dark:border-slate-700/50 pb-2">
                                    <span class="text-[11px] text-slate-400">Expected Start</span>
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white text-right">{{ formatDate(onboarding.expected_start_date) }}</span>
                                </div>
                                <div class="flex justify-between gap-3 border-b border-slate-100 dark:border-slate-700/50 pb-2">
                                    <span class="text-[11px] text-slate-400">Work Location</span>
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white text-right">{{ onboarding.work_location || '—' }}</span>
                                </div>
                                <div class="flex justify-between gap-3 border-b border-slate-100 dark:border-slate-700/50 pb-2">
                                    <span class="text-[11px] text-slate-400">Schedule</span>
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white text-right">{{ onboarding.work_schedule || '—' }}</span>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <span class="text-[11px] text-slate-400">Assigned Manager</span>
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white text-right">{{ onboarding.assigned_manager || 'To be assigned' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- ACTIVITIES -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Orientation & Training</h3>
                            </div>
                            <div class="p-4">
                                <div v-if="onboarding.activities.length === 0" class="text-center py-4">
                                    <p class="text-xs text-slate-500 dark:text-slate-400">No activities scheduled yet. HR will notify you once schedules are set.</p>
                                </div>
                                <div v-else class="space-y-2">
                                    <div v-for="activity in onboarding.activities" :key="activity.id"
                                        class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                                        <div class="flex items-start gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                                <CalendarIcon class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2 flex-wrap">
                                                    {{ activity.title }}
                                                    <span v-if="activity.is_required" class="bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 px-1.5 py-0.5 rounded text-[9px] font-bold">REQUIRED</span>
                                                    <span :class="['px-2 py-0.5 rounded-full text-[9px] font-bold', activityStatusBadge(activity.status)]">
                                                        {{ activity.status }}
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                                    {{ activity.type }}
                                                    <template v-if="activity.schedule_date"> · {{ formatDate(activity.schedule_date) }}
                                                        <template v-if="activity.start_time"> · {{ activity.start_time }}<template v-if="activity.end_time"> - {{ activity.end_time }}</template></template>
                                                    </template>
                                                    <template v-if="activity.method"> · {{ activity.method }}</template>
                                                    <template v-if="activity.location"> · {{ activity.location }}</template>
                                                    <template v-if="activity.meeting_link"> · <a :href="activity.meeting_link" target="_blank" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">{{ activity.meeting_link }}</a></template>
                                                    <template v-if="activity.facilitator"> · Facilitator: {{ activity.facilitator }}</template>
                                                    <template v-if="activity.organizer"> · Organizer: {{ activity.organizer }}</template>
                                                </div>
                                                <!-- LOOK: Actual calendar event field -->
                                                <div v-if="activity.schedule_date" class="mt-1.5 inline-flex items-center gap-1 text-[9px] font-semibold px-2 py-0.5 rounded"
                                                    :class="activity.attendance_status === 'Confirmed' ? 'text-emerald-700 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400' : 'text-blue-700 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400'">
                                                    <CheckIcon v-if="activity.attendance_status === 'Confirmed'" class="w-3 h-3" />
                                                    <BellIcon v-else class="w-3 h-3" />
                                                    <template v-if="activity.attendance_status === 'Confirmed'">Attendance confirmed</template>
                                                    <template v-else>
                                                        Will you attend?
                                                        <button @click="confirmAttendance(activity)" class="underline font-bold hover:text-blue-700 dark:hover:text-blue-300">Confirm</button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Unified onboarding items -->
                    <div class="lg:col-span-2 space-y-4 overflow-y-auto pr-1">
                        <!-- ITEMS -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">My Onboarding Requirements</h3>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Submit each requested item. You can resubmit anytime until everything is verified.</p>
                                </div>
                            </div>
                            <div class="p-4 space-y-5">
                                <div v-for="(items, section) in onboarding.sections" :key="section">
                                    <h4 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">{{ section }}</h4>
                                    <div class="space-y-2">
                                        <div v-for="item in items" :key="item.id"
                                            class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs font-semibold text-slate-900 dark:text-white flex items-center gap-2 flex-wrap">
                                                        {{ item.name }}
                                                        <span :class="item.is_required ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'" class="px-1.5 py-0.5 rounded text-[9px] font-bold">
                                                            {{ item.is_required ? 'REQUIRED' : 'OPTIONAL' }}
                                                        </span>
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">{{ item.input_type }}</span>
                                                        <span :class="['px-2 py-0.5 rounded-full text-[9px] font-bold', itemStatusBadge(item.status)]">
                                                            {{ itemStatusLabel(item.status) }}
                                                        </span>
                                                    </div>
                                                    <p v-if="item.description" class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">{{ item.description }}</p>
                                                    <div v-if="item.due_date" class="text-[9px] text-amber-700 dark:text-amber-400 mt-0.5">
                                                        Due: {{ formatDate(item.due_date) }}
                                                        <span v-if="isOverdue(item)" class="font-bold text-rose-600 dark:text-rose-400">(overdue)</span>
                                                    </div>

                                                    <!-- Latest submission -->
                                                    <div v-if="item.latest_submission" class="mt-2 flex flex-wrap items-center gap-2">
                                                        <a v-if="item.latest_submission.file_url" :href="item.latest_submission.file_url" target="_blank"
                                                            class="inline-flex items-center gap-1 text-[10px] font-semibold text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded-lg border border-blue-200 dark:border-blue-800">
                                                            <DocumentTextIcon class="w-3 h-3" /> {{ item.latest_submission.original_name }}
                                                        </a>
                                                        <span v-if="item.latest_submission.value" class="inline-flex items-center text-[10px] text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-900 px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700">
                                                            {{ displayValue(item.latest_submission.value) }}
                                                        </span>
                                                        <span v-if="item.latest_submission.submitted_at" class="text-[9px] text-slate-400">Submitted {{ formatDateTime(item.latest_submission.submitted_at) }}</span>
                                                        <span v-if="item.status === 'Rejected' && item.latest_submission?.rejection_reason"
                                                            class="text-[9px] font-semibold text-rose-600 dark:text-rose-400">Reason: {{ item.latest_submission.rejection_reason }}</span>
                                                    </div>

                                                    <!-- Verified lock -->
                                                    <div v-if="item.status === 'Verified' || item.status === 'Completed'" class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30 px-2 py-1 rounded-lg">
                                                        <CheckIcon class="w-3 h-3" /> Verified by HR
                                                    </div>
                                                    <div v-else-if="item.is_task" class="mt-2 text-[10px] text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-2 py-1.5 rounded-lg">
                                                        This item is completed by the {{ item.responsible_party || 'HR team' }}. No action needed from you.
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Submission input (per input type) -->
                                            <div v-if="canSubmit(item)" class="mt-3">
                                                <!-- Document upload -->
                                                <div v-if="item.input_type === 'Document Upload'" class="flex flex-col sm:flex-row sm:items-center gap-2">
                                                    <label class="flex-1 flex items-center gap-2 cursor-pointer">
                                                        <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="handleFileSelect(item, $event)" class="hidden" />
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-[10px] font-bold transition shadow-sm active:scale-95">
                                                            <ArrowUpTrayIcon class="w-3.5 h-3.5" /> {{ item.status === 'Rejected' ? 'Resubmit Document' : 'Upload Document' }}
                                                        </span>
                                                    </label>
                                                    <input v-model="itemNotes[item.id]" type="text" placeholder="Optional note..." class="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition" />
                                                </div>

                                                <!-- Form / Text / Signature -->
                                                <div v-else-if="['Form', 'Text', 'Signature'].includes(item.input_type)" class="flex flex-col sm:flex-row gap-2">
                                                    <input
                                                        v-model="itemValues[item.id]"
                                                        :type="item.input_type === 'Signature' ? 'text' : 'text'"
                                                        :placeholder="item.input_type === 'Signature' ? 'Type your full name to sign' : (item.input_type === 'Text' ? 'Type your answer...' : 'Fill in the details...')"
                                                        class="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition"
                                                    />
                                                    <button @click="submitValue(item)"
                                                        class="inline-flex items-center justify-center gap-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-[10px] font-bold transition shadow-sm active:scale-95">
                                                        <PaperAirplaneIcon class="w-3.5 h-3.5" /> Submit
                                                    </button>
                                                </div>

                                                <!-- Number / Date -->
                                                <div v-else-if="['Number', 'Date'].includes(item.input_type)" class="flex flex-col sm:flex-row gap-2">
                                                    <input
                                                        v-model="itemValues[item.id]"
                                                        :type="item.input_type === 'Number' ? 'number' : 'date'"
                                                        :placeholder="item.input_type === 'Number' ? 'Enter a number' : ''"
                                                        class="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition"
                                                    />
                                                    <button @click="submitValue(item)"
                                                        class="inline-flex items-center justify-center gap-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-[10px] font-bold transition shadow-sm active:scale-95">
                                                        <PaperAirplaneIcon class="w-3.5 h-3.5" /> Submit
                                                    </button>
                                                </div>

                                                <!-- Checkbox / Acknowledgement -->
                                                <div v-else-if="['Checkbox', 'Acknowledgement'].includes(item.input_type)" class="flex flex-wrap items-center gap-3">
                                                    <button @click="submitValue(item, true)"
                                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-[10px] font-bold transition shadow-sm active:scale-95">
                                                        <CheckIcon class="w-3.5 h-3.5" /> {{ item.input_type === 'Acknowledgement' ? 'I Acknowledge' : 'Yes' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ============ NO ACTIVE ONBOARDING ============ -->
            <template v-else>
                <div class="flex-1 flex items-center justify-center p-8">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-12 text-center max-w-md">
                        <div class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mx-auto mb-4">
                            <ClipboardDocumentCheckIcon class="w-7 h-7 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No Active Onboarding</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">You don't currently have any application in the onboarding stage. Once HR advances your application, you will be able to submit your requirements here.</p>
                        <a href="/applicant/applications" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-blue-500/30 active:scale-95">
                            View My Applications
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import {
    CheckIcon, ArrowUpTrayIcon, DocumentTextIcon, CalendarIcon, ClipboardDocumentCheckIcon,
    PaperAirplaneIcon, BellIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    active: { type: Boolean, default: false },
    onboarding: { type: Object, default: null },
});

const page = usePage();
const itemNotes = ref({});
const itemValues = ref({});

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
}, { immediate: true });

watch(() => page.props.errors, (errors) => {
    if (Object.keys(errors).length > 0) {
        toast.error(String(Object.values(errors)[0]));
    }
}, { deep: true });

const initials = computed(() => {
    if (!props.onboarding) return 'A';
    const parts = props.onboarding.name.trim().split(/\s+/);
    const first = parts[0] ? parts[0][0] : '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return (first + last).toUpperCase();
});

const firstName = computed(() => {
    if (!props.onboarding) return '';
    const parts = props.onboarding.name.trim().split(/\s+/);
    return parts[0] || '';
});

const canSubmit = (item) =>
    item.status !== 'Verified' && item.status !== 'Completed' && !item.is_task;

const isOverdue = (item) => {
    if (!item.due_date) return false;
    return new Date(item.due_date) < new Date(new Date().toISOString().slice(0, 10));
};

const handleFileSelect = (item, event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    if (itemNotes.value[item.id]) {
        formData.append('notes', itemNotes.value[item.id]);
    }

    router.post(
        route('applicant.onboarding.items.submit', { item: item.id }),
        formData,
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Document uploaded. HR will review it soon.');
                itemNotes.value[item.id] = '';
                if (event.target) event.target.value = '';
            },
            onError: () => toast.error('Upload failed. Check the file format and size.'),
        },
    );
};

const submitValue = (item, value, notes = '') => {
    let payload;
    if (value !== undefined) {
        payload = { value };
    } else {
        const raw = itemValues.value[item.id];
        if (raw === undefined || raw === '') {
            toast.error('Please provide a value first.');
            return;
        }
        if (item.input_type === 'Number') {
            payload = { value: Number(raw) };
        } else {
            payload = { value: raw };
        }
    }

    router.post(
        route('applicant.onboarding.items.submit', { item: item.id }),
        payload,
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Submission saved. HR will review it soon.');
                itemValues.value[item.id] = '';
            },
            onError: () => toast.error('Failed to submit. Please try again.'),
        },
    );
};

const confirmAttendance = (activity) => {
    router.post(route('applicant.onboarding.activities.attend', { activity: activity.id }), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Attendance confirmed. See you there!'),
        onError: () => toast.error('Failed to confirm attendance. Please try again.'),
    });
};

const displayValue = (value) => {
    if (value === true) return 'Yes';
    if (value === false) return 'No';
    return String(value);
};

const itemDone = (status) =>
    status === 'Verified' || status === 'Completed'
        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
        : status === 'Rejected'
            ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
            : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400';

const itemStatusBadge = (status) =>
    ({
        Pending: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
        Submitted: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'Under Review': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        'In Progress': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        Verified: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        Completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        Rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
        Resubmitted: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        Blocked: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    })[status] || 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';

const itemStatusLabel = (status) =>
    ({ Pending: 'Not Submitted', Submitted: 'Awaiting Review', 'Under Review': 'Under Review', Rejected: 'Rejected', Verified: 'Verified', Resubmitted: 'Resubmitted', 'In Progress': 'In Progress', Blocked: 'Blocked' })[status] || status;

const activityStatusBadge = (status) =>
    ({
        Draft: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
        Scheduled: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'In Progress': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        Completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        Cancelled: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
        Rescheduled: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        'No Show': 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    })[status] || 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';

const onboardingStatusBadge = (status) =>
    ({
        'In Progress': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'Pending Requirements': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        'Ready to Hire': 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        Completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    })[status] || 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';

const formatDate = (value) => {
    if (!value) return '—';
    const d = new Date(value);
    if (isNaN(d)) return '—';
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatDateTime = (value) => {
    if (!value) return '—';
    const d = new Date(value);
    if (isNaN(d)) return '—';
    return d.toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
};
</script>
