<template>
    <AuthenticatedLayout>
        <Head title="Order Calendar" />
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6 pb-16">

                <!-- Hero header -->
                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl animate-float-delayed" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                    <div class="relative flex flex-wrap items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg animate-pop">
                            <CalendarDays class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <Sparkles class="h-3.5 w-3.5" /> ORD · Scheduling
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Order Calendar</h1>
                            <p class="text-sm text-blue-100/90">All purchase and sales orders by expected date</p>
                        </div>

                        <!-- Month Navigation -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="prevMonth"
                                class="flex h-9 w-9 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/25 backdrop-blur transition-all duration-200 hover:bg-white/25 active:scale-95"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </button>
                            <div class="min-w-[9rem] rounded-2xl bg-white/95 px-4 py-2 text-center shadow-lg">
                                <p class="font-black text-gray-900 text-sm">{{ currentMonthName }}</p>
                                <p class="text-[11px] font-bold text-indigo-600">{{ currentYear }}</p>
                            </div>
                            <button
                                @click="nextMonth"
                                class="flex h-9 w-9 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/25 backdrop-blur transition-all duration-200 hover:bg-white/25 active:scale-95"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="relative mt-6 flex flex-wrap items-center gap-2 text-xs font-bold">
                        <span class="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 ring-1 ring-white/25 backdrop-blur">
                            <span class="h-2 w-2 rounded-full bg-blue-300 animate-pulse" /> PO — Purchase Order
                        </span>
                        <span class="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 ring-1 ring-white/25 backdrop-blur">
                            <span class="h-2 w-2 rounded-full bg-amber-300 animate-pulse" /> SO — Sales Order
                        </span>
                    </div>
                </div>

                <!-- Calendar -->
                <div class="animate-fade-up relative overflow-hidden bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 transition-all duration-300" style="animation-delay: 100ms">
                    <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 hover:opacity-100 transition-opacity duration-500" />
                    <!-- Week Day Headers -->
                    <div class="grid grid-cols-7 border-b border-gray-100 dark:border-zinc-800">
                        <div
                            v-for="day in weekDays"
                            :key="day"
                            class="py-3 text-center text-[10px] sm:text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500"
                        >
                            <span class="hidden sm:inline">{{ day }}</span>
                            <span class="sm:hidden">{{ day.charAt(0) }}</span>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="grid grid-cols-7 divide-x divide-y divide-gray-100 dark:divide-zinc-800">
                        <div
                            v-for="(day, idx) in calendarDays"
                            :key="idx"
                            class="min-h-[72px] sm:min-h-[110px] p-1 sm:p-2 relative transition-colors hover:bg-indigo-50/50 dark:hover:bg-indigo-950/30"
                            :class="day.isCurrentMonth ? 'bg-white/60 dark:bg-zinc-900/60' : 'bg-slate-50/80 dark:bg-zinc-950/60'"
                        >
                            <!-- Day Number -->
                            <div
                                class="text-xs sm:text-sm font-bold leading-none mb-1"
                                :class="day.isCurrentMonth ? 'text-gray-800 dark:text-gray-100' : 'text-gray-300 dark:text-zinc-600'"
                            >
                                {{ day.date }}
                            </div>

                            <!-- Order Events -->
                            <div class="space-y-0.5 sm:space-y-1">
                                <div
                                    v-for="order in day.orders"
                                    :key="order.id"
                                    @click="openOrderModal(order)"
                                    class="text-[9px] sm:text-[10px] px-1 sm:px-1.5 py-0.5 sm:py-1 rounded-md sm:rounded-lg cursor-pointer truncate font-bold transition-all hover:scale-[1.03] hover:shadow-md active:scale-95 flex items-center gap-1"
                                    :class="order.type === 'PO'
                                        ? 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/30'
                                        : 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500/30'"
                                    :title="`${order.number} - ${order.client_name}`"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse flex-shrink-0" />
                                    <span class="hidden sm:inline truncate">{{ order.type }} {{ order.number }}</span>
                                    <span class="sm:hidden">{{ order.type }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Order Detail Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="selectedOrder"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm"
                    @click.self="selectedOrder = null"
                >
                    <div
                        class="bg-white dark:bg-zinc-900 w-full sm:max-w-lg rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800"
                    >
                        <!-- Handle (mobile) -->
                        <div class="sm:hidden flex justify-center pt-3 pb-1">
                            <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-zinc-700"></div>
                        </div>

                        <!-- Modal Header -->
                        <div class="relative overflow-hidden px-6 py-4 bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 flex items-center justify-between flex-shrink-0">
                            <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl" />
                            <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 18px 18px;" />
                            <div class="relative">
                                <p class="text-blue-100 text-[10px] font-bold uppercase tracking-[0.2em]">Order Details</p>
                                <h3 class="text-white font-black text-lg leading-tight">{{ selectedOrder.number }}</h3>
                            </div>
                            <div class="relative flex items-center gap-2">
                                <span
                                    class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wide border"
                                    :class="selectedOrder.type === 'PO'
                                        ? 'bg-white/15 text-white border-white/30 backdrop-blur'
                                        : 'bg-amber-300/90 text-amber-950 border-amber-200'"
                                >{{ selectedOrder.type }}</span>
                                <button
                                    @click="selectedOrder = null"
                                    class="w-8 h-8 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-xl transition-colors"
                                >
                                    <X class="w-4 h-4 text-white" />
                                </button>
                            </div>
                        </div>

                        <!-- Modal Body -->
                        <div class="overflow-y-auto flex-1 p-5 space-y-4">
                            <!-- Core Details Grid -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-50 dark:bg-zinc-800/60 rounded-2xl p-3 border border-gray-100 dark:border-zinc-800">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Client</p>
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ selectedOrder.client_name }}</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-zinc-800/60 rounded-2xl p-3 border border-gray-100 dark:border-zinc-800">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total</p>
                                    <p class="font-black text-indigo-600 dark:text-indigo-300 text-base">₱{{ formatCurrency(selectedOrder.total) }}</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-zinc-800/60 rounded-2xl p-3 border border-gray-100 dark:border-zinc-800">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Status</p>
                                    <span :class="statusBadge(selectedOrder.status)" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase border inline-flex items-center gap-1">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />{{ selectedOrder.status }}
                                    </span>
                                </div>
                                <div class="bg-slate-50 dark:bg-zinc-800/60 rounded-2xl p-3 border border-gray-100 dark:border-zinc-800">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Date</p>
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ selectedOrder.date }}</p>
                                </div>
                            </div>

                            <!-- Payment Section -->
                            <div class="bg-slate-50 dark:bg-zinc-800/60 rounded-2xl p-4 border border-gray-200 dark:border-zinc-700">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Payment</p>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase border inline-flex items-center gap-1"
                                        :class="selectedOrder.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30' : 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:border-rose-500/30'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />
                                        {{ selectedOrder.payment_status === 'paid' ? 'Paid' : 'Unpaid' }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <button
                                        @click="openPaymentModal(selectedOrder)"
                                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all hover:shadow-lg hover:shadow-indigo-500/25 active:scale-95"
                                    >
                                        Manage Payment
                                    </button>
                                    <a
                                        v-if="selectedOrder.receipt_file"
                                        :href="route('ord.orders.download-receipt', { type: selectedOrder.type, id: selectedOrder.id })"
                                        target="_blank"
                                        class="px-3 py-1.5 bg-gray-200 dark:bg-zinc-700 hover:bg-gray-300 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition-colors"
                                    >
                                        View Receipt
                                    </a>
                                </div>
                            </div>

                            <!-- Production Details (SO only) -->
                            <div v-if="selectedOrder.type === 'SO'" class="bg-indigo-50/60 dark:bg-indigo-950/30 rounded-2xl p-4 border border-indigo-100 dark:border-indigo-800/50">
                                <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-3">Production Details</p>
                                <div class="grid grid-cols-2 gap-2.5">
                                    <div>
                                        <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-wider">Product</p>
                                        <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-100 mt-0.5">{{ selectedOrder.product_name || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-wider">Yarn Type</p>
                                        <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-100 mt-0.5">{{ selectedOrder.yarn_type || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-wider">Color</p>
                                        <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-100 mt-0.5">{{ selectedOrder.color || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-wider">Quantity</p>
                                        <p class="text-base font-black text-indigo-600 dark:text-indigo-300 mt-0.5">{{ selectedOrder.quantity || 0 }} <span class="text-xs font-bold text-indigo-400">kg</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="p-4 border-t border-gray-100 dark:border-zinc-800 flex-shrink-0">
                            <button
                                @click="selectedOrder = null"
                                class="w-full py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-zinc-900 rounded-2xl text-sm font-bold transition-all active:scale-[0.99]"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Payment Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="paymentOrder"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm"
                    @click.self="paymentOrder = null"
                >
                    <div
                        class="bg-white dark:bg-zinc-900 w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800"
                    >
                        <!-- Handle (mobile) -->
                        <div class="sm:hidden flex justify-center pt-3 pb-1">
                            <div class="w-10 h-1 rounded-full bg-gray-200 dark:bg-zinc-700"></div>
                        </div>

                        <!-- Modal Header -->
                        <div class="relative overflow-hidden px-6 py-4 bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 flex items-center justify-between flex-shrink-0">
                            <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl" />
                            <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 18px 18px;" />
                            <div class="relative">
                                <p class="text-blue-100 text-[10px] font-bold uppercase tracking-[0.2em]">Manage Payment</p>
                                <h3 class="text-white font-black text-lg leading-tight">{{ paymentOrder.number }}</h3>
                            </div>
                            <button
                                @click="paymentOrder = null"
                                class="relative w-8 h-8 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-xl transition-colors"
                            >
                                <X class="w-4 h-4 text-white" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="overflow-y-auto flex-1 p-5">
                            <form @submit.prevent="submitPayment" enctype="multipart/form-data">
                                <div class="space-y-4">
                                    <!-- Payment Status -->
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Payment Status</label>
                                        <div class="flex gap-3">
                                            <label class="inline-flex items-center">
                                                <input type="radio" v-model="form.payment_status" value="unpaid" class="form-radio">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">Unpaid</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input type="radio" v-model="form.payment_status" value="paid" class="form-radio">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">Paid</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Receipt Upload -->
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Receipt (PDF, JPG, PNG)</label>
                                        <input
                                            type="file"
                                            accept=".jpg,.jpeg,.png,.pdf"
                                            @change="handleFileUpload"
                                            class="block w-full text-sm text-gray-500 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-500/15 dark:file:text-indigo-300"
                                        />
                                        <p v-if="form.receipt" class="text-xs text-emerald-600 mt-1">File selected: {{ form.receipt.name }}</p>
                                        <p v-if="paymentOrder.receipt_file" class="text-xs text-gray-500 dark:text-gray-400 mt-1">Current receipt: <a :href="route('ord.orders.download-receipt', { type: paymentOrder.type, id: paymentOrder.id })" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline">Download</a></p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-2 mt-6">
                                    <button
                                        type="submit"
                                        class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-sm font-bold transition-all hover:shadow-lg hover:shadow-indigo-500/25 active:scale-[0.99]"
                                        :disabled="form.processing"
                                    >
                                        {{ form.processing ? 'Saving...' : 'Save Payment' }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="paymentOrder = null"
                                        class="flex-1 py-3 bg-gray-200 dark:bg-zinc-700 hover:bg-gray-300 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 rounded-2xl text-sm font-bold transition-colors"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { X, CalendarDays, Sparkles, ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    orders: Array,
    currentYear: Number,
    currentMonth: Number,
});

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const currentYear = ref(props.currentYear);
const currentMonth = ref(props.currentMonth);
const selectedOrder = ref(null);
const paymentOrder = ref(null);

const form = useForm({
    order_id: null,
    type: null,
    payment_status: 'unpaid',
    receipt: null,
});

const currentMonthName = computed(() => {
    return new Date(currentYear.value, currentMonth.value - 1).toLocaleString('default', { month: 'long' });
});

const calendarDays = computed(() => {
    const firstDayOfMonth = new Date(currentYear.value, currentMonth.value - 1, 1);
    const startDay = firstDayOfMonth.getDay();
    const daysInMonth = new Date(currentYear.value, currentMonth.value, 0).getDate();

    const days = [];
    const ordersByDate = {};
    props.orders.forEach(order => {
        const d = order.date;
        if (d) {
            if (!ordersByDate[d]) ordersByDate[d] = [];
            ordersByDate[d].push(order);
        }
    });

    // Previous month tail
    const prevMonthDays = startDay;
    for (let i = prevMonthDays - 1; i >= 0; i--) {
        const date = new Date(currentYear.value, currentMonth.value - 1, -i);
        days.push({ date: date.getDate(), isCurrentMonth: false, orders: [] });
    }

    // Current month
    for (let i = 1; i <= daysInMonth; i++) {
        const dateStr = `${currentYear.value}-${String(currentMonth.value).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
        days.push({ date: i, isCurrentMonth: true, orders: ordersByDate[dateStr] || [] });
    }

    // Fill remaining cells
    const totalCells = Math.ceil(days.length / 7) * 7;
    while (days.length < totalCells) {
        days.push({ date: '', isCurrentMonth: false, orders: [] });
    }
    return days;
});

const prevMonth = () => {
    if (currentMonth.value === 1) {
        currentMonth.value = 12;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
    refresh();
};

const nextMonth = () => {
    if (currentMonth.value === 12) {
        currentMonth.value = 1;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
    refresh();
};

const refresh = () => {
    window.location.href = route('ord.orders', { year: currentYear.value, month: currentMonth.value });
};

const formatCurrency = (val) => Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });

const statusBadge = (status) => {
    const map = {
        pending: 'bg-yellow-50 text-yellow-700 border-yellow-200',
        approved: 'bg-green-50 text-green-700 border-green-200',
        in_progress: 'bg-blue-50 text-blue-700 border-blue-200',
        completed: 'bg-green-50 text-green-700 border-green-200',
        shipped: 'bg-gray-100 text-gray-700 border-gray-200',
    };
    return map[status] || 'bg-gray-50 text-gray-600 border-gray-200';
};

const openOrderModal = (order) => {
    selectedOrder.value = order;
};

const openPaymentModal = (order) => {
    // Populate form
    form.order_id = order.id;
    form.type = order.type;
    form.payment_status = order.payment_status || 'unpaid';
    form.receipt = null;
    paymentOrder.value = order;
};

const handleFileUpload = (e) => {
    form.receipt = e.target.files[0] || null;
};

const submitPayment = () => {
    // Use FormData to handle file upload
    const data = new FormData();
    data.append('order_id', form.order_id);
    data.append('type', form.type);
    data.append('payment_status', form.payment_status);
    if (form.receipt) {
        data.append('receipt', form.receipt);
    }

    router.post(route('ord.orders.payment'), data, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            paymentOrder.value = null;
            // Refresh the page or update local data
            refresh();
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
};
</script>

<style scoped>
@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
@keyframes pop { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
@keyframes bounceSoft { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
.animate-fade-up { animation: fadeUp 0.6s cubic-bezier(0.22,1,0.36,1) both; }
.animate-float { animation: float 7s ease-in-out infinite; }
.animate-float-delayed { animation: float 8s ease-in-out 1.2s infinite; }
.animate-pop { animation: pop 0.5s cubic-bezier(0.22,1,0.36,1) both; }
.animate-bounce-soft { animation: bounceSoft 2.4s ease-in-out infinite; }
.card-enter-active { transition: opacity 0.45s ease, transform 0.45s cubic-bezier(0.22,1,0.36,1); }
.card-enter-from { opacity: 0; transform: translateY(18px) scale(0.98); }
.card-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; position: absolute; }
.card-leave-to { opacity: 0; transform: scale(0.96); }
.card-move { transition: transform 0.4s ease; }
.modal-enter-active { transition: opacity 0.25s ease, transform 0.32s cubic-bezier(0.22,1,0.36,1); }
.modal-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: translateY(18px) scale(0.97); }
</style>
