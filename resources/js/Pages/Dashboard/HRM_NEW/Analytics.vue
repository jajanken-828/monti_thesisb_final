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
                                    >Reports & Insights</span
                                >
                            </nav>
                            <h1
                                class="text-4xl font-bold tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent"
                            >
                                HR Analytics
                            </h1>
                            <p class="text-sm text-slate-500 mt-1.5">
                                Monitor HR KPIs and support workforce planning
                                with data-driven insights.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5"
                            >
                                <Upload class="w-4 h-4" /> Export Excel
                            </button>
                            <button
                                class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5"
                            >
                                <FileText class="w-4 h-4" /> Export PDF
                            </button>
                        </div>
                    </div>
                </header>

                <!-- KPI CARDS - Floating -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div
                        v-for="k in kpis"
                        :key="k.label"
                        class="group bg-white rounded-2xl border border-slate-200/60 p-5 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 hover:border-blue-200/60 shadow-md backdrop-blur-sm"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span
                                class="text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                >{{ k.label }}</span
                            >
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center group-hover:from-blue-100 group-hover:to-blue-200 transition-all duration-300 shadow-inner"
                            >
                                <component :is="k.icon" class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="text-2xl font-bold" :class="k.color">
                            {{ k.value }}
                        </div>
                        <div
                            class="text-xs mt-1 font-semibold"
                            :class="
                                k.trend > 0
                                    ? 'text-emerald-600'
                                    : 'text-rose-600'
                            "
                        >
                            {{ k.trend > 0 ? "↑" : "↓" }}
                            {{ Math.abs(k.trend) }}%
                        </div>
                    </div>
                </div>

                <!-- CHARTS GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Headcount by Department -->
                    <div
                        class="bg-white rounded-2xl border border-slate-200/60 shadow-lg p-6 backdrop-blur-sm"
                    >
                        <h3 class="text-lg font-bold text-slate-900 mb-5 flex items-center gap-2">
                            <BarChart3 class="w-5 h-5" /> Headcount by Department
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="d in deptData"
                                :key="d.name"
                                class="flex items-center gap-3"
                            >
                                <span
                                    class="text-xs font-medium w-24 text-slate-600"
                                    >{{ d.name }}</span
                                >
                                <div
                                    class="flex-1 h-7 bg-slate-100 rounded-full overflow-hidden shadow-inner"
                                >
                                    <div
                                        class="h-full rounded-full shadow-lg transition-all duration-500"
                                        :class="d.color"
                                        :style="{
                                            width:
                                                (d.count / maxDept) * 100 + '%',
                                        }"
                                    ></div>
                                </div>
                                <span
                                    class="text-xs font-bold w-10 text-right text-slate-800"
                                    >{{ d.count }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Hiring Funnel -->
                    <div
                        class="bg-white rounded-2xl border border-slate-200/60 shadow-lg p-6 backdrop-blur-sm"
                    >
                        <h3 class="text-lg font-bold text-slate-900 mb-5 flex items-center gap-2">
                            <TrendingUp class="w-5 h-5" /> Hiring Funnel
                        </h3>
                        <div class="space-y-3">
                            <div
                                v-for="f in funnel"
                                :key="f.stage"
                                class="flex items-center gap-3"
                            >
                                <span
                                    class="text-xs font-medium w-24 text-slate-600"
                                    >{{ f.stage }}</span
                                >
                                <div
                                    class="flex-1 h-8 rounded-full overflow-hidden shadow-inner"
                                    :class="f.bg"
                                >
                                    <div
                                        class="h-full flex items-center pl-3 text-xs font-bold rounded-full shadow-lg transition-all duration-500"
                                        :class="f.color"
                                        :style="{
                                            width:
                                                (f.count / funnel[0].count) *
                                                    100 +
                                                '%',
                                        }"
                                    >
                                        {{ f.count }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Upload, FileText, Users, RefreshCw, Timer, CheckCircle2, BarChart3, TrendingUp } from 'lucide-vue-next';

const kpis = [
    {
        label: "Total Employees",
        value: "1,245",
        icon: Users,
        trend: 12,
        color: "text-slate-900",
    },
    {
        label: "Turnover Rate",
        value: "3.2%",
        icon: RefreshCw,
        trend: -0.5,
        color: "text-amber-600",
    },
    {
        label: "Time to Hire",
        value: "28 Days",
        icon: Timer,
        trend: -5,
        color: "text-emerald-600",
    },
    {
        label: "Attendance Rate",
        value: "96.8%",
        icon: CheckCircle2,
        trend: 1.2,
        color: "text-blue-600",
    },
];

const deptData = [
    {
        name: "Engineering",
        count: 145,
        color: "bg-gradient-to-r from-blue-500 to-blue-400 shadow-blue-500/20",
    },
    {
        name: "Sales",
        count: 98,
        color: "bg-gradient-to-r from-emerald-500 to-emerald-400 shadow-emerald-500/20",
    },
    {
        name: "HR",
        count: 15,
        color: "bg-gradient-to-r from-purple-500 to-purple-400 shadow-purple-500/20",
    },
    {
        name: "Finance",
        count: 25,
        color: "bg-gradient-to-r from-amber-500 to-amber-400 shadow-amber-500/20",
    },
];

const maxDept = Math.max(...deptData.map((d) => d.count));

const funnel = [
    { stage: "Applied", count: 142, bg: "bg-blue-100", color: "text-blue-700" },
    {
        stage: "Screening",
        count: 45,
        bg: "bg-indigo-100",
        color: "text-indigo-700",
    },
    {
        stage: "Interview",
        count: 18,
        bg: "bg-purple-100",
        color: "text-purple-700",
    },
    { stage: "Offer", count: 6, bg: "bg-amber-100", color: "text-amber-700" },
    {
        stage: "Hired",
        count: 4,
        bg: "bg-emerald-100",
        color: "text-emerald-700",
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
