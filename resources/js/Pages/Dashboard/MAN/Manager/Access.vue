<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ShieldCheck, UserCog, UserCog2, Sparkles, Users } from 'lucide-vue-next';
import { usePageAccess } from '@/composables/usePageAccess';

const { canEdit } = usePageAccess();
const canEditAccess = computed(() => canEdit('MAN', 'access'));

const props = defineProps({
    staff: Array,
});

const isProcessing = ref(false);

// One supervisor per department: the seat holder (if any) for a department.
const supervisorOfDept = (dept) =>
    props.staff?.find(s => s.is_manufacturing_supervisor && s.supervisor_department === dept) || null;

const promoteSupervisor = (user) => {
    if (!user.manufacturing_role) {
        alert('This staff member does not have a manufacturing role assigned yet.');
        return;
    }

    // One supervisor per department.
    const occupant = user.possible_department ? supervisorOfDept(user.possible_department) : null;
    if (occupant) {
        alert(`${getDepartmentLabel(user.possible_department)} already has a supervisor (${occupant.name}). Demote them first.`);
        return;
    }

    if (confirm(`Promote ${user.name} to supervisor of the ${getDepartmentLabel(user.possible_department)}?`)) {
        isProcessing.value = true;
        router.post(route('man.access.assign-supervisor'), {
            user_id: user.id,
            is_supervisor: true,
        }, {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
            }
        });
    }
};

const demoteSupervisor = (user) => {
    if (confirm(`Demote ${user.name} from supervisor position?`)) {
        isProcessing.value = true;
        router.post(route('man.access.assign-supervisor'), {
            user_id: user.id,
            is_supervisor: false,
        }, {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
            }
        });
    }
};

