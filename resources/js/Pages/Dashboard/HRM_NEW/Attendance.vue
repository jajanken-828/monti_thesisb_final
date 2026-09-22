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
                                    >Time & Attendance</span
                                >
                            </nav>
                            <h1
                                class="text-4xl font-bold tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent"
                            >
                                Attendance
                            </h1>
                            <p class="text-sm text-slate-500 mt-1.5">
                                Live clock records from the employee portal —
                                same data as Workforce scheduling.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative group">
                                <svg
                                    class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-hover:text-blue-500 transition-colors"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                <input
                                    v-model="selectedDate"
                                    @change="applyFilter"
                                    type="date"
                                    class="pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm hover:shadow-md transition-all duration-200"
                                />
                            </div>
                            <button
                                @click="exportCsv"
                                class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5"
                            >
                                <Upload class="w-4 h-4" />
                                Export
                            </button>
                        </div>
                    </div>
                </header>

                <!-- KPI METRICS ROW - Floating Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div
                        v-for="m in metricCards"
                        :key="m.label"
                        class="group bg-white rounded-2xl border border-slate-200/60 p-5 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 hover:border-blue-200/60 shadow-md backdrop-blur-sm"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span
                                class="text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                >{{ m.label }}</span
                            >
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center group-hover:from-blue-100 group-hover:to-blue-200 transition-all duration-300 shadow-inner"
                            >
                                <component :is="m.icon" class="w-5 h-5" :class="m.color" />
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-slate-900">
                            {{ m.value }}
                        </div>
                    </div>
                </div>

                <!-- ATTENDANCE TABLE -->
                <div
                    class="bg-white rounded-2xl border border-slate-200/60 shadow-lg hover:shadow-2xl transition-all duration-300 backdrop-blur-sm overflow-hidden"
                >
                    <div
                        class="p-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4"
                    >
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center pl-3"
                                    ><Search class="w-4 h-4 text-slate-400" /></span
                                >
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search employee..."
                                    class="w-64 pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                />
                            </div>
                        </div>
                        <select
                            v-model="department"
                            @change="applyFilter"
                            class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-600 focus:ring-2 focus:ring-blue-500 shadow-sm transition-all duration-200 cursor-pointer"
                        >
                            <option value="">All Departments</option>
                            <option v-for="d in departmentOptions" :key="d.code" :value="d.code">{{ d.name }}</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="bg-gradient-to-r from-slate-50 to-transparent border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                >
                                    <th class="py-3 px-5">Employee</th>
                                    <th class="py-3 px-5">Date</th>
                                    <th class="py-3 px-5">Time In</th>
                                    <th class="py-3 px-5">Time Out</th>
                                    <th class="py-3 px-5">Hours</th>
                                    <th class="py-3 px-5">OT</th>
                                    <th class="py-3 px-5 text-center">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="a in recordList"
                                    :key="a.id"
                                    class="hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-transparent transition-all duration-200 text-xs"
                                >
                                    <td class="py-3 px-5">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center text-[10px] font-bold text-blue-600 shadow-inner"
                                            >
                                                {{ initials(a.employee) }}
                                            </div>
                                            <span
                                                class="font-semibold text-slate-800"
                                                >{{ a.employee }}</span
                                            >
                                        </div>
                                    </td>
                                    <td class="py-3 px-5 text-slate-500">
                                        {{ a.date }}
                                    </td>
                                    <td class="py-3 px-5 font-medium">
                                        {{ a.time_in }}
                                    </td>
                                    <td class="py-3 px-5 font-medium">
                                        {{ a.time_out }}
                                    </td>
                                    <td class="py-3 px-5 font-semibold">
                                        {{ a.hours }}
                                    </td>
                                    <td
                                        class="py-3 px-5"
                                        :class="
                                            Number(a.ot) > 0
                                                ? 'text-amber-600 font-semibold'
                                                : 'text-slate-400'
                                        "
                                    >
                                        {{ a.ot }}h
                                    </td>
                                    <td class="py-3 px-5 text-center">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold shadow-sm',
                                                a.status === 'Present'
                                                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                    : a.status === 'Late'
                                                      ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                                      : 'bg-rose-50 text-rose-700 border border-rose-200',
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'w-1.5 h-1.5 rounded-full',
                                                    a.status === 'Present'
                                                        ? 'bg-emerald-500'
                                                        : a.status === 'Late'
                                                          ? 'bg-amber-500'
                                                          : 'bg-rose-500',
                                                ]"
                                            ></span>
                                            {{ a.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="recordList.length === 0">
                                    <td colspan="7" class="py-10 text-center text-sm text-slate-400">No attendance records for this date.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { AlertTriangle, CheckCircle2, Search, TreePalm, Upload, XCircle } from "lucide-vue-next";

const props = defineProps({
    records: { type: [Array, Object], default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    metrics: { type: [Array, Object], default: () => [] },
    filters: { type: Object, default: () => ({ date: '', search: '', department: '' }) },
    departments: { type: [Array, Object], default: () => [] },
});

const selectedDate = ref(props.filters?.date || new Date().toISOString().slice(0, 10));
const search = ref(props.filters?.search || '');
const department = ref(props.filters?.department || '');

const asArray = (v) => (Array.isArray(v) ? v : (v?.data ?? [])) ?? [];
const recordList = computed(() => asArray(props.records).filter((r) => r && typeof r === 'object' && r.id != null));
const departmentOptions = computed(() => asArray(props.departments));

const iconFor = (label) => ({
    'Present Today': CheckCircle2, 'Late': AlertTriangle, 'Absent': XCircle, 'On Leave': TreePalm,
}[label] || CheckCircle2);
const colorFor = (label) => ({
    'Present Today': 'text-emerald-600', 'Late': 'text-amber-600', 'Absent': 'text-rose-600', 'On Leave': 'text-blue-600',
}[label] || 'text-blue-600');

const metricCards = computed(() => asArray(props.metrics).map((m) => ({
    ...m, icon: iconFor(m.label), color: colorFor(m.label),
})));

const initials = (name) => String(name || '?').split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();

let searchTimer = null;
watch(search, () => { clearTimeout(searchTimer); searchTimer = setTimeout(applyFilter, 400); });

const applyFilter = () => {
    router.get(route('hrm.workforce.attendance.index'), {
        date: selectedDate.value, search: search.value.trim(), department: department.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const exportCsv = () => {
    window.location.href = route('hrm.workforce.attendance.export', { date: selectedDate.value });
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
