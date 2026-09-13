<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Search, X, ShieldCheck, ShieldOff, PauseCircle, RotateCcw, Save, Users } from 'lucide-vue-next';

const props = defineProps({
    users: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
    modulePages: { type: Object, default: () => ({}) },
    userPagePerms: { type: Object, default: () => ({}) },
    userModules: { type: Object, default: () => ({}) },
    assignableRoles: { type: Array, default: () => [] },
    assignablePositions: { type: Array, default: () => [] },
});

const search = ref('');
const roleFilter = ref('');
const statusFilter = ref('');
const managing = ref(null);
const activeTab = ref('account');
const suspendUntil = ref('');

const statusForm = useForm({ user_id: '', action: '', suspended_until: '' });
const positionForm = useForm({ user_id: '', position: '', role: '' });
const modulesForm = useForm({ user_id: '', modules: [] });
const pagesForm = useForm({ user_id: '', grants: [] });

// Local editable page grants: { [module]: { [page]: 'none'|'view'|'edit' } }
const pageGrants = ref({});

const openManage = (user) => {
    managing.value = user;
    activeTab.value = 'account';
    suspendUntil.value = '';

    statusForm.user_id = user.id;
    positionForm.user_id = user.id;
    positionForm.position = user.position;
    positionForm.role = user.role;
    modulesForm.user_id = user.id;
    modulesForm.modules = [...(props.userModules[user.id] || [])];
    pagesForm.user_id = user.id;

    const grants = {};
    const existing = {};
    Object.entries(props.userPagePerms[user.id] || {}).forEach(([mod, pages]) => {
        existing[String(mod).toUpperCase()] = {};
        Object.entries(pages || {}).forEach(([pg, lvl]) => {
            existing[String(mod).toUpperCase()][String(pg).toLowerCase()] = lvl;
        });
    });
    Object.entries(props.modulePages).forEach(([module, pages]) => {
        grants[module] = {};
        Object.entries(pages || {}).forEach(([key, val]) => {
            const pageKey = String(key).toLowerCase();
            grants[module][pageKey] = existing[module]?.[pageKey] || 'none';
        });
    });
    pageGrants.value = grants;
};

const accountStatus = (u) => {
    if (!u.is_active && u.suspended_until) return 'suspended';
    if (!u.is_active) return 'disabled';
    return 'active';
};

const filteredUsers = computed(() => {
    let list = props.users;
    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter((u) =>
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q) ||
            (u.employee_id || '').toLowerCase().includes(q)
        );
    }
    if (roleFilter.value) list = list.filter((u) => u.role === roleFilter.value);
    if (statusFilter.value) list = list.filter((u) => accountStatus(u) === statusFilter.value);
    return list;
});

const counts = computed(() => ({
    total: props.users.length,
    active: props.users.filter((u) => accountStatus(u) === 'active').length,
    disabled: props.users.filter((u) => accountStatus(u) === 'disabled').length,
    suspended: props.users.filter((u) => accountStatus(u) === 'suspended').length,
}));

const statusPill = (u) => ({
    active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    disabled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    suspended: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
}[accountStatus(u)]);

const pageCount = (userId) => {
    const mods = props.userPagePerms[userId] || {};
    return Object.values(mods).reduce((n, pages) => n + Object.keys(pages).length, 0);
};

const setStatus = (action) => {
    statusForm.action = action;
    statusForm.suspended_until = action === 'suspend' ? suspendUntil.value : '';
    statusForm.post(route('it.access-control.status'), {
        preserveScroll: true,
        onSuccess: () => { managing.value = null; },
    });
};

const savePosition = () => {
    positionForm.post(route('it.access-control.position'), {
        preserveScroll: true,
        onSuccess: () => { managing.value = null; },
    });
};

const saveModules = () => {
    modulesForm.post(route('it.access-control.modules'), {
        preserveScroll: true,
        onSuccess: () => { managing.value = null; },
    });
};

const savePages = () => {
    const grants = [];
    Object.entries(pageGrants.value).forEach(([module, pages]) => {
        Object.entries(pages).forEach(([page, level]) => {
            if (level === 'view' || level === 'edit') {
                grants.push({ module, page, level });
            }
        });
    });
    pagesForm.grants = grants;
    pagesForm.post(route('it.access-control.pages'), {
        preserveScroll: true,
        onSuccess: () => { managing.value = null; },
    });
};

const tabs = [
    { key: 'account', label: 'Account' },
    { key: 'position', label: 'Position' },
    { key: 'modules', label: 'Modules' },
    { key: 'pages', label: 'Page Access' },
];

const moduleTabKeys = computed(() => Object.keys(props.modulePages));
const activeModule = ref('');
const currentModule = computed(() => activeModule.value || moduleTabKeys.value[0] || '');