const formatRoleLabel = (role) => {
    if (!role) return 'Not set';
    return role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getDepartmentLabel = (dept) => {
    const labels = {
        knitting: 'Knitting Department',
        dyeing: 'Dyeing Department',
        finishing: 'Finishing Department',
        maintenance: 'Maintenance Department',
        boiler: 'Boiler Department',
    };
    return labels[dept] || dept;
};
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
                                <Sparkles class="h-3.5 w-3.5" /> MAN · Access Control
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Manufacturing Access Control</h1>
                            <span v-if="!canEditAccess" class="mt-2 inline-flex w-fit items-center rounded-full bg-amber-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-amber-800">View only</span>
                            <p class="text-sm text-blue-100/90">{{ staff?.length ?? 0 }} staff member{{ (staff?.length ?? 0) !== 1 ? 's' : '' }} · {{ staff?.filter(s => s.is_manufacturing_supervisor).length ?? 0 }} supervisors</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur">
                                {{ staff?.filter(s => s.is_manufacturing_supervisor).length ?? 0 }} supervisors
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Staff table -->
                <div class="animate-fade-up group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 transition-all duration-300 overflow-hidden" style="animation-delay: 100ms">
                    <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                    <div class="absolute left-0 top-5 bottom-5 w-1 bg-gradient-to-b from-indigo-500 to-fuchsia-500 rounded-r-full" />
                    <div class="relative flex items-center gap-3 px-6 py-4 border-b border-gray-100 dark:border-zinc-800">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 text-white shadow-lg"><Users class="h-4 w-4" /></span>
                        <h2 class="text-sm font-black tracking-tight text-gray-900 dark:text-white">Supervisor Assignments</h2>
                        <span class="ml-auto rounded-full bg-indigo-50 dark:bg-indigo-900/20 px-3 py-1 text-[11px] font-black text-indigo-700 dark:text-indigo-300">{{ staff?.length ?? 0 }} total</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-400">Staff</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-400">Current Role</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-400">Supervisor Status</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-400">Department</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-400">Assigned Supervisor Roles</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <TransitionGroup name="card" tag="tbody" class="divide-y divide-gray-100 dark:divide-zinc-800">
                                <tr v-for="(user, i) in staff" :key="user.id" :style="{ transitionDelay: `${Math.min(i * 40, 400)}ms` }" class="hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 text-white text-sm font-black shadow-lg uppercase flex-shrink-0">{{ (user.name ?? '?').charAt(0) }}</div>
                                            <div>
                                                <div class="text-sm font-black text-gray-900 dark:text-white tracking-tight">{{ user.name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-600 dark:text-gray-300 capitalize">
                                        {{ formatRoleLabel(user.manufacturing_role) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="user.is_manufacturing_supervisor" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase ring-1 bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30 inline-flex items-center gap-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />Supervisor
                                        </span>
                                        <span v-else class="text-gray-400 text-sm">Staff</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="user.supervisor_department" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase ring-1 bg-purple-100 text-purple-700 ring-purple-200 dark:bg-purple-500/15 dark:text-purple-300 dark:ring-purple-500/30">
                                            {{ getDepartmentLabel(user.supervisor_department) }}
                                        </span>
                                        <span v-else-if="user.possible_department" :title="`Promoting this staff will make them ${getDepartmentLabel(user.possible_department)} Supervisor`"
                                            class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase ring-1 ring-dashed bg-gray-50 text-gray-500 ring-gray-300 dark:bg-zinc-800 dark:text-gray-400 dark:ring-zinc-700">
                                            → {{ getDepartmentLabel(user.possible_department) }}
                                        </span>
                                        <span v-else class="text-gray-400 text-sm">—</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="user.is_manufacturing_supervisor" class="flex flex-wrap gap-1.5">
                                            <span v-for="role in user.supervisor_roles" :key="role.id"
                                                class="bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-full text-[10px] font-black uppercase">
                                                {{ formatRoleLabel(role.manufacturing_role) }}
                                            </span>
                                        </div>
                                        <span v-else class="text-gray-400 text-sm">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button v-if="!user.is_manufacturing_supervisor && user.manufacturing_role && canEditAccess"
                                            @click="promoteSupervisor(user)"
                                            :disabled="isProcessing || !!supervisorOfDept(user.possible_department)"
                                            :title="supervisorOfDept(user.possible_department) ? `Seat occupied by ${supervisorOfDept(user.possible_department).name} — demote them first` : `Promote to ${getDepartmentLabel(user.possible_department)} Supervisor`"
                                            class="px-3 py-1.5 rounded-xl text-xs font-black bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-500/25 hover:-translate-y-0.5 transition-all active:scale-95 disabled:opacity-50 items-center gap-1 inline-flex">
                                            <UserCog2 class="w-4 h-4" />
                                            Promote
                                        </button>
                                        <button v-else-if="user.is_manufacturing_supervisor && canEditAccess"
                                            @click="demoteSupervisor(user)"
                                            :disabled="isProcessing"
                                            class="px-3 py-1.5 rounded-xl text-xs font-black bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300 ring-1 ring-rose-200 dark:ring-rose-800 hover:bg-rose-100 transition active:scale-95 items-center gap-1 inline-flex">
                                            <UserCog class="w-4 h-4" />
                                            Demote
                                        </button>
                                        <span v-else class="text-gray-400 text-sm italic">No role</span>
                                    </td>
                                </tr>
                            </TransitionGroup>
                        </table>
                        <div v-if="!staff || staff.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                            <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-4 animate-bounce-soft">
                                <Users class="h-9 w-9 text-indigo-400" />
                            </div>
                            <p class="text-sm font-black text-gray-700 dark:text-gray-200">No staff found</p>
                            <p class="text-xs text-gray-400 mt-1">Staff members will appear here.</p>
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
.card-enter-active { transition: opacity 0.45s ease, transform 0.45s cubic-bezier(0.22,1,0.36,1); }
.card-enter-from { opacity: 0; transform: translateY(18px) scale(0.98); }
.card-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; position: absolute; }
.card-leave-to { opacity: 0; transform: scale(0.96); }
.card-move { transition: transform 0.4s ease; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.22,1,0.36,1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: translateY(16px) scale(0.98); }
</style>
