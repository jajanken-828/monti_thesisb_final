<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Printer } from 'lucide-vue-next';

const props = defineProps({ generatedAt: String, kpis: Object, trends: Object, decisions: Array });

const peso = (v) => '₱' + new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(v ?? 0);
const num = (v) => new Intl.NumberFormat('en-US').format(v ?? 0);
const printPack = () => window.print();
</script>

<template>
    <Head title="Board Pack" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-white dark:bg-zinc-950">
            <div class="p-4 sm:p-8 max-w-5xl mx-auto space-y-8 pb-16 print:p-0 print:max-w-none">
                <div class="flex flex-wrap items-start gap-4 border-b-4 border-double border-gray-900 dark:border-gray-100 pb-6">
                    <div class="flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-gray-500">Monti Textile · Board of Directors</p>
                        <h1 class="text-3xl font-black tracking-tight mt-1">Monthly Board Pack</h1>
                        <p class="text-sm text-gray-500 mt-1">Generated {{ generatedAt }} · Confidential</p>
                    </div>
                    <button @click="printPack" class="print:hidden inline-flex items-center gap-1.5 rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-black uppercase text-white shadow-lg hover:bg-indigo-700 active:scale-95 transition"><Printer class="h-4 w-4" /> Print / PDF</button>
                </div>

                <section>
                    <h2 class="text-sm font-black uppercase tracking-widest border-b pb-2 mb-4">1 · Key Figures</h2>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-800">
                            <tr><td class="py-2 text-gray-500">Approved revenue (all time)</td><td class="py-2 text-right font-black">{{ peso(kpis.revenue_approved) }}</td></tr>
                            <tr><td class="py-2 text-gray-500">Procurement spend (all time)</td><td class="py-2 text-right font-black">{{ peso(kpis.procurement_spend) }}</td></tr>
                            <tr><td class="py-2 text-gray-500">Payroll cost (all time)</td><td class="py-2 text-right font-black">{{ peso(kpis.payroll_cost) }}</td></tr>
                            <tr><td class="py-2 text-gray-500">Unpaid supplier invoices</td><td class="py-2 text-right font-black">{{ peso(kpis.invoices_unpaid) }}</td></tr>
                            <tr><td class="py-2 text-gray-500">Fabrics produced</td><td class="py-2 text-right font-black">{{ num(kpis.fabrics_total) }}</td></tr>
                            <tr><td class="py-2 text-gray-500">Reject rate</td><td class="py-2 text-right font-black">{{ kpis.reject_rate }}%</td></tr>
                            <tr><td class="py-2 text-gray-500">Jobs in production</td><td class="py-2 text-right font-black">{{ num(kpis.jobs_in_production) }}</td></tr>
                            <tr><td class="py-2 text-gray-500">Packages delivered</td><td class="py-2 text-right font-black">{{ num(kpis.packages_delivered) }}</td></tr>
                            <tr><td class="py-2 text-gray-500">Leads open / won</td><td class="py-2 text-right font-black">{{ num(kpis.leads_open) }} / {{ num(kpis.leads_won) }}</td></tr>
                            <tr><td class="py-2 text-gray-500">Active headcount</td><td class="py-2 text-right font-black">{{ num(kpis.headcount) }}</td></tr>
                            <tr><td class="py-2 text-gray-500">Machines down</td><td class="py-2 text-right font-black">{{ num(kpis.machines_down) }}</td></tr>
                        </tbody>
                    </table>
                </section>

                <section>
                    <h2 class="text-sm font-black uppercase tracking-widest border-b pb-2 mb-4">2 · Twelve-Month Trend</h2>
                    <table class="w-full text-xs">
                        <thead><tr class="text-left text-gray-500 uppercase">
                            <th class="py-2">Month</th><th class="py-2 text-right">Revenue</th><th class="py-2 text-right">Spend</th>
                            <th class="py-2 text-right">Output</th><th class="py-2 text-right">Rejects</th><th class="py-2 text-right">Payroll</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-800">
                            <tr v-for="(label, i) in trends.labels" :key="label">
                                <td class="py-1.5 font-bold">{{ label }}</td>
                                <td class="py-1.5 text-right">{{ peso(trends.revenue[i]) }}</td>
                                <td class="py-1.5 text-right">{{ peso(trends.spend[i]) }}</td>
                                <td class="py-1.5 text-right">{{ num(trends.output[i]) }}</td>
                                <td class="py-1.5 text-right">{{ num(trends.rejects[i]) }}</td>
                                <td class="py-1.5 text-right">{{ peso(trends.payroll[i]) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section>
                    <h2 class="text-sm font-black uppercase tracking-widest border-b pb-2 mb-4">3 · Executive Decisions</h2>
                    <ul class="space-y-2 text-sm">
                        <li v-for="d in decisions" :key="d.id" class="flex flex-wrap gap-x-3 gap-y-0.5">
                            <span class="font-black uppercase text-[11px]" :class="d.decision === 'approved' ? 'text-emerald-700' : 'text-rose-700'">{{ d.decision }}</span>
                            <span class="font-bold">{{ d.subject_label }}</span>
                            <span class="text-gray-500 ml-auto">{{ d.actor?.name }} · {{ new Date(d.created_at).toLocaleDateString() }}</span>
                            <span v-if="d.reason" class="w-full text-gray-500 text-xs">Reason: {{ d.reason }}</span>
                        </li>
                        <li v-if="!decisions?.length" class="text-gray-400 text-sm">No decisions recorded in this period.</li>
                    </ul>
                </section>

                <p class="text-[11px] text-gray-400 pt-4 border-t">Monti Textile ERP · system-generated from live module data · {{ generatedAt }}</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
