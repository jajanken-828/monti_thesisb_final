<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Warehouse, Plus, Trash2, X, Edit2, Save, Grid, Layers, Package, Box,
    ArrowRight, Send, MapPin, LayoutList, AlertCircle, ChevronUp, ChevronDown,
    Eye, ClipboardList, Calendar, Hash, Info, CheckCircle, ArrowUpRight
} from 'lucide-vue-next';
import { usePageAccess } from '@/composables/usePageAccess';

const { canEdit } = usePageAccess();
const canEditMonitor = computed(() => canEdit('WAR', 'monitor'));

const props = defineProps({
    warehouse: Object,
    sections: Array,
    unassignedStock: Array,
    auth: Object,
});

// ─── UI & Layout State ──────────────────────────────────────────────
const editMode = ref(false);
const rows = ref(props.warehouse?.grid_rows || 3);
const cols = ref(props.warehouse?.grid_cols || 3);
const sectionsList = ref([]);
const shelvesList = ref({});

// Modal States
const showSectionModal = ref(false);
const showShelfModal = ref(false);
const showDetailsModal = ref(false);
const showUseModal = ref(false);
const showConfirmModal = ref(false);

// Data States
const editingSection = ref(null);
const editingShelfSection = ref(null);
const newSectionName = ref('');
const newShelfNumber = ref('');
const selectedCell = ref(null);
const detailItem = ref(null);
const selectedStockItem = ref(null);
const useFormErrors = ref({});      // FIX: track backend validation errors for the use modal

const confirmConfig = ref({ title: '', message: '', confirmText: '', type: 'blue', action: null });
const materialForm = useForm({ quantity: 0, manufacturing_department: '' });

// ─── Initialization ──────────────────────────────────────────────────
const initData = () => {
    if (props.sections) {
        sectionsList.value = JSON.parse(JSON.stringify(props.sections));
        const shelves = {};
        sectionsList.value.forEach(section => {
            shelves[section.id] = section.shelves || [];
        });
        shelvesList.value = shelves;

        const maxR = sectionsList.value.length > 0 ? Math.max(...sectionsList.value.map(s => s.grid_row)) : 2;
        const maxC = sectionsList.value.length > 0 ? Math.max(...sectionsList.value.map(s => s.grid_col)) : 2;
        rows.value = Math.max(props.warehouse?.grid_rows || 3, maxR + 1);
        cols.value = Math.max(props.warehouse?.grid_cols || 3, maxC + 1);
    }
};

onMounted(() => initData());
watch(() => props.sections, () => initData(), { deep: true });

// ─── Grid Display Logic ──────────────────────────────────────────────
const gridTemplate = computed(() => ({
    gridTemplateRows: `repeat(${rows.value}, minmax(160px, auto))`,
    gridTemplateColumns: `repeat(${cols.value}, 1fr)`,
}));

const gridCells = computed(() => {
    const cells = [];
    for (let r = 0; r < rows.value; r++) {
        for (let c = 0; c < cols.value; c++) {
            const section = sectionsList.value.find(s => s.grid_row === r && s.grid_col === c);
            cells.push({ row: r, col: c, section: section || null });
        }
    }
    return cells;
});

// ─── DELETE LOGIC ────────────────────────────────────────────────────
const deleteSection = (sectionId) => {
    const section = sectionsList.value.find(s => s.id === sectionId);
    if (!section) return;

    const hasItems = (section.stock_items_no_shelf?.length > 0) ||
                     (section.shelves?.some(sh => sh.stock_items?.length > 0));

    if (hasItems) {
        triggerConfirm('Action Denied', `Sector contains items. Move them to unassigned first.`, 'Understood', 'amber', () => showConfirmModal.value = false);
        return;
    }

    triggerConfirm('Delete Box', 'Are you sure you want to remove this specific box?', 'Delete', 'red', () => {
        sectionsList.value = sectionsList.value.filter(s => s.id !== sectionId);
        showConfirmModal.value = false;
    });
};

