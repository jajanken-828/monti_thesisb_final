<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Shield, Users, Plus, X, Save, Trash2, Edit, Search, UserCog
} from 'lucide-vue-next';

const props = defineProps({
    candidates: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] }
});

const page = usePage();
const userRole = computed(() => page.props.auth.user.role);

// Search filter
const searchQuery = ref('');
const filteredCandidates = computed(() => {
    if (!searchQuery.value) return props.candidates;
    const q = searchQuery.value.toLowerCase();
    return props.candidates.filter(c =>
        c.name.toLowerCase().includes(q) ||
        c.role.toLowerCase().includes(q) ||
        c.position.toLowerCase().includes(q)
    );
});

// Modal state
const isPermissionModalOpen = ref(false);
const selectedUser = ref(null);
const permissionsList = ref([]);

const openPermissionModal = (user) => {
    selectedUser.value = user;
    permissionsList.value = (user.permissions || []).map(p => ({
        module: p.module || '',
        department: p.department || '',
        access_level: p.access_level || 'view'
    }));
    if (permissionsList.value.length === 0) addPermissionRow();
    isPermissionModalOpen.value = true;
};

const closePermissionModal = () => {
    isPermissionModalOpen.value = false;
    selectedUser.value = null;
};

const addPermissionRow = () => {
    permissionsList.value.push({ module: '', department: '', access_level: 'view' });
};

const removePermissionRow = (index) => {
    permissionsList.value.splice(index, 1);
};

const removeAllPermissions = (userId) => {
    if (confirm('Are you sure you want to remove all workforce permissions for this user?')) {
        router.post(route('workforce.access.update'), {
            user_id: userId,
            permissions: [] // Sending empty array to clear
        }, { preserveScroll: true });
    }
};

const savePermissions = () => {
    const validPermissions = permissionsList.value.filter(p => p.module && p.access_level);
    router.post(route('workforce.access.update'), {
        user_id: selectedUser.value.id,
        permissions: validPermissions
    }, {
        preserveScroll: true,
        onSuccess: () => closePermissionModal(),
    });
};

const getAccessLevelClass = (level) => {
    switch (level) {
        case 'manage': return 'bg-purple-100 text-purple-700 ring-purple-200 dark:bg-purple-500/15 dark:text-purple-300 dark:ring-purple-500/30';
        // case 'schedule': return 'bg-blue-100 text-blue-700';
        // case 'view': return 'bg-green-100 text-green-700';
        default: return 'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-zinc-800 dark:text-gray-300 dark:ring-zinc-700';
    }
};

const getInitials = (name) => name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
</script>

