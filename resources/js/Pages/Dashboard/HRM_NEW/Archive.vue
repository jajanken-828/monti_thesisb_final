<template>
    <AuthenticatedLayout>
        <div
            class="flex min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-blue-50"
        >
            <Hrsidebar />

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
                                    >Records</span
                                >
                            </nav>
                            <h1
                                class="text-4xl font-bold tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent"
                            >
                                Archive
                            </h1>
                            <p class="text-sm text-slate-500 mt-1.5">
                                Store inactive HR records while preserving
                                historical data for audits and compliance.
                            </p>
                        </div>
                    </div>
                </header>

                <!-- METRICS - Floating Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div
                        v-for="m in metrics"
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

                <!-- TABS -->
                <div
                    class="bg-white rounded-2xl border border-slate-200/60 shadow-lg hover:shadow-2xl transition-all duration-300 backdrop-blur-sm overflow-hidden"
                >
                    <div class="flex border-b border-slate-100 overflow-x-auto">
                        <button
                            v-for="tab in tabs"
                            :key="tab"
                            @click="activeTab = tab"
                            :class="[
                                'px-5 py-3.5 text-xs font-semibold transition-all duration-200 whitespace-nowrap',
                                activeTab === tab
                                    ? 'text-blue-600 border-b-2 border-blue-600 bg-gradient-to-r from-blue-50/50 to-transparent'
                                    : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50',
                            ]"
                        >
                            {{ tab }}
                        </button>
                    </div>

                    <!-- SEARCH & FILTER -->
                    <div class="p-4 border-b border-slate-100 flex gap-3">
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3"
                                ><Search class="w-4 h-4" /></span
                            >
                            <input
                                type="text"
                                placeholder="Search archives..."
                                class="w-64 pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            />
                        </div>
                        <input
                            type="date"
                            class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 shadow-sm transition-all duration-200"
                        />
                    </div>

                    <!-- ARCHIVE TABLE -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="bg-gradient-to-r from-slate-50 to-transparent border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                >
                                    <th class="py-3 px-5">Record</th>
                                    <th class="py-3 px-5">Module</th>
                                    <th class="py-3 px-5">Archived By</th>
                                    <th class="py-3 px-5">Date Archived</th>
                                    <th class="py-3 px-5 text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr
                                    v-for="a in archives"
                                    :key="a.id"
                                    class="hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-transparent transition-all duration-200"
                                >
                                    <td
                                        class="py-3 px-5 font-semibold text-slate-800"
                                    >
                                        {{ a.name }}
                                    </td>
                                    <td class="py-3 px-5">
                                        <span
                                            class="px-2.5 py-1 bg-gradient-to-br from-slate-100 to-slate-200 text-slate-600 rounded-lg text-[10px] font-medium shadow-sm"
                                            >{{ a.module }}</span
                                        >
                                    </td>
                                    <td class="py-3 px-5 text-slate-500">
                                        {{ a.archived_by }}
                                    </td>
                                    <td class="py-3 px-5 text-slate-500">
                                        {{ a.date }}
                                    </td>
                                    <td class="py-3 px-5">
                                        <div class="flex justify-end gap-1">
                                            <button
                                                class="px-3 py-1.5 bg-gradient-to-r from-emerald-50 to-emerald-100 hover:from-emerald-100 hover:to-emerald-200 text-emerald-700 rounded-xl text-[10px] font-semibold transition-all duration-200 shadow-sm hover:shadow-md border border-emerald-200"
                                            >
                                                <Undo2 class="w-4 h-4 inline" /> Restore
                                            </button>
                                            <button
                                                class="px-3 py-1.5 bg-gradient-to-r from-rose-50 to-rose-100 hover:from-rose-100 hover:to-rose-200 text-rose-700 rounded-xl text-[10px] font-semibold transition-all duration-200 shadow-sm hover:shadow-md border border-rose-200"
                                            >
                                                <Trash2 class="w-4 h-4 inline" /> Delete
                                            </button>
                                        </div>
                                    </td>
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
import { ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Search, Undo2, Trash2 } from 'lucide-vue-next';

const activeTab = ref("Employees");
const tabs = [
    "Employees",
    "Departments",
    "Positions",
    "Recruitment",
    "Onboarding",
    "Payroll",
    "Performance",
    "Training",
];

const metrics = [
    { label: "Total Archives", value: "156" },
    { label: "This Month", value: "12" },
    { label: "Restored", value: "8" },
    { label: "Permanent Deletes", value: "3" },
];

const archives = [
    {
        id: 1,
        name: "Legacy Operations Dept",
        module: "Departments",
        archived_by: "Admin",
        date: "2025-06-15",
    },
    {
        id: 2,
        name: "Robert Kim",
        module: "Employees",
        archived_by: "HR Manager",
        date: "2025-08-20",
    },
];
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
