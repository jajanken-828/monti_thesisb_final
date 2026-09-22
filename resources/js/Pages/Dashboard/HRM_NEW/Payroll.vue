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
                                    >Compensation</span
                                >
                            </nav>
                            <h1
                                class="text-4xl font-bold tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent"
                            >
                                Payroll
                            </h1>
                            <p class="text-sm text-slate-500 mt-1.5">
                                Calculate salaries, deductions, taxes, benefits,
                                and generate payslips.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white rounded-xl text-sm font-bold transition-all duration-200 shadow-lg hover:shadow-xl hover:shadow-blue-500/25 hover:-translate-y-0.5"
                            >
                                <Banknote class="w-4 h-4" /> Generate Payroll
                            </button>
                            <button
                                class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5"
                            >
                                <FileText class="w-4 h-4" /> Export
                            </button>
                        </div>
                    </div>
                </header>

                <!-- PAYROLL SUMMARY CARDS - Floating -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div
                        v-for="s in summary"
                        :key="s.label"
                        class="group bg-white rounded-2xl border border-slate-200/60 p-5 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 hover:border-blue-200/60 shadow-md backdrop-blur-sm"
                    >
                        <span
                            class="text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >{{ s.label }}</span
                        >
                        <div class="text-2xl font-bold mt-2" :class="s.color">
                            {{ s.value }}
                        </div>
                    </div>
                </div>

                <!-- WIZARD STEPS - Floating Card -->
                <div
                    class="bg-white rounded-2xl border border-slate-200/60 shadow-lg p-6 mb-8 backdrop-blur-sm"
                >
                    <h3 class="text-lg font-bold text-slate-900 mb-5">
                        Payroll Processing
                    </h3>
                    <div class="flex items-center gap-2">
                        <div
                            v-for="(step, i) in steps"
                            :key="step"
                            class="flex items-center gap-2 flex-1"
                        >
                            <div
                                :class="[
                                    'w-10 h-10 rounded-xl flex items-center justify-center text-xs font-bold transition-all duration-300 shadow-md',
                                    i <= currentStep
                                        ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-blue-500/25'
                                        : 'bg-slate-100 text-slate-400',
                                ]"
                            >
                                {{ i + 1 }}
                            </div>
                            <span
                                class="text-xs"
                                :class="
                                    i <= currentStep
                                        ? 'text-slate-800 font-semibold'
                                        : 'text-slate-400'
                                "
                                >{{ step }}</span
                            >
                            <div
                                v-if="i < steps.length - 1"
                                class="flex-1 h-1 rounded-full"
                                :class="
                                    i < currentStep
                                        ? 'bg-gradient-to-r from-blue-500 to-blue-400'
                                        : 'bg-slate-200'
                                "
                            ></div>
                        </div>
                    </div>
                    <button
                        @click="
                            currentStep = Math.min(
                                currentStep + 1,
                                steps.length - 1,
                            )
                        "
                        class="mt-5 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white rounded-xl text-sm font-bold transition-all duration-200 shadow-lg hover:shadow-xl hover:shadow-blue-500/25"
                    >
                        Next Step →
                    </button>
                </div>

                <!-- PAYSLIPS TABLE -->
                <div
                    class="bg-white rounded-2xl border border-slate-200/60 shadow-lg hover:shadow-2xl transition-all duration-300 backdrop-blur-sm overflow-hidden"
                >
                    <div
                        class="p-5 border-b border-slate-100 bg-gradient-to-r from-slate-50/50 to-transparent"
                    >
                        <h3 class="text-lg font-bold text-slate-900">
                            Recent Payslips
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="bg-gradient-to-r from-slate-50 to-transparent border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                >
                                    <th class="py-3 px-5">Employee</th>
                                    <th class="py-3 px-5">Period</th>
                                    <th class="py-3 px-5">Basic Salary</th>
                                    <th class="py-3 px-5">Deductions</th>
                                    <th class="py-3 px-5 text-right">
                                        Net Pay
                                    </th>
                                    <th class="py-3 px-5 text-center">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr
                                    v-for="p in payslips"
                                    :key="p.id"
                                    class="hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-transparent transition-all duration-200"
                                >
                                    <td class="py-3 px-5">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center text-[10px] font-bold text-blue-600 shadow-inner"
                                            >
                                                {{
                                                    p.employee
                                                        .split(" ")
                                                        .map((n) => n[0])
                                                        .join("")
                                                }}
                                            </div>
                                            <span
                                                class="font-semibold text-slate-800"
                                                >{{ p.employee }}</span
                                            >
                                        </div>
                                    </td>
                                    <td class="py-3 px-5 text-slate-500">
                                        {{ p.period }}
                                    </td>
                                    <td class="py-3 px-5 font-semibold">
                                        ${{ p.basic }}
                                    </td>
                                    <td
                                        class="py-3 px-5 text-rose-600 font-semibold"
                                    >
                                        -${{ p.deductions }}
                                    </td>
                                    <td
                                        class="py-3 px-5 text-right font-bold text-emerald-600"
                                    >
                                        ${{ p.net }}
                                    </td>
                                    <td class="py-3 px-5 text-center">
                                        <button
                                            class="px-3 py-1.5 bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 text-blue-700 rounded-xl text-[10px] font-semibold transition-all duration-200 shadow-sm hover:shadow-md"
                                        >
                                            <Printer class="w-4 h-4 inline" /> Print
                                        </button>
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
import { Banknote, FileText, Printer } from 'lucide-vue-next';

const currentStep = ref(1);
const steps = ["Generate", "Calculate", "Approve", "Finalize"];

const summary = [
    { label: "Total Payroll", value: "$2,450,000", color: "text-slate-900" },
    { label: "Total Employees", value: "1,245", color: "text-blue-600" },
    { label: "Avg Salary", value: "$4,167", color: "text-emerald-600" },
    { label: "Next Payroll", value: "Jul 25", color: "text-amber-600" },
];

const payslips = [
    {
        id: 1,
        employee: "John Smith",
        period: "Jul 1-15, 2026",
        basic: "5,416",
        deductions: "1,200",
        net: "4,216",
    },
    {
        id: 2,
        employee: "Maria Garcia",
        period: "Jul 1-15, 2026",
        basic: "6,250",
        deductions: "1,500",
        net: "4,750",
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
