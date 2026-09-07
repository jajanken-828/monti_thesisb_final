<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package,
    ShoppingCart,
    AlertTriangle,
    AlertCircle,
    CheckCircle,
    RefreshCw,
    Search,
    X,
    Info,
    Sparkles
} from 'lucide-vue-next';

const props = defineProps({
    materials: {
        type: Array,
        default: () => [],
    },
    pendingOrdersCount: {
        type: Number,
        default: 0,
    },
    auth: Object,
});

// UI state
const searchQuery = ref('');
const processingMaterial = ref(null);
const checkingOrders = ref(false);

// MODAL CONFIRMATION STATE
const showConfirmModal = ref(false);
const confirmConfig = ref({
    title: '',
    message: '',
    confirmText: '',
    type: 'blue', // blue, green, amber
    action: null
});

// Helper to trigger confirmation
const triggerConfirm = (title, message, confirmText, type, action) => {
    confirmConfig.value = { title, message, confirmText, type, action };
    showConfirmModal.value = true;
};

// Filter materials
const filteredMaterials = computed(() => {
    if (!searchQuery.value) return props.materials;
    const q = searchQuery.value.toLowerCase();
    return props.materials.filter(mat =>
        mat.name.toLowerCase().includes(q) ||
        mat.mat_id.toLowerCase().includes(q)
    );
});

/**
 * PROCUREMENT LOGIC
 * Fixed: This now sends the data object instead of an empty {}
 */
const handleProcurementAction = (material) => {
    showConfirmModal.value = false;
    processingMaterial.value = material.id;
    
    // Prepare the payload for the controller
    const payload = {
        required_qty: material.reorder_point > 0 ? material.reorder_point : 100,
        urgency: 'Medium',
        notes: 'Auto-requested via Stock Checker dashboard.'
    };

    router.post(route('inv.checker.procurement', material.id), payload, {
        preserveScroll: true,
        onFinish: () => {
            processingMaterial.value = null;
        },
    });
};

const requestProcurement = (material) => {
    triggerConfirm(
        'Request Procurement',
        `Send a procurement request for "${material.name}" to the SCM department?`,
        'Send Request',
        'amber',
        () => handleProcurementAction(material)
    );
};

/**
 * ORDER CHECK LOGIC
 */
const handleOrderCheckAction = () => {
    showConfirmModal.value = false;
    checkingOrders.value = true;
    router.post(route('inv.checker.orders'), {}, {
        preserveScroll: true,
        onFinish: () => {
            checkingOrders.value = false;
        },
    });
};

const checkOrders = () => {
    triggerConfirm(
        'System Sufficiency Check',
        'Verify material sufficiency for all pending orders? This will update order statuses and auto-generate material requests where needed.',
        'Run Check',
        'blue',
        handleOrderCheckAction
    );
};