// ─── Actions ──────────────────────────────────────────────────────────
const saveSection = () => {
    if (!newSectionName.value.trim()) return;
    if (editingSection.value) {
        const idx = sectionsList.value.findIndex(s => s.id === editingSection.value.id);
        if (idx !== -1) sectionsList.value[idx].name = newSectionName.value;
    } else {
        const newId = 'temp-' + Date.now();
        const newSec = {
            id: newId,
            name: newSectionName.value,
            grid_row: selectedCell.value.row,
            grid_col: selectedCell.value.col,
            shelves: [],
            stock_items_no_shelf: []
        };
        sectionsList.value.push(newSec);
        shelvesList.value[newId] = [];
    }
    showSectionModal.value = false;
};

const addShelf = () => {
    if (!newShelfNumber.value.trim()) return;
    const sid = editingShelfSection.value.id;
    if (!shelvesList.value[sid]) shelvesList.value[sid] = [];
    const newShelf = { id: 'ts-' + Date.now(), shelf_number: newShelfNumber.value.toUpperCase(), stock_items: [] };
    shelvesList.value[sid].push(newShelf);
    const sec = sectionsList.value.find(s => s.id === sid);
    if (sec) {
        sec.shelves = JSON.parse(JSON.stringify(shelvesList.value[sid]));
    }
    newShelfNumber.value = '';
};

const removeShelf = (sid, shid) => {
    const shelf = (shelvesList.value[sid] || []).find(s => s.id === shid);
    if (shelf?.stock_items?.length > 0) {
        triggerConfirm('Denied', 'Shelf contains items.', 'OK', 'amber', () => showConfirmModal.value = false);
        return;
    }
    shelvesList.value[sid] = shelvesList.value[sid].filter(s => s.id !== shid);
    const sec = sectionsList.value.find(s => s.id === sid);
    if (sec) {
        sec.shelves = JSON.parse(JSON.stringify(shelvesList.value[sid]));
    }
};

const saveLayout = () => {
    showConfirmModal.value = false;
    const finalSections = sectionsList.value.map(s => ({
        id: String(s.id).startsWith('temp') ? null : s.id,
        name: s.name,
        row: s.grid_row,
        col: s.grid_col,
        shelves: (shelvesList.value[s.id] || []).map(sh => ({
            id: String(sh.id).startsWith('ts') ? null : sh.id,
            shelf_number: sh.shelf_number
        }))
    }));

    router.post(route('warehouse.monitor.layout', props.warehouse.id), {
        grid_rows: rows.value,
        grid_cols: cols.value,
        sections: finalSections
    }, {
        onSuccess: () => { editMode.value = false; router.reload(); },
        preserveScroll: true
    });
};

const onDrop = (event, shelfId, section = null) => {
    event.preventDefault();
    const item = JSON.parse(event.dataTransfer.getData('text/plain'));
    router.post(route('warehouse.monitor.assign'), { stock_item_id: item.id, shelf_id: shelfId, section_id: section?.id }, { preserveScroll: true });
};

const triggerConfirm = (title, message, confirmText, type, action) => {
    confirmConfig.value = { title, message, confirmText, type, action };
    showConfirmModal.value = true;
};

const openDetailsModal = (item) => { detailItem.value = item; showDetailsModal.value = true; };

// ─── FIX: Reset form fields each time the Use modal opens ────────────
const openUseModal = (item) => {
    selectedStockItem.value = item;
    useFormErrors.value = {};
    // Always set quantity from the item and RESET department to force user selection
    materialForm.quantity = item.quantity;
    materialForm.manufacturing_department = '';   // ← was missing: caused silently re-using stale department
    showUseModal.value = true;
};

const submitUse = () => {
    // Client-side guard: department is mandatory
    if (!materialForm.manufacturing_department) {
        useFormErrors.value = { manufacturing_department: 'Please select a destination department.' };
        return;
    }
    useFormErrors.value = {};

    triggerConfirm(
        'Release Material',
        `Send ${materialForm.quantity} ${selectedStockItem.value?.unit} of "${selectedStockItem.value?.material?.name}" to ${materialForm.manufacturing_department}?`,
        'Confirm',
        'green',
        () => {
            showConfirmModal.value = false;
            materialForm.post(route('warehouse.monitor.use', selectedStockItem.value.id), {
                onSuccess: () => {
                    showUseModal.value = false;
                    router.reload();
                },
                onError: (errors) => {
                    // Surface backend validation errors back in the modal
                    useFormErrors.value = errors;
                    showUseModal.value = true; // keep modal open so user can fix
                },
            });
        }
    );
};

