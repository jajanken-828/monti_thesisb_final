<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ArrowLeft, Banknote, Calendar, CheckCircle2, Users } from 'lucide-vue-next';

const props = defineProps({
    employees: { type: Array, default: () => [] },
});

const form = useForm({
    cutoff_start: '',
    cutoff_end: '',
    employee_ids: [],
});

const query = ref('');

const list = computed(() => {
    const q = query.value.trim().toLowerCase();
    const rows = props.employees || [];
    if (!q) return rows;
    return rows.filter((e) => (e.name || '').toLowerCase().includes(q) || String(e.id).includes(q));
});

const toggleAll = (e) => {
    form.employee_ids = e.target.checked ? list.value.map((emp) => emp.id) : [];
};

const submit = () => {
    form.post(route('hrm.payroll.generate.store'));
};
</script>

<template>
    <Head title="Generate Payroll" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto p-6">
            <Link :href="route('hrm.payroll')" class="text-xs text-slate-500 hover:text-slate-800 font-semibold">← Back to Payroll Console</Link>

            <div class="mt-3 flex items-center gap-3 mb-6">
                <div class="p-3 rounded-2xl bg-blue-600 text-white shadow-lg">
                    <Banknote class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Generate Payroll</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Select a cutoff period and employees to run payroll.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-1">Cutoff Start <span class="text-red-500">*</span></label>
                        <input type="date" v-model="form.cutoff_start" required
                            class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 border-none focus:ring-2 focus:ring-blue-500 outline-none" />
                        <div v-if="form.errors.cutoff_start" class="text-xs text-red-500 mt-1">{{ form.errors.cutoff_start }}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-1">Cutoff End <span class="text-red-500">*</span></label>
                        <input type="date" v-model="form.cutoff_end" required
                            class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 border-none focus:ring-2 focus:ring-blue-500 outline-none" />
                        <div v-if="form.errors.cutoff_end" class="text-xs text-red-500 mt-1">{{ form.errors.cutoff_end }}</div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-wider flex items-center gap-2">
                            <Users class="h-4 w-4" /> Select Employees ({{ form.employee_ids.length }} selected)
                        </label>
                        <input v-model="query" type="text" placeholder="Search employees..."
                            class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 text-sm border-none focus:ring-2 focus:ring-blue-500 outline-none" />
                    </div>
                    <label class="flex items-center gap-2 text-xs mb-2 cursor-pointer">
                        <input type="checkbox" @change="toggleAll" :checked="form.employee_ids.length === list.length && list.length > 0" class="rounded" />
                        Select All
                    </label>
                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                        <div class="max-h-72 overflow-y-auto p-2 space-y-1">
                            <div v-if="list.length === 0" class="p-4 text-center text-slate-400 text-sm">No active employees found.</div>
                            <label v-for="emp in list" :key="emp.id" class="flex items-center gap-3 p-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-lg cursor-pointer">
                                <input type="checkbox" :value="emp.id" v-model="form.employee_ids" class="rounded" />
                                <span class="text-sm font-medium">{{ emp.name }}</span>
                                <span class="text-xs text-slate-400 ml-auto flex items-center gap-1"><Calendar class="h-3 w-3" />{{ emp.role }}</span>
                            </label>
                        </div>
                    </div>
                    <div v-if="form.errors.employee_ids" class="text-xs text-red-500 mt-1">{{ form.errors.employee_ids }}</div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                    <Link :href="route('hrm.payroll')" class="px-6 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-sm hover:bg-slate-200 transition">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg transition flex items-center gap-2 disabled:opacity-50">
                        <CheckCircle2 class="h-4 w-4" />
                        <span v-if="form.processing">Generating...</span>
                        <span v-else>Generate Payroll</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
