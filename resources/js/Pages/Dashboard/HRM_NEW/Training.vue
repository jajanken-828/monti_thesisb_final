<template>
    <AuthenticatedLayout>
        <div
            class="flex min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-blue-50"
        >
            <div class="flex-1 p-8 text-slate-900 font-sans min-w-0 relative">
                <!-- Subtle Background Pattern -->
                <div
                    class="absolute inset-0 bg-grid-slate-100 [mask-image:linear-gradient(0deg,transparent,black,transparent)] pointer-events-none"
                ></div>

                <!-- Professional Header -->
                <header class="mb-8 relative">
                    <div
                        class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6"
                    >
                        <div>
                            <nav
                                class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-3"
                            >
                                <span
                                    class="hover:text-slate-700 cursor-pointer transition-colors"
                                    >Dashboard</span
                                >
                                <svg
                                    class="w-3 h-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                                <span class="text-slate-800 font-semibold"
                                    >Learning & Development</span
                                >
                            </nav>
                            <h1
                                class="text-4xl font-bold tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent"
                            >
                                Training
                            </h1>
                            <p class="text-sm text-slate-500 mt-1.5">
                                Courses, enrollments, and certifications —
                                completions issue certificates automatically.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative">
                                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                                </svg>
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search courses..."
                                    class="pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm"
                                />
                            </div>
                            <select
                                v-model="filterStatus"
                                @change="applyFilter"
                                class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 shadow-sm"
                            >
                                <option value="">All Statuses</option>
                                <option value="Draft">Draft</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                            <button
                                @click="openCreate"
                                class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white rounded-xl text-sm font-bold transition-all duration-200 shadow-lg hover:shadow-xl hover:shadow-blue-500/25 hover:-translate-y-0.5"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                Create Course
                            </button>
                        </div>
                    </div>
                </header>

                <!-- METRICS - Floating Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div
                        v-for="m in metricList"
                        :key="m.label"
                        class="group bg-white rounded-2xl border border-slate-200/60 p-5 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 hover:border-blue-200/60 shadow-md backdrop-blur-sm"
                    >
                        <span
                            class="text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >{{ m.label }}</span
                        >
                        <div class="text-2xl font-bold text-slate-900 mt-2">
                            {{ m.value }}
                        </div>
                    </div>
                </div>

                <!-- TRAINING CARDS -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-8"
                >
                    <div
                        v-for="t in trainingList"
                        :key="t.id"
                        class="bg-white rounded-2xl border border-slate-200/60 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 backdrop-blur-sm p-5"
                    >
                        <div class="flex justify-between mb-3">
                            <span
                                :class="[
                                    'px-3 py-1 rounded-xl text-[10px] font-semibold shadow-sm',
                                    t.status === 'Completed'
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : 'bg-blue-50 text-blue-700 border border-blue-200',
                                ]"
                                >{{ t.status }}</span
                            >
                            <span
                                v-if="t.expiring"
                                class="inline-flex items-center gap-1 text-[10px] text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-200"
                                ><AlertTriangle class="w-3 h-3" />Expiring</span
                            >
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 mb-2">
                            {{ t.name }}
                        </h3>
                        <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                            {{ t.description || 'No description.' }}
                        </p>
                        <div
                            class="flex justify-between text-xs text-slate-500 pt-3 border-t border-slate-100"
                        >
                            <span class="flex items-center gap-1"
                                ><Users class="w-3.5 h-3.5" />
                                <strong>{{ t.enrolled }}</strong> enrolled</span
                            >
                            <span class="flex items-center gap-1"
                                ><Clock class="w-3.5 h-3.5" /> <strong>{{ t.duration }}</strong></span
                            >
                        </div>
                        <div class="flex gap-2 mt-4">
                            <button
                                @click="openEnroll(t)"
                                class="flex-1 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-[11px] font-bold transition-all shadow-sm"
                            >
                                Enroll
                            </button>
                            <button
                                @click="openEdit(t)"
                                class="px-3 py-2 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl text-[11px] font-bold transition-all"
                            >
                                Edit
                            </button>
                            <button
                                v-if="(t.enrolled || 0) === 0"
                                @click="removeCourse(t)"
                                class="px-3 py-2 bg-white border border-slate-200 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 rounded-xl text-[11px] font-bold transition-all"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                    <div v-if="trainingList.length === 0" class="md:col-span-2 xl:col-span-3 text-center py-16 bg-white rounded-2xl border border-slate-200/60">
                        <h3 class="text-base font-bold text-slate-900 mb-1">No Courses Found</h3>
                        <p class="text-xs text-slate-500">Create a course to start enrolling employees.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <div v-if="showFormModal" class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showFormModal = false">
            <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900">{{ form.id ? 'Edit Course' : 'New Course' }}</h3>
                    <button @click="showFormModal = false" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400"><X class="w-4 h-4" /></button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Name <span class="text-rose-500">*</span></label>
                        <input v-model="form.name" type="text" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Description</label>
                        <textarea v-model="form.description" rows="2" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                            <select v-model="form.status" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs">
                                <option>Draft</option>
                                <option>In Progress</option>
                                <option>Completed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Hours</label>
                            <input v-model.number="form.duration_hours" type="number" min="1" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cert valid (days)</label>
                            <input v-model.number="form.expiry_days" type="number" min="1" placeholder="Optional" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs" />
                        </div>
                    </div>
                    <button @click="submitForm" :disabled="saving" class="w-full py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl text-xs font-bold shadow-lg disabled:opacity-50">
                        {{ saving ? 'Saving...' : (form.id ? 'Save Changes' : 'Create Course') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Enroll Modal -->
        <div v-if="showEnrollModal" class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showEnrollModal = false">
            <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900">Enroll employees</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ enrollTarget?.name }}</p>
                </div>
                <div class="p-5 space-y-3 max-h-80 overflow-y-auto">
                    <label v-for="e in employeeOptions" :key="e.id" class="flex items-center gap-2 text-xs p-2 hover:bg-slate-50 rounded-lg cursor-pointer">
                        <input type="checkbox" :value="e.id" v-model="enrollIds" class="rounded" />
                        <span class="font-medium">{{ e.name }}</span>
                    </label>
                </div>
                <div class="p-5 border-t border-slate-100">
                    <button @click="submitEnroll" :disabled="saving || enrollIds.length === 0" class="w-full py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl text-xs font-bold disabled:opacity-50">
                        Enroll {{ enrollIds.length }} employee(s)
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { AlertTriangle, Users, Clock, X } from 'lucide-vue-next';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    trainings: { type: [Array, Object], default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    metrics: { type: [Array, Object], default: () => [] },
    filters: { type: Object, default: () => ({ search: '', status: '' }) },
    employees: { type: [Array, Object], default: () => [] },
});

const page = usePage();
const search = ref(props.filters?.search || '');
const filterStatus = ref(props.filters?.status || '');
const showFormModal = ref(false);
const showEnrollModal = ref(false);
const enrollTarget = ref(null);
const enrollIds = ref([]);
const saving = ref(false);
const form = ref({ id: null, name: '', description: '', status: 'In Progress', duration_hours: 4, expiry_days: null });

const asArray = (v) => (Array.isArray(v) ? v : (v?.data ?? [])) ?? [];
const trainingList = computed(() => asArray(props.trainings).filter((t) => t && typeof t === 'object' && t.id != null));
const metricList = computed(() => asArray(props.metrics));
const employeeOptions = computed(() => asArray(props.employees));

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
}, { immediate: true });