<template>
    <Head title="Workforce Access Control" />
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
                            <Shield class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <UserCog class="h-3.5 w-3.5" /> Workforce · Permissions
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Workforce Access</h1>
                            <p class="text-sm text-blue-100/90">{{ filteredCandidates.length }} of {{ candidates.length }} manager{{ candidates.length !== 1 ? 's' : '' }} showing</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur">
                                {{ candidates.length }} candidates
                            </span>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="relative mt-6 flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <Search class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input v-model="searchQuery" type="text" placeholder="Search managers..."
                                class="w-full rounded-2xl border-0 bg-white/95 py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-lg placeholder:text-gray-400 focus:ring-2 focus:ring-white/70 outline-none transition" />
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="filteredCandidates.length === 0"
                    class="animate-fade-up flex flex-col items-center justify-center py-20 text-center bg-white dark:bg-zinc-900 rounded-3xl border border-dashed border-gray-200 dark:border-zinc-800 shadow-sm">
                    <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-4 animate-bounce-soft">
                        <Users class="h-9 w-9 text-indigo-400" />
                    </div>
                    <p class="text-sm font-black text-gray-700 dark:text-gray-200">{{ candidates.length === 0 ? 'No managers yet' : 'No matches found' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ candidates.length === 0 ? 'Eligible managers will appear here.' : 'Try a different search.' }}</p>
                    <button v-if="searchQuery" @click="searchQuery=''"
                        class="mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 transition active:scale-95">Clear search</button>
                </div>

                <!-- Managers grid -->
                <TransitionGroup v-else name="card" tag="div" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="(user, i) in filteredCandidates"
                        :key="user.id"
                        :style="{ transitionDelay: `${Math.min(i * 40, 400)}ms` }"
                        class="group relative flex flex-col bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1.5 hover:border-indigo-200 dark:hover:border-indigo-800 hover:scale-[1.01] transition-all duration-300 p-5 overflow-hidden"
                    >
                        <!-- hover glow -->
                        <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                        <div class="absolute left-0 top-5 bottom-5 w-1 bg-gradient-to-b from-indigo-500 to-fuchsia-500 rounded-r-full scale-y-0 group-hover:scale-y-100 origin-center transition-transform duration-300" />

                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 flex items-center justify-center text-white text-sm font-black shadow-lg uppercase flex-shrink-0 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                                    {{ getInitials(user.name) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-gray-900 dark:text-white truncate tracking-tight group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors">
                                        {{ user.name }}
                                    </p>
                                    <p class="flex items-center gap-1 text-[10px] text-indigo-600 dark:text-indigo-400 font-bold uppercase truncate">
                                        <UserCog class="h-3 w-3" /> {{ user.role ?? user.position ?? 'Manager' }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-[9px] font-black uppercase px-2.5 py-1 rounded-full ring-1 flex-shrink-0 ml-2 flex items-center gap-1 bg-indigo-100 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/30">
                                <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" /> {{ (user.permissions ?? []).length }} rules
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-1.5 mb-4 min-h-[28px]">
                            <span v-if="!(user.permissions ?? []).length" class="text-[11px] text-gray-400 italic font-medium">No workforce permissions assigned</span>
                            <span v-for="(perm, idx) in user.permissions" :key="idx" :class="['px-2 py-0.5 rounded-full text-[9px] font-black uppercase ring-1 inline-flex items-center gap-1', getAccessLevelClass(perm.access_level)]">
                                <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />{{ perm.module }} ({{ perm.access_level }})
                            </span>
                        </div>

                        <div class="mt-auto flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-zinc-800">
                            <button @click="openPermissionModal(user)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-400 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300 hover:scale-110" title="Edit access"><Edit class="h-4 w-4" /></button>
                            <button v-if="user.permissions.length > 0" @click="removeAllPermissions(user.id)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-600 hover:text-white transition-all duration-300 hover:scale-110" title="Remove all"><Trash2 class="h-4 w-4" /></button>
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </div>

        <Transition name="modal">
            <div v-if="isPermissionModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closePermissionModal">
                <div class="modal-panel bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl max-w-2xl w-full flex flex-col overflow-hidden">
                    <div class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-5 flex justify-between items-center shrink-0">
                        <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl animate-float" />
                        <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 18px 18px;" />
                        <h2 class="relative text-lg font-black tracking-tight text-white uppercase truncate">Edit Access: {{ selectedUser?.name }}</h2>
                        <button @click="closePermissionModal" class="relative h-8 w-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition shrink-0"><X class="h-5 w-5" /></button>
                    </div>
                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div v-for="(perm, idx) in permissionsList" :key="idx" class="border border-slate-200 dark:border-zinc-700 rounded-2xl p-4 relative flex flex-col gap-4 bg-white/60 dark:bg-zinc-900/60">
                            <button @click="removePermissionRow(idx)" class="absolute top-2 right-2 p-1.5 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition"><Trash2 class="h-4 w-4" /></button>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <select v-model="perm.module" class="px-3 py-2.5 rounded-2xl bg-slate-100 dark:bg-zinc-800 border-none text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select Module</option>
                                    <option v-for="mod in modules" :key="mod" :value="mod">{{ mod }}</option>
                                </select>
                                <select v-model="perm.access_level" class="px-3 py-2.5 rounded-2xl bg-slate-100 dark:bg-zinc-800 border-none text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                                    <!-- <option value="view">View</option>
                                    <option value="schedule">Schedule</option> -->
                                    <option value="manage">Manage</option>
                                </select>
                            </div>
                        </div>
                        <button @click="addPermissionRow" class="w-full py-2.5 border-2 border-dashed border-indigo-200 dark:border-indigo-800 text-indigo-500 rounded-2xl font-bold uppercase text-xs hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">+ Add Rule</button>
                    </div>
                    <div class="p-4 border-t border-slate-100 dark:border-zinc-800 flex gap-3 bg-slate-50 dark:bg-zinc-900/50">
                        <button @click="closePermissionModal" class="flex-1 py-2.5 font-bold text-sm text-slate-500 hover:text-slate-700 transition">Cancel</button>
                        <button @click="savePermissions" class="flex-1 py-2.5 bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 text-white rounded-2xl font-extrabold text-sm shadow-lg shadow-indigo-500/20 hover:scale-[1.02] active:scale-95 transition">Save Changes</button>
                    </div>
                </div>
            </div>
        </Transition>
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
.modal-enter-from .modal-panel { opacity: 0; transform: translateY(16px) scale(0.97); }
.modal-leave-to .modal-panel { opacity: 0; transform: translateY(8px) scale(0.98); }
</style>
