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
                                    >Performance</span
                                >
                            </nav>
                            <h1
                                class="text-4xl font-bold tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent"
                            >
                                Performance Management
                            </h1>
                            <p class="text-sm text-slate-500 mt-1.5">
                                Measure employee performance through KPIs,
                                evaluations, goals, and feedback.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button
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
                                Create Review
                            </button>
                        </div>
                    </div>
                </header>

                <!-- METRICS - Floating Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div
                        v-for="m in metrics"
                        :key="m.label"
                        class="group bg-white rounded-2xl border border-slate-200/60 p-5 text-center transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 hover:border-blue-200/60 shadow-md backdrop-blur-sm"
                    >
                        <div
                            class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center mx-auto group-hover:from-blue-100 group-hover:to-blue-200 transition-all duration-300 shadow-inner"
                        >
                            <component :is="m.icon" class="w-5 h-5" :class="m.color" />
                        </div>
                        <div class="text-2xl font-bold mt-3" :class="m.color">
                            {{ m.value }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ m.label }}</p>
                    </div>
                </div>

                <!-- PERFORMANCE CARDS -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-8"
                >
                    <div
                        v-for="p in performances"
                        :key="p.id"
                        class="bg-white rounded-2xl border border-slate-200/60 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 backdrop-blur-sm p-5"
                    >
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-purple-500/20"
                                >
                                    {{ p.avatar }}
                                </div>
                                <div>
                                    <h3
                                        class="text-sm font-bold text-slate-900"
                                    >
                                        {{ p.employee }}
                                    </h3>
                                    <p class="text-xs text-slate-500">
                                        {{ p.position }}
                                    </p>
                                </div>
                            </div>
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold border shadow-sm',
                                    p.status === 'Completed'
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-amber-50 text-amber-700 border-amber-200',
                                ]"
                            >
                                <span
                                    :class="[
                                        'w-1.5 h-1.5 rounded-full',
                                        p.status === 'Completed'
                                            ? 'bg-emerald-500'
                                            : 'bg-amber-500 animate-pulse',
                                    ]"
                                ></span>
                                {{ p.status }}
                            </span>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500"
                                    >Overall Rating</span
                                ><span class="font-bold text-slate-800"
                                    >{{ p.rating }}/5</span
                                >
                            </div>
                            <div class="flex gap-0.5">
                                <Star
                                    v-for="i in 5"
                                    :key="i"
                                    class="w-4 h-4"
                                    :class="
                                        i <= p.rating
                                            ? 'text-amber-400 drop-shadow-sm'
                                            : 'text-slate-200'
                                    "
                                />
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500"
                                    >KPI Achievement</span
                                ><span class="font-semibold text-emerald-600"
                                    >{{ p.kpi }}%</span
                                >
                            </div>
                            <div
                                class="w-full h-2 bg-slate-100 rounded-full overflow-hidden shadow-inner"
                            >
                                <div
                                    class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full shadow-lg shadow-emerald-500/20 transition-all duration-500"
                                    :style="{ width: p.kpi + '%' }"
                                ></div>
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
import { CheckCircle2, Star, TrendingDown, Trophy } from "lucide-vue-next";

const metrics = [
    {
        label: "Avg Performance",
        value: "4.2",
        icon: Star,
        color: "text-amber-500",
    },
    {
        label: "Reviews Completed",
        value: "85%",
        icon: CheckCircle2,
        color: "text-emerald-600",
    },
    {
        label: "High Performers",
        value: "15",
        icon: Trophy,
        color: "text-blue-600",
    },
    {
        label: "Needs Improvement",
        value: "3",
        icon: TrendingDown,
        color: "text-rose-600",
    },
];

const performances = [
    {
        id: 1,
        employee: "John Smith",
        avatar: "JS",
        position: "Sr Engineer",
        rating: 4.5,
        kpi: 92,
        status: "Completed",
    },
    {
        id: 2,
        employee: "Maria Garcia",
        avatar: "MG",
        position: "Product Manager",
        rating: 3.8,
        kpi: 78,
        status: "In Review",
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