let searchTimer = null;
watch(search, () => { clearTimeout(searchTimer); searchTimer = setTimeout(applyFilter, 400); });

const applyFilter = () => {
    router.get(route('hrm.workforce.training.index'), {
        search: search.value.trim(), status: filterStatus.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const openCreate = () => {
    form.value = { id: null, name: '', description: '', status: 'In Progress', duration_hours: 4, expiry_days: null };
    showFormModal.value = true;
};

const openEdit = (t) => {
    if (!t?.id) return;
    form.value = { id: t.id, name: t.name, description: t.description, status: t.status, duration_hours: parseInt(t.duration) || 4, expiry_days: null };
    showFormModal.value = true;
};

const submitForm = () => {
    if (!form.value.name.trim()) { toast.error('Course name is required.'); return; }
    saving.value = true;
    const payload = { ...form.value };
    if (!payload.expiry_days) delete payload.expiry_days;
    if (payload.id) {
        router.put(route('hrm.workforce.training.update', payload.id), payload, {
            onSuccess: () => { toast.success('Course updated.'); showFormModal.value = false; },
            onError: () => toast.error('Failed to save course.'),
            onFinish: () => { saving.value = false; },
        });
    } else {
        router.post(route('hrm.workforce.training.store'), payload, {
            onSuccess: () => { toast.success('Course created.'); showFormModal.value = false; },
            onError: () => toast.error('Failed to create course.'),
            onFinish: () => { saving.value = false; },
        });
    }
};

const openEnroll = (t) => {
    if (!t?.id) return;
    enrollTarget.value = t;
    enrollIds.value = [];
    showEnrollModal.value = true;
};

const submitEnroll = () => {
    if (!enrollTarget.value?.id || enrollIds.value.length === 0) return;
    saving.value = true;
    router.post(route('hrm.workforce.training.enroll', enrollTarget.value.id), { user_ids: enrollIds.value }, {
        onSuccess: () => { toast.success('Employees enrolled.'); showEnrollModal.value = false; },
        onError: () => toast.error('Failed to enroll.'),
        onFinish: () => { saving.value = false; },
    });
};

const removeCourse = (t) => {
    if (!t?.id) return;
    if (!confirm(`Delete "${t.name}"?`)) return;
    router.delete(route('hrm.workforce.training.destroy', t.id), {
        onSuccess: () => toast.success('Course deleted.'),
        onError: () => toast.error('Failed to delete course.'),
    });
};
</script>

<style scoped>
.bg-grid-slate-100 {
    background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px);
    background-size: 24px 24px;
}
.bg-clip-text {
    -webkit-background-clip: text;
    background-clip: text;
}
.backdrop-blur-sm {
    backdrop-filter: blur(8px);
}
</style>