const tryReduceRows = () => {
    const hasItems = sectionsList.value.some(s => s.grid_row === rows.value - 1);
    if (hasItems) triggerConfirm('Restriction', `Row ${rows.value} is not empty.`, 'OK', 'amber', () => showConfirmModal.value = false);
    else if (rows.value > 1) rows.value--;
};

const tryReduceCols = () => {
    const hasItems = sectionsList.value.some(s => s.grid_col === cols.value - 1);
    if (hasItems) triggerConfirm('Restriction', `Column ${cols.value} is not empty.`, 'OK', 'amber', () => showConfirmModal.value = false);
    else if (cols.value > 1) cols.value--;
};

const dragStart = (e, item) => { e.dataTransfer.setData('text/plain', JSON.stringify(item)); e.dataTransfer.effectAllowed = 'move'; };
const allowDrop = (e) => e.preventDefault();
</script>

<template>
    <Head :title="`Monitor: ${warehouse.name}`" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="p-4 sm:p-6 max-w-[1600px] mx-auto space-y-6 pb-16">

                <!-- Hero header -->
                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl animate-float-delayed" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                    <div class="relative flex flex-wrap items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg animate-pop">
                            <Warehouse class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <LayoutList class="h-3.5 w-3.5" /> Warehouse · Layout
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Warehouse Monitor <span v-if="!canEditMonitor" class="ml-2 inline-block rounded-full bg-amber-400/90 px-3 py-1 align-middle text-xs font-black uppercase tracking-widest text-amber-950">View only</span></h1>
                            <p class="text-sm text-blue-100/90">{{ warehouse.name }} · {{ rows }} × {{ cols }} grid · drag stock onto shelves</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur">
                                {{ unassignedStock.length }} unassigned
                            </span>
                            <span v-if="editMode" class="rounded-full bg-amber-300/90 px-3 py-1.5 text-xs font-black text-amber-950 flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" /> Editing</span>
                            <span v-else class="rounded-full bg-emerald-400/90 px-3 py-1.5 text-xs font-black text-emerald-950">Live view</span>
                        </div>
                    </div>
                    <div class="relative mt-6 flex flex-wrap items-center gap-2">
                        <button v-if="!editMode && canEditMonitor" @click="editMode = true" class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-2.5 text-xs font-black uppercase tracking-wide text-indigo-700 shadow-lg transition-all duration-200 hover:scale-105 active:scale-95"><Edit2 class="w-3.5 h-3.5" /> Edit Layout</button>
                        <button v-if="editMode && canEditMonitor" @click="triggerConfirm('Apply Layout', 'Save grid changes?', 'Save', 'blue', saveLayout)" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-400 px-5 py-2.5 text-xs font-black uppercase tracking-wide text-emerald-950 shadow-lg transition-all duration-200 hover:scale-105 active:scale-95"><Save class="w-3.5 h-3.5" /> Save Layout</button>
                        <button v-if="editMode" @click="editMode = false" class="inline-flex items-center gap-2 rounded-2xl bg-white/15 px-5 py-2.5 text-xs font-black uppercase tracking-wide text-white ring-1 ring-white/25 backdrop-blur hover:bg-white/25 transition-all active:scale-95">Cancel</button>
                    </div>
                </div>

                <!-- Edit-mode grid sizing -->
                <div v-if="editMode && canEditMonitor" class="animate-fade-up bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-indigo-200 dark:border-indigo-800 border-dashed p-6 sm:p-8 flex flex-wrap gap-8 shadow-sm">
                    <div class="flex flex-col gap-2">
                        <span class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em]">Rows · {{ rows }}</span>
                        <div class="flex items-center gap-3 bg-slate-50 dark:bg-zinc-800 p-2 rounded-2xl border border-gray-100 dark:border-zinc-700 shadow-sm">
                            <button @click="tryReduceRows" class="p-2 rounded-xl hover:bg-white dark:hover:bg-zinc-700 text-slate-500 transition"><ChevronDown class="w-5 h-5"/></button>
                            <span class="text-xl font-black w-8 text-center text-slate-900 dark:text-white">{{ rows }}</span>
                            <button @click="rows++" class="p-2 rounded-xl hover:bg-white dark:hover:bg-zinc-700 text-slate-500 transition"><ChevronUp class="w-5 h-5"/></button>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <span class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em]">Columns · {{ cols }}</span>
                        <div class="flex items-center gap-3 bg-slate-50 dark:bg-zinc-800 p-2 rounded-2xl border border-gray-100 dark:border-zinc-700 shadow-sm">
                            <button @click="tryReduceCols" class="p-2 rounded-xl hover:bg-white dark:hover:bg-zinc-700 text-slate-500 transition"><ChevronDown class="w-5 h-5"/></button>
                            <span class="text-xl font-black w-8 text-center text-slate-900 dark:text-white">{{ cols }}</span>
                            <button @click="cols++" class="p-2 rounded-xl hover:bg-white dark:hover:bg-zinc-700 text-slate-500 transition"><ChevronUp class="w-5 h-5"/></button>
                        </div>
                    </div>
                    <p class="w-full text-[11px] font-bold text-slate-400 uppercase tracking-widest">Click an empty zone to add a sector · drag stock cards onto shelves</p>
                </div>

                <!-- Incoming queue (drag sources — ids/handlers preserved) -->
                <div v-if="unassignedStock.length > 0" class="animate-fade-up bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 p-6 sm:p-8 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300">
                    <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-5 flex items-center gap-2"><Package class="w-4 h-4 text-indigo-500" /> Incoming Queue · drag onto a shelf</h3>
                    <TransitionGroup name="card" tag="div" class="flex flex-wrap gap-3">
                        <div v-for="(item, i) in unassignedStock" :key="item.id" :style="{ transitionDelay: `${Math.min(i * 40, 400)}ms` }" :draggable="canEditMonitor" @dragstart="dragStart($event, item)" class="group bg-slate-50 dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 rounded-2xl px-4 py-3 flex items-center gap-3 cursor-move hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-lg hover:shadow-indigo-500/15 hover:-translate-y-0.5 transition-all shadow-sm">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 text-white shadow"><Box class="w-4 h-4" /></span>
                            <span class="text-sm tracking-tight text-slate-700 dark:text-slate-200 font-bold">{{ item.material.name }}</span>
                            <span class="bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/30 px-3 py-1 rounded-full text-[10px] font-black flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />{{ item.quantity }}{{ item.unit }}</span>
                            <button @click="openDetailsModal(item)" class="text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition"><Eye class="w-4 h-4" /></button>
                        </div>
                    </TransitionGroup>
                </div>

                <!-- Grid (drop targets — ids/handlers preserved) -->
                <div class="animate-fade-up bg-white/60 dark:bg-zinc-900/60 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 p-4 sm:p-8 shadow-inner">
                    <div class="grid gap-4 sm:gap-6" :style="gridTemplate">
                        <div v-for="cell in gridCells" :key="`${cell.row}-${cell.col}`"
                             @click="!cell.section && editMode ? (selectedCell=cell, showSectionModal=true) : null"
                             @drop="cell.section ? onDrop($event, null, cell.section) : null" @dragover="allowDrop"
                             class="group/cell relative border rounded-3xl transition-all min-h-[200px] overflow-hidden"
                             :class="[
                                 cell.section ? 'bg-white/80 dark:bg-zinc-900/80 shadow-lg border-gray-100 dark:border-zinc-800 hover:shadow-2xl hover:shadow-indigo-500/15 hover:border-indigo-200 dark:hover:border-indigo-800' : 'bg-slate-50/50 dark:bg-zinc-900/40 border-dashed border-slate-200 dark:border-zinc-700',
                                 !cell.section && editMode ? 'hover:border-indigo-400 hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 cursor-pointer' : ''
                             ]">

                            <div v-if="cell.section" class="p-5 h-full flex flex-col">
                                <div class="flex justify-between items-center mb-5">
                                    <span class="text-[10px] font-black bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-4 py-1.5 rounded-xl shadow-lg shadow-indigo-500/25 uppercase tracking-widest">{{ cell.section.name }}</span>
                                    <div v-if="editMode" class="flex gap-1">
                                        <button @click.stop="editingSection=cell.section; newSectionName=cell.section.name; showSectionModal=true" class="flex h-8 w-8 items-center justify-center rounded-xl bg-gray-100 dark:bg-zinc-800 text-slate-400 hover:bg-indigo-600 hover:text-white transition-all" title="Rename"><Edit2 class="w-3.5 h-3.5" /></button>
                                        <button @click.stop="deleteSection(cell.section.id)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-gray-100 dark:bg-zinc-800 text-slate-400 hover:bg-rose-600 hover:text-white transition-all" title="Delete"><Trash2 class="w-3.5 h-3.5" /></button>
                                        <button @click.stop="editingShelfSection=cell.section; showShelfModal=true" class="flex h-8 w-8 items-center justify-center rounded-xl bg-gray-100 dark:bg-zinc-800 text-slate-400 hover:bg-emerald-600 hover:text-white transition-all" title="Shelves"><Plus class="w-3.5 h-3.5" /></button>
                                    </div>
                                </div>

                                <div class="flex-1 overflow-y-auto space-y-3 custom-scroll pr-1">
                                    <div v-if="cell.section.stock_items_no_shelf?.length" class="mb-3 space-y-2">
                                        <p class="text-[9px] font-black text-amber-500 tracking-[0.2em] uppercase px-2 mb-1">Common Area</p>
                                        <div v-for="s in cell.section.stock_items_no_shelf" :key="s.id" :draggable="canEditMonitor" @dragstart="dragStart($event, s)"
                                             class="flex justify-between items-center bg-amber-50/60 dark:bg-amber-900/10 p-3 rounded-2xl border border-amber-100 dark:border-amber-900/30 cursor-move hover:border-amber-400 transition-all">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <button @click="openDetailsModal(s)" class="text-amber-400 hover:text-amber-600"><Eye class="w-3.5 h-3.5"/></button>
                                                <span class="text-[11px] font-bold truncate text-slate-700 dark:text-slate-200">{{ s.material.name }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <span class="text-[10px] font-black text-amber-600 dark:text-amber-400">{{ s.quantity }}{{ s.unit }}</span>
                                                <button v-if="canEditMonitor" @click.stop="openUseModal(s)" class="text-[9px] font-black uppercase text-amber-700 dark:text-amber-300 bg-white dark:bg-zinc-800 border border-amber-200 dark:border-amber-800 px-3 py-1 rounded-lg hover:bg-amber-500 hover:text-white transition-all">Use</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-for="shelf in (shelvesList[cell.section.id] || [])" :key="shelf.id" @drop.stop="onDrop($event, shelf.id)" @dragover="allowDrop" class="bg-slate-50/70 dark:bg-zinc-800/60 p-4 rounded-2xl border border-slate-100 dark:border-zinc-700 hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors">
                                        <div class="flex justify-between items-center mb-3">
                                            <span class="text-[10px] font-black text-slate-400 tracking-widest uppercase">Shelf {{ shelf.shelf_number }}</span>
                                        </div>
                                        <div v-if="shelf.stock_items?.length" class="space-y-2">
                                            <div v-for="st in shelf.stock_items" :key="st.id" :draggable="canEditMonitor" @dragstart="dragStart($event, st)"
                                                 class="flex justify-between items-center bg-white dark:bg-zinc-900 p-3 rounded-xl border border-slate-100 dark:border-zinc-700 shadow-sm cursor-move hover:border-indigo-400 hover:shadow-md transition-all">
                                                <div class="flex items-center gap-2 overflow-hidden">
                                                    <button @click="openDetailsModal(st)" class="text-slate-300 hover:text-indigo-500"><Eye class="w-3.5 h-3.5"/></button>
                                                    <span class="text-[11px] font-bold truncate text-slate-700 dark:text-slate-200">{{ st.material.name }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 flex-shrink-0">
                                                    <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-black">{{ st.quantity }}{{ st.unit }}</span>
                                                    <button v-if="canEditMonitor" @click.stop="openUseModal(st)" class="text-[9px] font-black uppercase text-emerald-600 dark:text-emerald-400 hover:underline">Use</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-[9px] font-bold text-slate-300 dark:text-slate-600 text-center py-4 tracking-widest uppercase border border-dashed border-slate-200 dark:border-zinc-700 rounded-xl">Drop stock here</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="h-full flex flex-col items-center justify-center gap-3 opacity-30 group-hover/cell:opacity-70 transition-opacity py-10">
                                <Box class="w-10 h-10 text-indigo-300" />
                                <span class="text-[10px] font-black tracking-[0.3em] text-slate-400 uppercase">{{ editMode ? 'Add sector' : 'Available Zone' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Teleport to="body">

                <!-- Confirm Modal -->
                <Transition name="modal">
                    <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                        <div class="bg-white dark:bg-zinc-900 rounded-3xl w-full max-w-[340px] overflow-hidden shadow-2xl border border-gray-100 dark:border-zinc-800 text-center">
                            <div class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 px-6 pt-6 pb-5 text-white">
                                <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl" />
                                <div :class="{'bg-white/15 ring-white/30': true}" class="relative h-14 w-14 rounded-2xl flex items-center justify-center mx-auto ring-1 animate-pop">
                                    <AlertCircle v-if="confirmConfig.type !== 'green' && confirmConfig.type !== 'blue'" class="w-7 h-7" />
                                    <Info v-if="confirmConfig.type === 'blue'" class="w-7 h-7" />
                                    <CheckCircle v-if="confirmConfig.type === 'green'" class="w-7 h-7" />
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight uppercase">{{ confirmConfig.title }}</h3>
                                <p class="text-xs text-slate-400 leading-relaxed mt-2 mb-6">{{ confirmConfig.message }}</p>
                                <div class="flex gap-2">
                                    <button @click="showConfirmModal = false" class="flex-1 py-3 bg-slate-50 dark:bg-zinc-800 text-slate-400 rounded-2xl text-[10px] font-black uppercase hover:bg-slate-100 transition active:scale-95">Cancel</button>
                                    <button @click="confirmConfig.action" :class="{'bg-blue-600 shadow-blue-500/25': confirmConfig.type === 'blue', 'bg-amber-600 shadow-amber-500/25': confirmConfig.type === 'amber', 'bg-red-600 shadow-red-500/25': confirmConfig.type === 'red', 'bg-emerald-600 shadow-emerald-500/25': confirmConfig.type === 'green'}"
                                            class="flex-1 py-3 text-white rounded-2xl shadow-lg text-[10px] font-black uppercase hover:opacity-90 transition active:scale-95">{{ confirmConfig.confirmText }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Details Modal -->
                <Transition name="modal">
                    <div v-if="showDetailsModal && detailItem" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                        <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-[360px] overflow-hidden border border-gray-100 dark:border-zinc-800">
                            <div class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 px-6 py-5 text-white">
                                <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl" />
                                <div class="relative flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/30 animate-pop"><ClipboardList class="w-5 h-5" /></span>
                                    <h3 class="text-base font-black tracking-tight">Stock Details</h3>
                                </div>
                            </div>
                            <div class="p-6 space-y-3">
                                <div class="bg-slate-50 dark:bg-zinc-800 p-5 rounded-2xl text-center border border-gray-100 dark:border-zinc-700">
                                    <p class="text-[8px] font-black text-indigo-500 tracking-[0.4em] mb-2 uppercase">Description</p>
                                    <p class="text-sm font-black text-slate-900 dark:text-white tracking-tight uppercase">{{ detailItem.material.name }}</p>
                                </div>
                                <div class="flex items-center justify-between p-4 bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-2xl shadow-sm text-xs">
                                    <span class="text-slate-400 font-bold uppercase text-[10px]">Control ID</span>
                                    <span class="text-indigo-600 dark:text-indigo-400 tracking-widest font-black">{{ detailItem.control_number }}</span>
                                </div>
                                <div class="flex items-center justify-between p-4 bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-2xl shadow-sm text-xs">
                                    <span class="text-slate-400 font-bold uppercase text-[10px]">Stock In</span>
                                    <span class="text-slate-900 dark:text-white font-black">{{ new Date(detailItem.received_at).toLocaleDateString() }}</span>
                                </div>
                            </div>
                            <div class="px-6 pb-6">
                                <button @click="showDetailsModal = false" class="w-full py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-500/25 hover:opacity-90 transition active:scale-95">Dismiss</button>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Use / Floor Release Modal (FIXED) -->
                <Transition name="modal">
                    <div v-if="showUseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                        <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-[380px] overflow-hidden border border-gray-100 dark:border-zinc-800">
                            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-700 px-6 py-5 text-white">
                                <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl" />
                                <div class="relative">
                                    <h3 class="text-lg font-black tracking-tight">Floor Release</h3>
                                    <p class="text-[11px] text-emerald-50 mt-1">Select the department that will receive this material.</p>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="bg-emerald-50 dark:bg-emerald-900/20 p-5 rounded-2xl text-center mb-5 border border-emerald-100 dark:border-emerald-800">
                                    <p class="text-sm font-black text-emerald-900 dark:text-emerald-200 uppercase">{{ selectedStockItem?.material?.name }}</p>
                                    <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ selectedStockItem?.control_number }}</p>
                                </div>

                                <div class="space-y-4">
                                    <!-- Quantity -->
                                    <div>
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Quantity to Transfer</label>
                                        <input
                                            v-model="materialForm.quantity"
                                            type="number" step="0.01"
                                            class="w-full p-4 bg-slate-50 dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-2xl font-black text-lg text-center outline-none focus:ring-2 focus:ring-indigo-500/30 text-slate-800 dark:text-slate-100"
                                        />
                                        <p v-if="useFormErrors.quantity" class="text-red-500 text-[10px] font-bold mt-1">
                                            {{ useFormErrors.quantity }}
                                        </p>
                                    </div>

                                    <!-- Destination department — REQUIRED, always reset to empty on open -->
                                    <div>
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">
                                            Destination Dept <span class="text-red-400">*</span>
                                        </label>
                                        <select
                                            v-model="materialForm.manufacturing_department"
                                            class="w-full appearance-none p-4 bg-slate-50 dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-2xl text-xs text-center font-black uppercase outline-none focus:ring-2 focus:ring-indigo-500/30 text-slate-700 dark:text-slate-200"
                                            :class="useFormErrors.manufacturing_department ? 'ring-2 ring-red-400' : ''">
                                            <option value="">— Select Department —</option>
                                            <option value="knitting">Knitting</option>
                                            <option value="dyeing">Dyeing</option>
                                            <option value="maintenance">Maintenance</option>
                                            <option value="packaging">Packaging</option>
                                        </select>
                                        <p v-if="useFormErrors.manufacturing_department" class="text-red-500 text-[10px] font-bold mt-1">
                                            {{ useFormErrors.manufacturing_department }}
                                        </p>
                                    </div>

                                    <!-- General backend error (e.g. material not found) -->
                                    <p v-if="useFormErrors.error" class="text-red-500 text-[10px] font-bold bg-red-50 dark:bg-red-900/20 rounded-2xl p-3">
                                        {{ useFormErrors.error }}
                                    </p>
                                </div>

                                <div class="flex gap-2 mt-6">
                                    <button @click="showUseModal = false" class="flex-1 py-3.5 bg-slate-50 dark:bg-zinc-800 text-slate-500 rounded-2xl text-[10px] font-black uppercase hover:bg-slate-100 transition active:scale-95">Cancel</button>
                                    <button
                                        v-if="canEditMonitor"
                                        @click="submitUse"
                                        :disabled="materialForm.processing"
                                        class="flex-[2] py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl text-[10px] font-black uppercase shadow-lg shadow-emerald-500/25 disabled:opacity-50 hover:opacity-90 transition active:scale-95">
                                        {{ materialForm.processing ? 'Releasing...' : 'Release to Production' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Shelf Config Modal -->
                <Transition name="modal">
                    <div v-if="showShelfModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                        <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-zinc-800 w-full max-w-[380px] overflow-hidden">
                            <div class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 px-6 py-5 text-white">
                                <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl" />
                                <div class="relative flex items-center justify-between">
                                    <h3 class="text-base font-black tracking-tight">Storage Config</h3>
                                    <button @click="showShelfModal = false" class="p-1.5 rounded-xl bg-white/15 ring-1 ring-white/25 hover:bg-white/25 transition"><X class="w-4 h-4" /></button>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="space-y-2 max-h-[220px] overflow-y-auto mb-5 pr-1 custom-scroll">
                                    <div v-for="shelf in (shelvesList[editingShelfSection?.id] || [])" :key="shelf.id"
                                         class="flex items-center justify-between p-4 bg-slate-50 dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-2xl">
                                        <span class="text-[10px] font-black tracking-widest text-slate-900 dark:text-white uppercase">SHELF {{ shelf.shelf_number }}</span>
                                        <button @click="removeShelf(editingShelfSection.id, shelf.id)" class="p-1.5 text-slate-300 hover:text-rose-500 transition-all"><Trash2 class="w-4 h-4" /></button>
                                    </div>
                                </div>
                                <div class="flex gap-2 mb-5 p-2 bg-slate-100 dark:bg-zinc-800 rounded-2xl">
                                    <input v-model="newShelfNumber" @keyup.enter="addShelf" type="text" placeholder="Shelf ID" class="flex-1 px-4 py-3 bg-white dark:bg-zinc-900 border-0 rounded-xl text-xs font-black outline-none focus:ring-2 focus:ring-indigo-500/30 text-slate-700 dark:text-slate-200 uppercase" />
                                    <button @click="addShelf" class="px-5 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl text-[10px] font-black uppercase shadow-lg shadow-indigo-500/25 hover:opacity-90 transition active:scale-95">Add</button>
                                </div>
                                <button @click="showShelfModal = false" class="w-full py-3.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl text-[10px] font-black uppercase hover:opacity-90 transition active:scale-95">Done</button>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Section Name Modal -->
                <Transition name="modal">
                    <div v-if="showSectionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                        <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-[340px] overflow-hidden border border-gray-100 dark:border-zinc-800 text-center">
                            <div class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 px-6 pt-6 pb-5 text-white">
                                <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl" />
                                <div class="relative h-14 w-14 bg-white/15 ring-1 ring-white/30 rounded-2xl flex items-center justify-center mx-auto animate-pop"><MapPin class="w-6 h-6" /></div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight uppercase">{{ editingSection ? 'Edit' : 'New' }} Sector</h3>
                                <input v-model="newSectionName" placeholder="Sector label" class="mt-4 w-full p-4 bg-slate-50 dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-2xl focus:ring-2 focus:ring-indigo-500/30 transition font-black text-center text-sm uppercase outline-none text-slate-800 dark:text-slate-100" />
                                <div class="flex gap-2 mt-5">
                                    <button @click="showSectionModal = false" class="flex-1 py-3.5 bg-slate-100 dark:bg-zinc-800 text-slate-400 rounded-2xl text-[10px] font-black uppercase hover:bg-slate-200 transition active:scale-95">Cancel</button>
                                    <button @click="saveSection" class="flex-[2] py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl text-[10px] font-black uppercase shadow-lg shadow-indigo-500/25 hover:opacity-90 transition active:scale-95">Confirm</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>

            </Teleport>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.grid { display: grid; user-select: none; }
.custom-scroll::-webkit-scrollbar { width: 5px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 20px; }

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
.modal-enter-active { transition: opacity 0.3s ease; }
.modal-enter-active > div { transition: transform 0.35s cubic-bezier(0.22,1,0.36,1), opacity 0.3s ease; }
.modal-enter-from { opacity: 0; }
.modal-enter-from > div { opacity: 0; transform: translateY(20px) scale(0.96); }
.modal-leave-active { transition: opacity 0.2s ease; }
.modal-leave-to { opacity: 0; }
</style>
