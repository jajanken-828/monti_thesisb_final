<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Eye, Pencil, Save, ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
    users: Array,
    pages: Array,
    permissions: Object, // current user's permissions for the CRM module (from controller)
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const isCEO = computed(() => currentUser.value?.role === 'CEO');
const isCRMmanager = computed(() => currentUser.value?.role === 'CRM' && currentUser.value?.position === 'manager');
const canEdit = computed(() => isCEO.value || isCRMmanager.value);

// Local state: for each user, store array of { page, permission }
const userPermissions = ref({});

// Initialize from props
const initPermissions = () => {
    props.users.forEach(user => {
        const perms = (user.permissions || []).map(p => ({
            page: p.page,
            permission: p.permission_level || 'edit'
        }));
        userPermissions.value[user.id] = perms;
    });
};
initPermissions();

// Helper to check if a page is enabled for a user
const isPageEnabled = (userId, pageKey) => {
    return userPermissions.value[userId]?.some(p => p.page === pageKey) || false;
};

// Helper to get permission level for a page
const getPagePermission = (userId, pageKey) => {
    const perm = userPermissions.value[userId]?.find(p => p.page === pageKey);
    return perm ? perm.permission : 'view';
};

// Toggle page enabled/disabled
const togglePage = (userId, pageKey) => {
    const perms = userPermissions.value[userId] || [];
    const existing = perms.find(p => p.page === pageKey);
    if (existing) {
        // Remove
        userPermissions.value[userId] = perms.filter(p => p.page !== pageKey);
    } else {
        // Add with default 'view'
        userPermissions.value[userId] = [...perms, { page: pageKey, permission: 'view' }];
    }
};

// Set permission level for a page
const setPagePermission = (userId, pageKey, level) => {
    const perms = userPermissions.value[userId] || [];
    const existing = perms.find(p => p.page === pageKey);
    if (existing) {
        existing.permission = level;
    } else {
        perms.push({ page: pageKey, permission: level });
    }
    userPermissions.value[userId] = [...perms];
};