// Normalized [{key, label}] rows for the active module tab.
const currentPages = computed(() => {
    const pages = props.modulePages[currentModule.value] || {};
    return Object.entries(pages).map(([key, val]) => ({
        key: String(key).toLowerCase(),
        label: typeof val === 'string' ? val : val.label,
    }));
});

const setLevel = (page, level) => {
    pageGrants.value[currentModule.value][page] =
        pageGrants.value[currentModule.value][page] === level ? 'none' : level;
};
</script>

<template>
    <Head title="IT Access Control | Monti Textile" />
    <AuthenticatedLayout>
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    IT Access <span class="text-blue-600">Control</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                    Organization-wide accounts, promotion, modules and per-page view/edit grants.
                </p>
            </div>
            <div class="flex gap-2 text-xs font-black">
                <span class="px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">{{ counts.total }} total</span>
                <span class="px-3 py-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">{{ counts.active }} active</span>
                <span class="px-3 py-1.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">{{ counts.disabled }} disabled</span>
                <span class="px-3 py-1.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">{{ counts.suspended }} suspended</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex flex-col lg:flex-row gap-3 bg-slate-50/50 dark:bg-slate-800/50">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="search" type="text" placeholder="Search name, email, employee ID..."
                        class="w-full pl-9 pr-4 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-700 dark:text-slate-200 placeholder-slate-400" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <select v-model="roleFilter" class="px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200">
                        <option value="">All modules</option>
                        <option v-for="m in modules" :key="m.key" :value="m.key">{{ m.key }} — {{ m.name }}</option>
                    </select>
                    <select v-model="statusFilter" class="px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="disabled">Disabled</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/40 border-b border-slate-100 dark:border-slate-700">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Employee</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Module · Position</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Grants</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Manage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr v-for="u in filteredUsers" :key="u.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60">
                            <td class="px-6 py-3.5">
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ u.name }}</p>
                                <p class="text-[11px] text-slate-400">{{ u.email }}{{ u.employee_id ? ` · ${u.employee_id}` : '' }}</p>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-[10px] font-black">{{ u.role }}</span>
                                <span class="ml-1 text-xs text-slate-500 capitalize">{{ u.position.replace('_', ' ') }}</span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span :class="['px-2.5 py-1 rounded-full text-[10px] font-black uppercase', statusPill(u)]">
                                    {{ accountStatus(u) }}
                                </span>
                                <p v-if="u.suspended_until" class="text-[10px] text-slate-400 mt-0.5">until {{ new Date(u.suspended_until).toLocaleString() }}</p>
                            </td>
                            <td class="px-6 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                                {{ (userModules[u.id] || []).length }} modules · {{ pageCount(u.id) }} pages
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <button @click="openManage(u)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 active:scale-95">
                                    <Users class="w-3.5 h-3.5" /> Manage
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="filteredUsers.length === 0" class="py-12 text-center text-sm text-slate-400 font-bold">No employees match.</div>
            </div>
        </div>

        <!-- Manage drawer -->
        <div v-if="managing" class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-sm" @click.self="managing = null">
            <div class="bg-white dark:bg-slate-800 w-full max-w-2xl h-full overflow-y-auto border-l border-slate-200 dark:border-slate-700 flex flex-col">
                <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-start justify-between sticky top-0 bg-white dark:bg-slate-800 z-10">
                    <div>
                        <p class="text-[11px] font-mono font-bold text-slate-400">{{ managing.role }} · {{ managing.position }}</p>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">{{ managing.name }}</h2>
                        <p class="text-xs text-slate-400">{{ managing.email }}</p>
                    </div>
                    <button @click="managing = null" class="p-1.5 text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>

                <div class="px-6 pt-4 flex gap-2 border-b border-slate-100 dark:border-slate-700">
                    <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key"
                        :class="['px-4 py-2.5 text-xs font-black uppercase tracking-wider border-b-2 -mb-px transition-colors',
                            activeTab === t.key ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600']">
                        {{ t.label }}
                    </button>
                </div>

                <div class="p-6 flex-1">
                    <!-- ACCOUNT -->
                    <div v-if="activeTab === 'account'" class="space-y-3">
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm">
                            <p class="font-bold text-slate-700 dark:text-slate-200">Current status:
                                <span class="uppercase">{{ accountStatus(managing) }}</span>
                            </p>
                            <p v-if="managing.suspended_until" class="text-xs text-slate-400 mt-1">Suspended until {{ new Date(managing.suspended_until).toLocaleString() }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <button @click="setStatus('enable')" :disabled="statusForm.processing"
                                class="inline-flex items-center justify-center gap-2 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 active:scale-95 disabled:opacity-50">
                                <ShieldCheck class="w-4 h-4" /> Enable
                            </button>
                            <button @click="setStatus('disable')" :disabled="statusForm.processing"
                                class="inline-flex items-center justify-center gap-2 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 active:scale-95 disabled:opacity-50">
                                <ShieldOff class="w-4 h-4" /> Disable / Block
                            </button>
                        </div>
                        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 space-y-3">
                            <p class="text-xs font-black uppercase tracking-widest text-amber-700 dark:text-amber-400 inline-flex items-center gap-1.5">
                                <PauseCircle class="w-4 h-4" /> Temporary suspension
                            </p>
                            <input v-model="suspendUntil" type="datetime-local"
                                class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200" />
                            <div class="grid grid-cols-2 gap-3">
                                <button @click="setStatus('suspend')" :disabled="statusForm.processing || !suspendUntil"
                                    class="py-2.5 text-sm font-bold text-white bg-amber-600 rounded-xl hover:bg-amber-700 active:scale-95 disabled:opacity-50">
                                    Suspend Until Date
                                </button>
                                <button @click="setStatus('restore')" :disabled="statusForm.processing"
                                    class="inline-flex items-center justify-center gap-2 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 active:scale-95 disabled:opacity-50">
                                    <RotateCcw class="w-4 h-4" /> Restore Access
                                </button>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400">Disabled accounts cannot log in anywhere. Suspended accounts show their release date. Every action is written to Access Logs.</p>
                    </div>

                    <!-- POSITION -->
                    <div v-if="activeTab === 'position'" class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-bold text-slate-500 uppercase">Position</label>
                                <select v-model="positionForm.position" class="mt-1 w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200">
                                    <option v-for="p in assignablePositions" :key="p" :value="p">{{ p.replace('_', ' ') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-500 uppercase">Home Module</label>
                                <select v-model="positionForm.role" class="mt-1 w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200">
                                    <option v-for="r in assignableRoles" :key="r" :value="r">{{ r }}</option>
                                </select>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400">Demoting to staff clears module + page grants. Promoting to manager clears page grants (managers get full module access). One manager per module and one secretary org-wide are enforced.</p>
                        <button @click="savePosition" :disabled="positionForm.processing"
                            class="w-full py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 active:scale-95 disabled:opacity-50">
                            Save Position
                        </button>
                    </div>

                    <!-- MODULES -->
                    <div v-if="activeTab === 'modules'" class="space-y-4">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <label v-for="m in modules" :key="m.key"
                                class="flex items-center gap-2 p-2.5 rounded-xl border text-xs font-bold cursor-pointer transition-colors"
                                :class="modulesForm.modules.includes(m.key)
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
                                    : 'border-slate-200 dark:border-slate-700 text-slate-500'">
                                <input type="checkbox" :value="m.key" v-model="modulesForm.modules" class="rounded text-blue-600" />
                                {{ m.key }}
                            </label>
                        </div>
                        <button @click="saveModules" :disabled="modulesForm.processing"
                            class="w-full py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 active:scale-95 disabled:opacity-50">
                            Save Module Grants
                        </button>
                    </div>

                    <!-- PAGES -->
                    <div v-if="activeTab === 'pages'" class="space-y-4">
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="key in moduleTabKeys" :key="key" @click="activeModule = key"
                                :class="['px-3 py-1.5 text-[11px] font-black rounded-lg transition-colors',
                                    currentModule === key ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 hover:text-slate-700']">
                                {{ key }}
                            </button>
                        </div>
                        <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-900/40 border-b border-slate-200 dark:border-slate-700">
                                        <th class="px-4 py-2.5 text-[10px] font-black text-slate-400 uppercase">Page</th>
                                        <th class="px-4 py-2.5 text-[10px] font-black text-slate-400 uppercase text-right">Access</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                    <tr v-for="p in currentPages" :key="p.key">
                                        <td class="px-4 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-200">
                                            {{ p.label }}
                                            <span class="block text-[10px] font-mono font-normal text-slate-400">{{ p.key }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                            <div class="inline-flex rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 text-[11px] font-black">
                                                <button @click="setLevel(p.key, 'view')"
                                                    :class="['px-3 py-1.5 transition-colors', (pageGrants[currentModule]?.[p.key] === 'view') ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700']">VIEW</button>
                                                <button @click="setLevel(p.key, 'edit')"
                                                    :class="['px-3 py-1.5 transition-colors', (pageGrants[currentModule]?.[p.key] === 'edit') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700']">EDIT</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button @click="savePages" :disabled="pagesForm.processing"
                            class="w-full inline-flex items-center justify-center gap-2 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 active:scale-95 disabled:opacity-50">
                            <Save class="w-4 h-4" /> Save Page Permissions
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
