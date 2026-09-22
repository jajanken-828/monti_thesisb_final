<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed, watch } from 'vue'
import {
    Search,
    Filter,
    MoreHorizontal,
    Download,
    DollarSign,
    TrendingUp,
    Clock,
    FileText,
    CheckCircle2,
    Calendar,
    AlertCircle,
    Plus,
    History,
    Banknote,
    X,
    Users,
    ChevronDown,
} from 'lucide-vue-next'

const props = defineProps({
    payroll_data: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total_payout: 0,
            pending_approvals: 0,
        })
    },
    permissions: {
        type: Object,
        default: () => ({})
    },
    // Optional: pass employees list for the modal
    employees: {
        type: Array,
        default: () => []
    }
})

// Check if user can edit payroll
const canEdit = computed(() => props.permissions?.payroll === 'edit')

// Search filter
const searchQuery = ref('')

// Filtered payroll data based on search
const filteredPayroll = computed(() => {
    if (!searchQuery.value) return props.payroll_data
    const q = searchQuery.value.toLowerCase()
    return props.payroll_data.filter(item =>
        (item.employee_name || '').toLowerCase().includes(q) ||
        String(item.employee_id).toLowerCase().includes(q) ||
        (item.role || '').toLowerCase().includes(q)
    )
})

// Status badge styling
const getStatusClass = (status) => {
    switch (status?.toLowerCase()) {
        case 'approved': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
        case 'pending': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
        case 'rejected': return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
        default: return 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400'
    }
}

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value)
}

// Get initials for avatar
const getInitials = (name) => {
    if (!name) return 'NA'
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

// ------------------------------------------------------------------
// Generate Payroll Modal
// ------------------------------------------------------------------
const showGenerateModal = ref(false)
const generateForm = useForm({
    cutoff_start: '',
    cutoff_end: '',
    employee_ids: [],
})

// Employee list for modal (either from prop or fetch via API)
const employeeList = ref(props.employees || [])
const loadingEmployees = ref(false)

const openGenerateModal = async () => {
    // If employees prop is empty, fetch from server
    if (employeeList.value.length === 0) {
        loadingEmployees.value = true
        try {
            const response = await fetch(route('hrm.payroll.employees.json'))
            const data = await response.json()
            employeeList.value = data
        } catch (error) {
            console.error('Failed to fetch employees', error)
        } finally {
            loadingEmployees.value = false
        }
    }
    showGenerateModal.value = true
}

const closeGenerateModal = () => {
    showGenerateModal.value = false
    generateForm.reset()
    generateForm.clearErrors()
}

const submitGenerate = () => {
    generateForm.post(route('hrm.payroll.generate.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeGenerateModal()
            // Optionally refresh payroll data via Inertia visit
            router.reload({ only: ['payroll_data', 'stats'] })
        },
    })
}

// Select/deselect all employees
const selectAll = (event) => {
    if (event.target.checked) {
        generateForm.employee_ids = employeeList.value.map(emp => emp.id)
    } else {
        generateForm.employee_ids = []
    }
}

// ------------------------------------------------------------------
// Approve / Reject Actions
// ------------------------------------------------------------------
const approvePayroll = (id) => {
    if (confirm('Approve this payroll record?')) {
        router.post(route('hrm.payroll.approve', id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Flash message handled by layout
            },
        })
    }
}

const rejectPayroll = (id) => {
    const reason = prompt('Enter rejection reason (optional):')
    router.post(route('hrm.payroll.reject', id), { reason }, {
        preserveScroll: true,
    })
}

// ------------------------------------------------------------------
// Quick stats cards computed from props.stats
// ------------------------------------------------------------------
const statCards = computed(() => [
    {
        label: 'Total Payout (Approved)',
        value: formatCurrency(props.stats.total_payout || 0),
        sub: 'Current period',
        icon: DollarSign,
        color: 'text-blue-600',
        bg: 'bg-blue-50'
    },
    {
        label: 'Pending Approvals',
        value: props.stats.pending_approvals || 0,
        sub: 'Requires attention',
        icon: AlertCircle,
        color: 'text-amber-600',
        bg: 'bg-amber-50'
    },
    {
        label: 'Avg. Net Pay',
        value: props.payroll_data.length
            ? formatCurrency(props.payroll_data.reduce((sum, p) => sum + parseFloat(p.net_pay || 0), 0) / props.payroll_data.length)
            : '₱0.00',
        sub: 'Per employee',
        icon: TrendingUp,
        color: 'text-emerald-600',
        bg: 'bg-emerald-50'
    },
    {
        label: 'Total Employees',
        value: props.payroll_data.length,
        sub: 'Processed this cutoff',
        icon: Users,
        color: 'text-purple-600',
        bg: 'bg-purple-50'
    },
])
</script>