// Save permissions for a user
const savePermissions = (userId) => {
    if (!canEdit.value) return;
    const pagesToSend = userPermissions.value[userId] || [];
    router.post(route('crm.access.update'), {
        user_id: userId,
        pages: pagesToSend
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: show toast
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
};

const isOwnRow = (userId) => userId === currentUser.value?.id;
</script>

<template>
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
                            <ShieldCheck class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <ShieldCheck class="h-3.5 w-3.5" /> CRM · Security
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">CRM Access Control</h1>
                            <p class="text-sm text-blue-100/90">Manage page permissions for CRM staff and managers.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur">
                                {{ users.length }} users
                            </span>
                            <span v-if="!canEdit" class="rounded-full bg-amber-300/90 px-3 py-1.5 text-xs font-black text-amber-950">View only</span>
                            <span v-else class="rounded-full bg-emerald-400/90 px-3 py-1.5 text-xs font-black text-emerald-950">Full access</span>
                        </div>
                    </div>
                </div>

                <div v-if="users.length === 0"
                    class="animate-fade-up flex flex-col items-center justify-center py-20 text-center bg-white dark:bg-zinc-900 rounded-3xl border border-dashed border-gray-200 dark:border-zinc-800 shadow-sm">
                    <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-4 animate-bounce-soft">
                        <ShieldCheck class="h-9 w-9 text-indigo-400" />
                    </div>
                    <p class="text-sm font-black text-gray-700 dark:text-gray-200">No CRM users found.</p>
                    <p class="text-xs text-gray-400 mt-1">Users with CRM roles will appear here.</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="(user, i) in users" :key="user.id"
                        :style="{ animationDelay: `${Math.min(i * 70, 400)}ms` }"
                        class="animate-fade-up group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 p-5 sm:p-6 overflow-hidden">
                        <!-- hover glow + accent -->
                        <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                        <div class="absolute left-0 top-5 bottom-5 w-1 bg-gradient-to-b from-indigo-500 to-fuchsia-500 rounded-r-full scale-y-0 group-hover:scale-y-100 origin-center transition-transform duration-300" />

                        <div class="relative flex flex-wrap justify-between items-start gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 text-white text-lg font-black shadow-lg uppercase group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                                    {{ (user.name ?? '?').charAt(0) }}
                                </div>
                                <div class="min-w-0">
                                    <h2 class="font-black tracking-tight text-gray-900 dark:text-white group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors truncate">{{ user.name }}</h2>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                        {{ user.role }} · {{ user.position }}
                                        <span v-if="user.position === 'manager'" class="ml-2 text-[10px] font-black uppercase bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 px-2 py-0.5 rounded-full ring-1 ring-violet-200 dark:ring-violet-800">Manager</span>
                                    </p>
                                </div>
                            </div>
                            <button
                                v-if="canEdit && !isOwnRow(user.id)"
                                @click="savePermissions(user.id)"
                                class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2 text-xs font-black uppercase tracking-wide text-white shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:scale-105 active:scale-95 transition-all"
                            >
                                <Save class="h-4 w-4" />
                                Save Permissions
                            </button>
                            <span v-else-if="isOwnRow(user.id) && !isCEO" class="rounded-full bg-slate-100 dark:bg-zinc-800 px-3 py-1.5 text-[11px] font-bold text-slate-500 dark:text-slate-400 italic">Your own permissions – ask a manager or CEO to change</span>
                            <span v-else-if="!canEdit" class="rounded-full bg-amber-100 dark:bg-amber-900/30 px-3 py-1.5 text-[11px] font-bold text-amber-700 dark:text-amber-300 italic">Read only</span>
                        </div>

                        <div class="relative mt-5 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div v-for="page in pages" :key="page"
                                :class="isPageEnabled(user.id, page) ? 'border-indigo-200 dark:border-indigo-800 bg-indigo-50/50 dark:bg-indigo-900/10' : 'border-gray-100 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-800/40'"
                                class="animate-fade-up rounded-2xl border p-3.5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                                <div class="flex items-center gap-3">
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input
                                            type="checkbox"
                                            :checked="isPageEnabled(user.id, page)"
                                            @change="togglePage(user.id, page)"
                                            :disabled="!canEdit || (isOwnRow(user.id) && !isCEO)"
                                            class="peer sr-only"
                                        />
                                        <span :class="isPageEnabled(user.id, page) ? 'bg-gradient-to-r from-indigo-600 to-violet-600' : 'bg-slate-300 dark:bg-zinc-700'"
                                            class="flex h-6 w-11 items-center rounded-full p-1 transition-all">
                                            <span :class="isPageEnabled(user.id, page) ? 'translate-x-5' : 'translate-x-0'"
                                                class="h-4 w-4 rounded-full bg-white shadow transition-transform" />
                                        </span>
                                    </label>
                                    <label class="text-sm font-black tracking-tight text-gray-700 dark:text-gray-200 capitalize">
                                        {{ page.replace('_', ' ') }}
                                    </label>
                                </div>
                                <div v-if="isPageEnabled(user.id, page)" class="ml-0 mt-3 flex gap-2">
                                    <button
                                        @click="setPagePermission(user.id, page, 'view')"
                                        :disabled="!canEdit || (isOwnRow(user.id) && !isCEO)"
                                        :class="[
                                            'flex flex-1 items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all active:scale-95',
                                            getPagePermission(user.id, page) === 'view'
                                                ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-500/25'
                                                : 'bg-white dark:bg-zinc-900 text-slate-500 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-zinc-700 hover:ring-amber-300'
                                        ]"
                                    >
                                        <Eye class="w-3.5 h-3.5" /> View Only
                                    </button>
                                    <button
                                        @click="setPagePermission(user.id, page, 'edit')"
                                        :disabled="!canEdit || (isOwnRow(user.id) && !isCEO)"
                                        :class="[
                                            'flex flex-1 items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all active:scale-95',
                                            getPagePermission(user.id, page) === 'edit'
                                                ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/25'
                                                : 'bg-white dark:bg-zinc-900 text-slate-500 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-zinc-700 hover:ring-emerald-300'
                                        ]"
                                    >
                                        <Pencil class="w-3.5 h-3.5" /> Can Edit
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="!canEdit && user.position === 'staff'" class="relative mt-4 flex items-center gap-2 rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/10 p-3 text-xs font-bold text-amber-700 dark:text-amber-300">
                            Only CRM managers or the CEO can change permissions.
                        </div>
                        <div v-else-if="isOwnRow(user.id) && !isCEO && canEdit" class="relative mt-4 flex items-center gap-2 rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/10 p-3 text-xs font-bold text-amber-700 dark:text-amber-300">
                            You cannot edit your own permissions. Ask another manager or the CEO.
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
</style>