// Status badge helper
const statusBadge = (status) => {
    switch (status) {
        case 'ok': return { class: 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30', label: 'In Stock' };
        case 'low': return { class: 'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30', label: 'Low Stock' };
        case 'out': return { class: 'bg-rose-100 text-rose-700 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-500/30', label: 'Out of Stock' };
        default: return { class: 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-zinc-800 dark:text-gray-300 dark:ring-zinc-700', label: status };
    }
};
</script>

<template>
    <Head title="Stock Checker | Inventory" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 pb-16">
                <!-- Hero header -->
                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl animate-float-delayed" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                    <div class="relative flex flex-wrap items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg animate-pop">
                            <ShoppingCart class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <Sparkles class="h-3.5 w-3.5" /> Inventory · Stock Health
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Stock Checker</h1>
                            <p class="text-sm text-blue-100/90">Monitor material stock levels and trigger procurement or order checks.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur">
                                {{ pendingOrdersCount }} pending
                            </span>
                            <button
                                @click="checkOrders"
                                :disabled="checkingOrders"
                                class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2.5 text-xs font-black uppercase tracking-wide text-indigo-700 shadow-lg transition-all duration-200 hover:scale-105 active:scale-95 disabled:opacity-50"
                            >
                                <RefreshCw :class="['w-4 h-4', checkingOrders && 'animate-spin']" />
                                {{ checkingOrders ? 'Checking...' : `Check Orders (${pendingOrdersCount})` }}
                            </button>
                        </div>
                    </div>

                    <div class="relative mt-6 flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <Search class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search materials..."
                                class="w-full rounded-2xl border-0 bg-white/95 py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-lg placeholder:text-gray-400 focus:ring-2 focus:ring-white/70 outline-none transition"
                            />
                        </div>
                    </div>
                </div>

                <div class="animate-fade-up bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden" style="animation-delay: 80ms">
                    <div v-if="filteredMaterials.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
                        <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-4 animate-bounce-soft">
                            <Package class="h-9 w-9 text-indigo-400" />
                        </div>
                        <p class="text-sm font-black text-gray-700 dark:text-gray-200">No materials found</p>
                        <p class="text-xs text-gray-400 mt-1">Try a different search.</p>
                        <button v-if="searchQuery" @click="searchQuery=''" class="mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 transition active:scale-95">Clear filters</button>
                    </div>
                    <TransitionGroup v-else name="card" tag="div" class="overflow-x-auto">
                        <table key="checker-table" class="w-full text-left text-sm">
                            <thead class="bg-slate-50/80 dark:bg-zinc-800/60 border-b border-gray-100 dark:border-zinc-800">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest">Material ID</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest">Name</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest">Category</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest text-right">Total Stock</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest">Unit</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest">Reorder Point</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                <tr v-for="mat in filteredMaterials" :key="mat.id" class="hover:bg-indigo-50/40 dark:hover:bg-indigo-950/10 transition-colors">
                                    <td class="px-6 py-5 font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ mat.mat_id }}</td>
                                    <td class="px-6 py-5 font-bold text-gray-900 dark:text-white">{{ mat.name }}</td>
                                    <td class="px-6 py-5 text-slate-500 dark:text-gray-400 font-medium">{{ mat.category }}</td>
                                    <td class="px-6 py-5 text-right font-black text-gray-900 dark:text-white">{{ mat.total_stock.toLocaleString() }}</td>
                                    <td class="px-6 py-5 font-bold text-slate-500 dark:text-gray-400">{{ mat.unit }}</td>
                                    <td class="px-6 py-5 text-slate-500 dark:text-gray-400 font-bold">{{ mat.reorder_point.toLocaleString() }}</td>
                                    <td class="px-6 py-5">
                                        <span :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase ring-1', statusBadge(mat.status).class]">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />
                                            <AlertTriangle v-if="mat.status === 'low'" class="w-3 h-3" />
                                            <CheckCircle v-else-if="mat.status === 'ok'" class="w-3 h-3" />
                                            <AlertCircle v-else class="w-3 h-3" />
                                            {{ statusBadge(mat.status).label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <button
                                            v-if="mat.status !== 'ok'"
                                            @click="requestProcurement(mat)"
                                            :disabled="processingMaterial === mat.id"
                                            class="inline-flex items-center gap-2 px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 hover:scale-105 active:scale-95 transition-all shadow-lg shadow-indigo-500/20 disabled:opacity-50"
                                        >
                                            <ShoppingCart class="w-3.5 h-3.5" />
                                            {{ processingMaterial === mat.id ? 'Sending...' : 'Procure' }}
                                        </button>
                                        <span v-else class="text-slate-300 dark:text-zinc-600 text-[10px] font-black uppercase tracking-widest">— Adequate —</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TransitionGroup>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xl" @click.self="showConfirmModal = false">
                    <div class="modal-panel bg-white dark:bg-zinc-900 rounded-3xl w-full max-w-md p-8 shadow-2xl border border-gray-100 dark:border-zinc-800">
                        <div class="flex flex-col items-center text-center">
                            <div class="h-16 w-16 rounded-2xl flex items-center justify-center mb-5 shadow-lg animate-pop text-white" :class="{
                                'bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700': confirmConfig.type === 'blue',
                                'bg-gradient-to-br from-amber-500 via-orange-600 to-rose-600': confirmConfig.type === 'amber',
                                'bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700': confirmConfig.type === 'green'
                            }">
                                <Info v-if="confirmConfig.type === 'blue'" class="w-8 h-8" />
                                <AlertTriangle v-if="confirmConfig.type === 'amber'" class="w-8 h-8" />
                                <CheckCircle v-if="confirmConfig.type === 'green'" class="w-8 h-8" />
                            </div>

                            <h3 class="text-lg font-black uppercase tracking-tight mb-2 text-gray-900 dark:text-white">
                                {{ confirmConfig.title }}
                            </h3>
                            <p class="text-sm font-medium text-slate-500 dark:text-gray-400 leading-relaxed px-4">
                                {{ confirmConfig.message }}
                            </p>

                            <div class="mt-8 flex w-full gap-3">
                                <button
                                    @click="showConfirmModal = false"
                                    class="flex-1 px-6 py-3.5 bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-gray-300 rounded-2xl text-[11px] font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-zinc-700 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="confirmConfig.action"
                                    :class="{
                                        'bg-indigo-600 shadow-indigo-500/25': confirmConfig.type === 'blue',
                                        'bg-amber-600 shadow-amber-500/25': confirmConfig.type === 'amber',
                                        'bg-emerald-600 shadow-emerald-500/25': confirmConfig.type === 'green'
                                    }"
                                    class="flex-[1.5] px-6 py-3.5 text-white rounded-2xl text-[11px] font-black uppercase tracking-widest shadow-lg hover:opacity-90 hover:scale-[1.02] active:scale-95 transition-all"
                                >
                                    {{ confirmConfig.confirmText }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

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
.modal-enter-active, .modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .modal-panel, .modal-leave-active .modal-panel { transition: transform 0.3s cubic-bezier(0.22,1,0.36,1), opacity 0.3s ease; }
.modal-enter-from .modal-panel { opacity: 0; transform: translateY(20px) scale(0.97); }
.modal-leave-to .modal-panel { opacity: 0; transform: translateY(12px) scale(0.98); }
</style>