<template>
    <Head title="HR Payroll Console" />

    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto p-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-10">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <span class="bg-blue-600 text-white text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-widest">
                            HR Admin
                        </span>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Payroll <span class="text-blue-600 font-light">Console</span>
                        </h1>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium italic">
                        Managing payroll cycles • <span class="text-emerald-500">System Online</span>
                    </p>
                    <!-- Permission indicator -->
                    <div v-if="!canEdit && permissions.payroll === 'view'" class="mt-2 text-xs text-amber-600 bg-amber-50 inline-block px-2 py-0.5 rounded-full">
                        View only access
                    </div>
                    <div v-else-if="canEdit" class="mt-2 text-xs text-emerald-600 bg-emerald-50 inline-block px-2 py-0.5 rounded-full">
                        Full access (can manage payroll)
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link :href="route('hrm.payroll.rates')" v-if="canEdit"
                        class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-sm text-slate-600 dark:text-slate-300 hover:shadow-md transition-all">
                        <Banknote class="h-4 w-4" />
                        Payroll Rates
                    </Link>
                    <button
                        class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-sm text-slate-600 dark:text-slate-300 hover:shadow-md transition-all">
                        <Download class="h-4 w-4" />
                        Export
                    </button>
                    <button v-if="canEdit" @click="openGenerateModal"
                        class="flex items-center gap-2 px-6 py-3 bg-slate-900 dark:bg-blue-600 hover:bg-black dark:hover:bg-blue-700 text-white rounded-2xl font-bold text-sm transition-all shadow-xl active:scale-95">
                        <Plus class="h-4 w-4" />
                        Generate Payroll
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="stat in statCards" :key="stat.label"
                    class="bg-white dark:bg-slate-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:border-blue-200 transition-colors group">
                    <div class="flex items-center justify-between mb-4">
                        <div :class="[stat.bg, 'p-3 rounded-2xl group-hover:scale-110 transition-transform']">
                            <component :is="stat.icon" :class="[stat.color, 'h-6 w-6']" />
                        </div>
                        <span class="text-[10px] font-black text-slate-300 group-hover:text-blue-500 transition-colors">DETAILS</span>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">{{ stat.label }}</p>
                        <p class="text-3xl font-black text-slate-900 dark:text-white">{{ stat.value }}</p>
                        <p class="mt-2 text-[11px] font-medium text-slate-400 flex items-center gap-1">
                            <CheckCircle2 class="h-3 w-3 text-emerald-500" />
                            {{ stat.sub }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Payroll Table -->
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                <!-- Search & Filters -->
                <div class="p-8 border-b border-slate-50 dark:border-slate-700 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="relative flex-1 max-w-xl">
                        <Search class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                        <input v-model="searchQuery" type="text" placeholder="Search by name, ID, or role..."
                            class="w-full pl-14 pr-6 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium" />
                    </div>

                    <div class="flex items-center gap-3">
                        <button class="flex items-center gap-2 px-5 py-4 bg-slate-50 dark:bg-slate-900 rounded-2xl font-bold text-xs text-slate-500 uppercase tracking-widest border border-transparent hover:border-slate-200 transition-all">
                            <Calendar class="h-4 w-4" />
                            All Periods
                        </button>
                        <button class="flex items-center gap-2 px-5 py-4 bg-slate-50 dark:bg-slate-900 rounded-2xl font-bold text-xs text-slate-500 uppercase tracking-widest border border-transparent hover:border-slate-200 transition-all">
                            <Filter class="h-4 w-4" />
                            Filter
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/50">
                                <th class="pl-8 pr-4 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ID</th>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Employee</th>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Gross Pay</th>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Deductions</th>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Net Pay</th>
                                <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                                <th v-if="canEdit" class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right pr-8">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-if="filteredPayroll.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No payroll records found. Click "Generate Payroll" to create a new run.
                                </td>
                            </tr>
                            <tr v-for="pay in filteredPayroll" :key="pay.id"
                                class="group hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all cursor-default">
                                <td class="pl-8 pr-4 py-6">
                                    <span class="text-xs font-black text-slate-400 group-hover:text-blue-600 transition-colors">
                                        {{ pay.employee_id }}
                                    </span>
                                </td>
                                <td class="px-6 py-6">
                                    <div class="flex items-center">
                                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center font-black text-sm mr-4 shadow-inner">
                                            {{ getInitials(pay.employee_name) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black text-slate-900 dark:text-white leading-tight">
                                                {{ pay.employee_name }}
                                            </span>
                                            <span class="text-[11px] font-bold text-blue-500 uppercase tracking-tighter">
                                                {{ pay.role }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                        {{ formatCurrency(pay.gross_pay) }}
                                    </span>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="text-sm text-slate-500">
                                        {{ formatCurrency(pay.late_total_deduction + pay.sss_deduction + pay.philhealth_deduction + pay.pagibig_deduction + (pay.tax_withheld || 0) + (pay.sss_loan || 0) + (pay.pf_loan || 0)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="text-sm font-black text-slate-900 dark:text-white">
                                        {{ formatCurrency(pay.net_pay) }}
                                    </span>
                                </td>
                                <td class="px-6 py-6">
                                    <span :class="[getStatusClass(pay.status), 'px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-[0.1em] inline-flex items-center gap-2 shadow-sm']">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                        {{ pay.status }}
                                    </span>
                                </td>
                                <td v-if="canEdit" class="px-6 py-6 text-right pr-8">
                                    <div class="flex items-center justify-end gap-2">
                                        <button v-if="pay.status === 'pending'" @click="approvePayroll(pay.id)"
                                            class="p-2.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-xl transition-all"
                                            title="Approve">
                                            <CheckCircle2 class="h-4 w-4" />
                                        </button>
                                        <button v-if="pay.status === 'pending'" @click="rejectPayroll(pay.id)"
                                            class="p-2.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-all"
                                            title="Reject">
                                            <X class="h-4 w-4" />
                                        </button>
                                        <Link :href="route('hrm.payroll.show', pay.id)"
                                            class="p-2.5 text-slate-400 hover:text-blue-600 hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-all shadow-sm border border-transparent hover:border-slate-100"
                                            title="View Details">
                                            <FileText class="h-4 w-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination placeholder -->
                <div class="px-8 py-6 bg-slate-50/80 dark:bg-slate-900/80 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.15em]">
                        <span>Showing {{ filteredPayroll.length }} records</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate Payroll Modal -->
        <Transition name="modal">
            <div v-if="showGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeGenerateModal">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                    <div class="bg-blue-600 p-5 text-white flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-black uppercase">Generate Payroll</h2>
                            <p class="text-blue-100 text-xs mt-1">Select cutoff period and employees</p>
                        </div>
                        <button @click="closeGenerateModal" class="p-2 hover:bg-white/20 rounded-full transition">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitGenerate" class="p-6 space-y-6 overflow-y-auto flex-1">
                        <!-- Cutoff Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-1">Cutoff Start <span class="text-red-500">*</span></label>
                                <input type="date" v-model="generateForm.cutoff_start" required
                                    class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 border-none focus:ring-2 focus:ring-blue-500 outline-none" />
                                <div v-if="generateForm.errors.cutoff_start" class="text-xs text-red-500 mt-1">{{ generateForm.errors.cutoff_start }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-1">Cutoff End <span class="text-red-500">*</span></label>
                                <input type="date" v-model="generateForm.cutoff_end" required
                                    class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 border-none focus:ring-2 focus:ring-blue-500 outline-none" />
                                <div v-if="generateForm.errors.cutoff_end" class="text-xs text-red-500 mt-1">{{ generateForm.errors.cutoff_end }}</div>
                            </div>
                        </div>

                        <!-- Employee Selection -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Select Employees</label>
                                <label class="flex items-center gap-2 text-xs">
                                    <input type="checkbox" @change="selectAll" :checked="generateForm.employee_ids.length === employeeList.length" class="rounded" />
                                    Select All
                                </label>
                            </div>
                            <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                                <div class="max-h-64 overflow-y-auto p-2 space-y-1">
                                    <div v-if="loadingEmployees" class="p-4 text-center text-slate-400">
                                        Loading employees...
                                    </div>
                                    <div v-else-if="employeeList.length === 0" class="p-4 text-center text-slate-400">
                                        No active employees found.
                                    </div>
                                    <label v-for="emp in employeeList" :key="emp.id" class="flex items-center gap-3 p-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-lg cursor-pointer">
                                        <input type="checkbox" :value="emp.id" v-model="generateForm.employee_ids" class="rounded" />
                                        <span class="text-sm font-medium">{{ emp.name }}</span>
                                        <span class="text-xs text-slate-400 ml-auto">{{ emp.role }}</span>
                                    </label>
                                </div>
                            </div>
                            <div v-if="generateForm.errors.employee_ids" class="text-xs text-red-500 mt-1">{{ generateForm.errors.employee_ids }}</div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <button type="button" @click="closeGenerateModal" class="px-6 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-sm hover:bg-slate-200 transition">
                                Cancel
                            </button>
                            <button type="submit" :disabled="generateForm.processing"
                                class="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg transition flex items-center gap-2 disabled:opacity-50">
                                <span v-if="generateForm.processing">Generating...</span>
                                <span v-else>Generate Payroll</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
    transition: transform 0.2s ease;
}
.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
    transform: scale(0.95);
}

.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}
.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .overflow-x-auto::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>