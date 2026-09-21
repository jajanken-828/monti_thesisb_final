<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { User as UserIcon, ShieldCheck, BadgeCheck, CalendarDays, Briefcase } from 'lucide-vue-next';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = computed(() => usePage().props.auth.user);

const photoUrl = computed(() =>
    user.value?.profile_photo_path ? `/storage/${user.value.profile_photo_path}` : null
);
const initial = computed(() => (user.value?.name || '?').charAt(0).toUpperCase());
const verified = computed(() => !!user.value?.email_verified_at);
const active = computed(() => user.value?.is_active !== false);

const fmtDate = (d?: string | null) =>
    d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }) : '—';

const details = computed(() => [
    { label: 'Employee ID', value: user.value?.employee_id || '—', mono: true },
    { label: 'Email', value: user.value?.email || '—' },
    { label: 'Role', value: user.value?.role || '—', badge: true },
    { label: 'Position', value: (user.value?.position || '—').replace(/_/g, ' ') },
    { label: 'Department', value: user.value?.department || '—' },
    { label: 'Manufacturing role', value: (user.value?.manufacturing_role || '—').replace(/_/g, ' ') },
    {
        label: 'Supervisor seat',
        value: user.value?.is_manufacturing_supervisor
            ? `Supervisor · ${user.value?.supervisor_department || ''}`
            : '—',
        badge: !!user.value?.is_manufacturing_supervisor,
    },
    { label: 'Join date', value: fmtDate(user.value?.join_date) },
    { label: 'Member since', value: fmtDate(user.value?.created_at) },
]);
</script>

<template>
    <Head title="My Profile" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="mx-auto max-w-5xl space-y-5 p-4 pb-16 sm:p-6">

                <!-- Hero -->
                <div class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 text-white shadow-xl sm:p-7">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800" />
                    <div class="absolute -top-20 -right-16 h-64 w-64 rounded-full bg-white/10 blur-3xl" />
                    <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl" />
                    <div class="relative flex flex-wrap items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white/15 text-2xl font-black ring-1 ring-white/30">
                            <img v-if="photoUrl" :src="photoUrl" alt="Profile photo" class="h-full w-full object-cover" />
                            <span v-else>{{ initial }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <UserIcon class="h-3.5 w-3.5" /> My Profile
                            </p>
                            <h1 class="mt-0.5 truncate text-2xl font-extrabold tracking-tight sm:text-3xl">{{ user?.name }}</h1>
                            <p class="mt-0.5 truncate text-sm text-blue-100/90">{{ user?.email }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-[11px] font-black uppercase">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 ring-1 ring-white/25">{{ user?.role }} · {{ (user?.position || '').replace(/_/g, ' ') }}</span>
                            <span :class="['rounded-full px-3 py-1.5', active ? 'bg-emerald-400/90 text-emerald-950' : 'bg-rose-400/90 text-rose-950']">
                                {{ active ? 'Active' : 'Inactive' }}
                            </span>
                            <span v-if="verified" class="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1.5 ring-1 ring-white/25">
                                <BadgeCheck class="h-3.5 w-3.5" /> Verified
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Full details -->
                <div class="rounded-3xl border border-gray-100 bg-white/80 p-5 shadow-sm backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-6">
                    <h2 class="flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                        <Briefcase class="h-4 w-4 text-indigo-500" /> Account details
                    </h2>
                    <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="d in details" :key="d.label"
                            class="rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <dt class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ d.label }}</dt>
                            <dd class="mt-1 truncate text-sm font-black text-slate-800 dark:text-zinc-100"
                                :class="[{ 'font-mono': d.mono }, d.badge && 'inline-block rounded-full bg-indigo-100 px-2.5 py-0.5 text-[11px] uppercase text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300']">
                                {{ d.value }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <!-- Edit -->
                    <div class="rounded-3xl border border-gray-100 bg-white/80 p-5 shadow-sm backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-6">
                        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                    </div>
                    <div class="space-y-5">
                        <!-- Security -->
                        <div class="rounded-3xl border border-gray-100 bg-white/80 p-5 shadow-sm backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-6">
                            <UpdatePasswordForm />
                        </div>
                        <!-- Account status policy -->
                        <div class="rounded-3xl border border-indigo-100 bg-indigo-50/60 p-5 dark:border-indigo-900/40 dark:bg-indigo-900/10 sm:p-6">
                            <h2 class="flex items-center gap-2 text-sm font-black text-indigo-900 dark:text-indigo-200">
                                <ShieldCheck class="h-4 w-4" /> Account status
                            </h2>
                            <p class="mt-2 flex items-start gap-2 text-xs leading-relaxed text-indigo-800/80 dark:text-indigo-200/70">
                                <CalendarDays class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                Accounts cannot be deleted by their owners. Only the IT department can suspend or
                                disable an account — please contact your IT administrator if your access needs to change.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
