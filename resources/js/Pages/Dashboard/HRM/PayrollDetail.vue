<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ArrowLeft, CheckCircle2, FileText, X } from 'lucide-vue-next';

const props = defineProps({
    payroll: { type: Object, required: true },
});

const fmt = (v) => '₱' + Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const rows = [
    ['Base Salary', props.payroll?.base_salary],
    ['Days Worked', props.payroll?.days_worked, true],
    ['Daily Rate', props.payroll?.daily_rate],
    ['Days Amount', props.payroll?.total_days_amt],
    ['Night Differential', props.payroll?.night_amt],
    ['Overtime', props.payroll?.ot_amt],
    ['Sunday / Rest Day', props.payroll?.sun_sp_amt],
    ['Holiday Amount', props.payroll?.holiday_amt],
    ['Late Deduction', props.payroll?.late_total_deduction],
    ['SSS', props.payroll?.sss_deduction],
    ['PhilHealth', props.payroll?.philhealth_deduction],
    ['Pag-IBIG', props.payroll?.pagibig_deduction],
    ['Withholding Tax', props.payroll?.tax_withheld],
    ['SSS Loan', props.payroll?.sss_loan],
    ['PF Loan', props.payroll?.pf_loan],
];

const approve = () => {
    if (confirm('Approve this payroll record?')) {
        router.post(route('hrm.payroll.approve', props.payroll.id));
    }
};

const reject = () => {
    const reason = prompt('Enter rejection reason (optional):');
    router.post(route('hrm.payroll.reject', props.payroll.id), { reason });
};
</script>

<template>
    <Head title="Payroll Detail" />

    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto p-6">
            <Link :href="route('hrm.payroll')" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-800 font-semibold">
                <ArrowLeft class="h-3 w-3" /> Back to Payroll Console
            </Link>

            <div class="mt-3 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center font-black text-slate-600 dark:text-slate-300 shadow-inner">
                            <FileText class="h-6 w-6" />
                        </div>
                        <div>
                            <h1 class="text-xl font-black text-slate-900 dark:text-white">{{ payroll?.employee_name }}</h1>
                            <p class="text-xs text-slate-400">{{ payroll?.employee_id }} • {{ payroll?.role }} • <span class="uppercase font-bold">{{ payroll?.status }}</span></p>
                        </div>
                    </div>
                    <div v-if="payroll?.status === 'pending'" class="flex gap-2">
                        <button @click="approve" class="inline-flex items-center gap-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
                            <CheckCircle2 class="h-4 w-4" /> Approve
                        </button>
                        <button @click="reject" class="inline-flex items-center gap-1 px-4 py-2 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-xl text-xs font-bold transition">
                            <X class="h-4 w-4" /> Reject
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div v-for="[label, val, plain] in rows" :key="label"
                        class="flex items-center justify-between py-2.5 border-b border-slate-50 dark:border-slate-700/50 last:border-0 text-sm">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">{{ label }}</span>
                        <span class="font-bold text-slate-800 dark:text-slate-100">{{ plain ? val : fmt(val) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-4 mt-2 bg-slate-50 dark:bg-slate-900/60 rounded-xl px-4">
                        <span class="text-sm font-black uppercase tracking-wider text-slate-500">Gross Pay</span>
                        <span class="text-lg font-black text-slate-900 dark:text-white">{{ fmt(payroll?.gross_pay) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-4 mt-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl px-4">
                        <span class="text-sm font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Net Pay</span>
                        <span class="text-xl font-black text-emerald-700 dark:text-emerald-300">{{ fmt(payroll?.net_pay) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
