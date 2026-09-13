<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    ShieldCheck, Save, UserCog, CheckCircle, AlertCircle, Crown,
    UserCheck, Loader2, Lock, Factory, Users, Eye, Pencil, X,
    Star, Briefcase, LayoutGrid, Search, ChevronRight,
    Building2, Network, GitBranch, Truck, Info, User, Phone,
    MapPin, BookOpen, Briefcase as BriefcaseIcon, Heart, AlertTriangle,
    Calendar, FileText, Globe, Image as ImageIcon, UserMinus
} from 'lucide-vue-next';

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps({
    ceo:                Object,
    vicePresident:      Object,
    secretary:          Object,
    specialOfficers:    Array,
    managers:           Array,
    supervisors:        Array,
    staff:              Array,
    allModules:         Array,
    modulePages:        Object,
    manufacturingRoles: Array,
    secretaryExists:    Boolean,
});

// ─── Core module definitions ──────────────────────────────────────────────────

const CORE_MODULES = [
    { key: 'HRM', name: 'Human Resource',        short: 'HRM', accent: '#2563eb', light: '#eff6ff', border: '#bfdbfe', ring: 'ring-blue-400',    badge: 'bg-blue-100 text-blue-800 border-blue-200'   },
    { key: 'CRM', name: 'Customer Relationship', short: 'CRM', accent: '#9333ea', light: '#faf5ff', border: '#e9d5ff', ring: 'ring-purple-400',  badge: 'bg-purple-100 text-purple-800 border-purple-200' },
    { key: 'MAN', name: 'Manufacturing',         short: 'MAN', accent: '#059669', light: '#f0fdf4', border: '#a7f3d0', ring: 'ring-emerald-400', badge: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
    { key: 'LOG', name: 'Logistics',             short: 'LOG', accent: '#ea580c', light: '#fff7ed', border: '#fed7aa', ring: 'ring-orange-400',  badge: 'bg-orange-100 text-orange-800 border-orange-200' },
];

const getModuleDef = (key) => CORE_MODULES.find(m => m.key === key) || null;

// ─── Global UI state ──────────────────────────────────────────────────────────

const searchQuery  = ref('');
const savingUsers  = ref(new Set());
const savingRoles  = ref(new Set());
const savingPages  = ref(false);

const showSuccessToast = ref(false);
const successMessage   = ref('');

// ─── Confirmation modal state ─────────────────────────────────────────────────

const confirmModal = ref(null);

function openConfirm({ title, message, onConfirm, confirmLabel = 'Confirm', confirmClass = 'bg-indigo-600 hover:bg-indigo-700', icon = 'info' }) {
    confirmModal.value = { title, message, onConfirm, confirmLabel, confirmClass, icon };
}
function closeConfirm() { confirmModal.value = null; }
function doConfirm() {
    if (confirmModal.value?.onConfirm) confirmModal.value.onConfirm();
    closeConfirm();
}

// ─── Image preview modal ──────────────────────────────────────────────────────

const imageModal = ref(null);

function openImageModal(url, title) {
    imageModal.value = { url, title };
}
function closeImageModal() {
    imageModal.value = null;
}

// ─── Selected user for detail panel ──────────────────────────────────────────

const selectedUser = ref(null);
const selectedType = ref(null);
const panelOpen    = ref(false);
const panelTab     = ref('access');

const personalInfo        = ref(null);
const personalInfoLoading = ref(false);
const personalInfoError   = ref(null);

function openPanel(user, type) {
    if (!user) return;
    selectedUser.value = user;
    selectedType.value = type;
    panelOpen.value    = true;
    panelTab.value     = 'access';
    personalInfo.value = null;
    personalInfoError.value = null;
    initUser(user);
    if (user.role === 'CRM' && user.position === 'staff') {
        fetchClientAssignments(user.id);
    } else {
        clientAssignments.value.clients = [];
        clientAssignments.value.assignedClientIds = [];
        clientAssignments.value.search = '';
    }
}

function closePanel() {
    panelOpen.value    = false;
    selectedUser.value = null;
    selectedType.value = null;
    personalInfo.value = null;
    clientAssignments.value.clients = [];
    clientAssignments.value.assignedClientIds = [];
    clientAssignments.value.search = '';
}

function switchTab(tab) {
    panelTab.value = tab;
    if (tab === 'personal' && !personalInfo.value && !personalInfoLoading.value) {
        fetchPersonalInfo(selectedUser.value.id);
    }
}

async function fetchPersonalInfo(userId) {
    personalInfoLoading.value = true;
    personalInfoError.value   = null;
    try {
        const res = await fetch(route('ceo.access.employeePersonalInfo', userId));
        if (!res.ok) throw new Error('Failed to load personal information.');
        const data = await res.json();
        personalInfo.value = data;
        if (data.user?.profile_photo) {
            selectedUser.value.profile_photo = data.user.profile_photo;
        }
    } catch (err) {
        personalInfoError.value = err.message || 'Could not load personal information.';
    } finally {
        personalInfoLoading.value = false;
    }
}

const headerProfilePhoto = computed(() => {
    if (personalInfo.value?.user?.profile_photo) {
        return personalInfo.value.user.profile_photo;
    }
    return selectedUser.value?.profile_photo || null;
});

// ─── Per-user module selections ────────────────────────────────────────────────

const selectedModules   = ref({});
const modulePermissions = ref({});
const staffRoles        = ref({});
const pagesModal        = ref(null);
const vpReturnModule    = ref('HRM');

const VP_HOME_MODULES = ['HRM','CRM','MAN','LOG','ECO','ORD','SCM','WAR','INV','PRO','FIN','PROJ','IT'];

function initUser(user) {
    if (!user) return;
    const mods = (user.granted_modules || []).map(m => m.module);
    selectedModules.value[user.id] = [...mods];

    const perms = {};
    for (const grant of (user.granted_modules || [])) {
        perms[grant.module] = grant.permission_level || 'edit';
    }
    modulePermissions.value[user.id] = perms;

    staffRoles.value[user.id] = {
        manufacturing_role:    user.manufacturing_role    || null,
        supervisor_department: user.supervisor_department || null,
        log_role:              user.log_role              || null,
    };
}

[
    ...(props.specialOfficers || []),
    ...(props.secretary ? [props.secretary] : []),
    ...(props.supervisors || []),
    ...(props.managers    || []),
    ...(props.staff       || []),
].forEach(initUser);

watch(() => props.specialOfficers, users => users?.forEach(initUser), { deep: true });
watch(() => props.secretary,       user  => { if (user) initUser(user); }, { deep: true });
watch(() => props.supervisors,     users => users?.forEach(initUser), { deep: true });
watch(() => props.managers,        users => users?.forEach(initUser), { deep: true });
watch(() => props.staff,           users => users?.forEach(initUser), { deep: true });

// ─── Computed helpers ─────────────────────────────────────────────────────────

const sl = computed(() => searchQuery.value.toLowerCase().trim());

function matchesSearch(user) {
    if (!sl.value || !user) return false;
    return (user.name  || '').toLowerCase().includes(sl.value)
        || (user.email || '').toLowerCase().includes(sl.value)
        || (user.employee_id  || '').toLowerCase().includes(sl.value)
        || (user.smart_label  || '').toLowerCase().includes(sl.value)
        || (user.role || '').toLowerCase().includes(sl.value);
}

const totalCount = computed(() =>
    (props.managers?.length        || 0) +
    (props.specialOfficers?.length || 0) +
    (props.secretary ? 1 : 0) +
    (props.supervisors?.length     || 0) +
    (props.staff?.length           || 0)
);

const managerByModule = (key) =>
    (props.managers || []).find(m => m.role === key) || null;

// HRM / CRM / LOG side by side on top, Manufacturing full-width below them.
const orderedCoreModules = computed(() =>
    [...CORE_MODULES].sort((a, b) => (a.key === 'MAN' ? 1 : 0) - (b.key === 'MAN' ? 1 : 0))
);

const staffByModule = (key) => {
    const list = (props.staff || []).filter(s => s.role === key);
    if (key === 'MAN') {
        // Legacy MAN-manager accounts (the manager role no longer exists):
        // surface them here so the CEO can demote/reassign them.
        (props.managers || []).filter(m => m.role === 'MAN').forEach(m => list.push(m));
    }
    return list;
};

// ─── MAN department branches (4 departments, one supervisor each) ────────────
const MAN_DEPTS = ['knitting', 'dyeing', 'finishing', 'maintenance'];
const manSupervisorOf = (dept) => (props.supervisors || []).find(s => s.supervisor_department === dept) || null;
const manStaffByDept = (dept) => staffByModule('MAN').filter(s => manDeptOf(s.manufacturing_role) === dept);
const unassignedManStaff = () => staffByModule('MAN').filter(s => !manDeptOf(s.manufacturing_role));

const panelUser = computed(() => selectedUser.value);
const panelId   = computed(() => selectedUser.value?.id);
const canAssignModules = computed(() =>
    panelUser.value && (
        panelUser.value.is_manufacturing_supervisor ||
        selectedType.value === 'secretary' ||
        selectedType.value === 'so'
    )
);

function hasManagerForModule(role) {
    return props.managers?.some(m => m.role === role) ?? false;
}

// ─── Display Helpers ──────────────────────────────────────────────────────────

const getInitials = name =>
    (name || '?').split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();

const avatarColors = [
    'from-violet-500 to-purple-600', 'from-blue-500 to-indigo-600',
    'from-emerald-500 to-teal-600',  'from-rose-500 to-pink-600',
    'from-amber-500 to-orange-600',  'from-cyan-500 to-blue-600',
    'from-fuchsia-500 to-pink-600',  'from-lime-500 to-green-600',
];
const getAvatarColor = name => avatarColors[(name || 'A').charCodeAt(0) % avatarColors.length];

const getModuleName = key => props.allModules?.find(m => m.key === key)?.name || key;

const getPagesForModule = moduleKey => props.modulePages?.[moduleKey] || [];

const isRootModule = (user, moduleKey) => user?.root_module === moduleKey;

const isSaving     = userId => savingUsers.value.has(userId);
const isRoleSaving = userId => savingRoles.value.has(userId);

// ─── Module permission toggle ──────────────────────────────────────────────

function setModulePerm(userId, moduleKey, level) {
    if (!modulePermissions.value[userId]) modulePermissions.value[userId] = {};
    modulePermissions.value[userId][moduleKey] = level;
}

// ─── Save modules ───────────────────────────────────────────────────────────

function saveModules(userId) {
    openConfirm({
        title: 'Save Module Permissions',
        message: `Are you sure you want to update module access for ${panelUser.value?.name}?`,
        confirmLabel: 'Save Permissions',
        confirmClass: 'bg-indigo-600 hover:bg-indigo-700',
        icon: 'shield',
        onConfirm: () => {
            if (isSaving(userId)) return;
            savingUsers.value.add(userId);
            const modules = selectedModules.value[userId] || [];
            const perms   = modulePermissions.value[userId] || {};
            router.post(route('ceo.access.updateModules'), {
                user_id: userId, modules, permissions: perms,
            }, {
                preserveScroll: true,
                onSuccess: () => showSuccess('Module permissions saved!'),
                onError:   err => showSuccess('Error: ' + (err.error || 'Could not save.')),
                onFinish:  () => savingUsers.value.delete(userId),
            });
        },
    });
}

// ─── Position update ─────────────────────────────────────────────────────────

function updatePosition(userId, pos, extra = {}) {
    const user = panelUser.value;
    const currentPos = user?.position;

    if (pos === 'secretary' && props.secretaryExists && currentPos !== 'secretary') {
        openConfirm({
            title: 'Secretary Slot Occupied',
            message: `Monti Textile only allows ONE secretary. There is already an existing secretary. Please demote the current secretary first before assigning a new one.`,
            confirmLabel: 'Understood',
            confirmClass: 'bg-red-600 hover:bg-red-700',
            icon: 'warning',
            onConfirm: () => {},
        });
        return;
    }

    // MAN has no manager: the module is handled by the 4 department supervisors.
    if (pos === 'manager' && user?.role === 'MAN') {
        openConfirm({
            title: 'No Manager Role in Manufacturing',
            message: `Manufacturing has no manager position — promote ${user?.name} to a department supervisor instead (department is derived from their Department Role).`,
            confirmLabel: 'Understood',
            confirmClass: 'bg-red-600 hover:bg-red-700',
            icon: 'warning',
            onConfirm: () => {},
        });
        return;
    }

    // Only one vice president (second in command).
    if (pos === 'vice_president' && props.vicePresident && currentPos !== 'vice_president') {
        openConfirm({
            title: 'Vice President Slot Occupied',
            message: `Monti Textile only allows ONE vice president. ${props.vicePresident.name} currently holds the seat. Please demote them first before appointing another.`,
            confirmLabel: 'Understood',
            confirmClass: 'bg-red-600 hover:bg-red-700',
            icon: 'warning',
            onConfirm: () => {},
        });
        return;
    }

    if (pos === 'manager') {
        const targetRole = user?.role;
        const existingManager = props.managers?.find(m => m.role === targetRole);
        if (existingManager && existingManager.id !== user?.id) {
            openConfirm({
                title: 'Manager Slot Occupied',
                message: `The ${getModuleName(targetRole)} module already has a manager (${existingManager.name}). Only one manager is allowed per module. Please demote the existing manager first before promoting another.`,
                confirmLabel: 'Understood',
                confirmClass: 'bg-red-600 hover:bg-red-700',
                icon: 'warning',
                onConfirm: () => {},
            });
            return;
        }
    }

    const posLabel =
        pos === 'special_officer' ? 'Special Officer' :
        pos === 'secretary'       ? 'Secretary'       :
        pos === 'vice_president'  ? 'Vice President'  :
        pos === 'staff'           ? 'Staff'            :
                                    'Manager';

    let isPromotion = false;
    if (pos === 'manager') {
        isPromotion = (currentPos === 'staff');
    } else if (pos === 'secretary' || pos === 'special_officer' || pos === 'vice_president') {
        isPromotion = true;
    }

    let title, message, confirmLabel, confirmClass, icon;
    if (isPromotion) {
        title = `Promote to ${posLabel}`;
        message = pos === 'vice_president'
            ? `Are you sure you want to appoint ${user?.name} as Vice President (second in command)? They will mirror the CEO's pages and powers.`
            : `Are you sure you want to promote ${user?.name} to ${posLabel}?`;
        confirmLabel = 'Promote';
        confirmClass = 'bg-indigo-600 hover:bg-indigo-700';
        icon = 'promote';
    } else {
        title = `Demote to ${posLabel}`;
        message = `Are you sure you want to demote ${user?.name} to ${posLabel}? All extra module access will be removed.`;
        confirmLabel = 'Demote';
        confirmClass = 'bg-red-600 hover:bg-red-700';
        icon = 'warning';
    }

    openConfirm({
        title, message, confirmLabel, confirmClass, icon,
        onConfirm: () => {
            router.post(route('ceo.access.updatePosition'), { user_id: userId, position: pos, ...extra }, {
                preserveScroll: true,
                onSuccess: () => location.reload(),
                onError: (err) => showSuccess('Error: ' + (err.error || 'Could not update position.')),
            });
        },
    });
}

// ─── Staff / supervisor role assignment ────────────────────────────────────────

function saveStaffRole(userId) {
    openConfirm({
        title: 'Assign Role',
        message: `Are you sure you want to update the role assignment for ${panelUser.value?.name}?`,
        confirmLabel: 'Assign Role',
        confirmClass: 'bg-emerald-600 hover:bg-emerald-700',
        icon: 'info',
        onConfirm: () => {
            if (isRoleSaving(userId)) return;
            savingRoles.value.add(userId);
            const role = staffRoles.value[userId] || {};
            const allUsers = [
                ...(props.staff           || []),
                ...(props.supervisors     || []),
                ...(props.managers        || []),
                ...(props.specialOfficers || []),
                ...(props.secretary ? [props.secretary] : []),
            ];
            const user = allUsers.find(u => u.id === userId);
            if (!user) { savingRoles.value.delete(userId); return; }

            const payload = { user_id: userId };
            if (user.role === 'MAN')              payload.manufacturing_role    = role.manufacturing_role;
            if (user.is_manufacturing_supervisor) payload.supervisor_department = role.supervisor_department;
            if (user.role === 'LOG')              payload.log_role              = role.log_role;

            router.post(route('ceo.access.assignStaffRole'), payload, {
                preserveScroll: true,
                onSuccess: () => showSuccess('Role assigned successfully!'),
                onError:   err => showSuccess('Error: ' + (err.error || 'Could not assign.')),
                onFinish:  () => savingRoles.value.delete(userId),
            });
        },
    });
}

// ─── MAN department mapping (mirrors ManAccessController::getDepartmentFromRole) ──

const MAN_ROLE_DEPARTMENT = {
    knitting_yarn: 'knitting', knitting_mechanic: 'knitting',
    dyeing_color: 'dyeing', dyeing_fabric_softener: 'dyeing', dyeing_squeezer: 'dyeing',
    dyeing_ironing: 'dyeing', dyeing_packaging: 'dyeing', dyeing_lab_chemist: 'dyeing',
    checker_quality: 'finishing',
    maintenance_checker: 'maintenance', pollution_control_operator: 'maintenance', safety_officer: 'maintenance',
    boiler_operator: 'boiler',
};
const MAN_DEPT_LABELS = {
    knitting: 'Knitting Department',
    dyeing: 'Dyeing Department',
    finishing: 'Finishing Department',
    maintenance: 'Maintenance Department',
    boiler: 'Boiler Department',
};
const manDeptOf = (role) => MAN_ROLE_DEPARTMENT[role] || null;

// ─── Promote a MAN staff member to department supervisor ─────────────────────

function promoteManSupervisor(userId) {
    const user = panelUser.value;
    const dept = manDeptOf(user?.manufacturing_role);
    if (!dept) {
        showSuccess('Assign a Department Role first — the supervisor department is derived from it.');
        return;
    }
    openConfirm({
        title: `Promote to ${MAN_DEPT_LABELS[dept]} Supervisor`,
        message: `Are you sure you want to promote ${user?.name} to ${MAN_DEPT_LABELS[dept]} Supervisor? They will oversee all staff in that department.`,
        confirmLabel: 'Promote',
        confirmClass: 'bg-emerald-600 hover:bg-emerald-700',
        icon: 'promote',
        onConfirm: () => {
            router.post(route('man.access.assign-supervisor'), { user_id: userId, is_supervisor: true }, {
                preserveScroll: true,
                onSuccess: () => location.reload(),
                onError: (err) => showSuccess('Error: ' + (err.error || 'Could not promote to supervisor.')),
            });
        },
    });
}

// ─── Staff pages modal ───────────────────────────────────────────────────────

function openPagesModal(user) {
    const existing = user.page_permissions || [];
    let allPages = getPagesForModule(user.role);

    // ✅ For staff members of core modules (HRM, CRM, MAN, LOG), the 'dashboard' page
    // is granted by default and does not need to be shown in the permissions UI.
    // This matches the behaviour of the HRM module and prevents accidental removal.
    const coreModules = ['HRM', 'CRM', 'MAN', 'LOG'];
    if (user.position === 'staff' && coreModules.includes(user.role)) {
        allPages = allPages.filter(pg => pg.key !== 'dashboard');
    }

    pagesModal.value = {
        user,
        pages: allPages.map(pg => {
            const found = existing.find(ep => ep.page === pg.key);
            return { page: pg.key, label: pg.label, enabled: !!found, permission: found?.permission_level || 'edit' };
        }),
    };
}
function closePagesModal()        { pagesModal.value = null; }
function togglePage(pageObj)      { pageObj.enabled = !pageObj.enabled; }
function setPagePerm(pageObj, lv) { pageObj.permission = lv; }

function saveStaffPages() {
    if (!pagesModal.value || savingPages.value) return;
    openConfirm({
        title: 'Save Page Permissions',
        message: `Are you sure you want to update page permissions for ${pagesModal.value.user.name}?`,
        confirmLabel: 'Save Permissions',
        confirmClass: 'bg-indigo-600 hover:bg-indigo-700',
        icon: 'shield',
        onConfirm: () => {
            savingPages.value = true;
            const pages = pagesModal.value.pages.filter(p => p.enabled).map(p => ({ page: p.page, permission: p.permission }));
            router.post(route('ceo.access.updateStaffPages'), { user_id: pagesModal.value.user.id, pages }, {
                preserveScroll: true,
                onSuccess: () => {
                    showSuccess('Page permissions updated!');
                    if (selectedUser.value && selectedUser.value.id === pagesModal.value.user.id) {
                        const savedPerms = pages.map(p => ({
                            module: selectedUser.value.role,
                            page: p.page,
                            permission_level: p.permission
                        }));
                        // Mirror the backend fallback: always ensure 'dashboard' exists in local state
                        // so that re-opening the modal reflects what is actually stored in the DB.
                        const coreModules = ['HRM', 'CRM', 'MAN', 'LOG'];
                        const hasDashboard = savedPerms.some(p => p.page === 'dashboard');
                        if (!hasDashboard && coreModules.includes(selectedUser.value.role)) {
                            savedPerms.push({
                                module: selectedUser.value.role,
                                page: 'dashboard',
                                permission_level: 'view',
                            });
                        }
                        selectedUser.value.page_permissions = savedPerms;
                    }
                    closePagesModal();
                },
                onError:   err => showSuccess('Error: ' + (err.error || 'Could not save.')),
                onFinish:  () => { savingPages.value = false; },
            });
        },
    });
}

// ─── Success toast ────────────────────────────────────────────────────────────

function showSuccess(msg) {
    successMessage.value   = msg;
    showSuccessToast.value = true;
    setTimeout(() => { showSuccessToast.value = false; }, 3500);
}

// ─── Personal info display helpers ────────────────────────────────────────────

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
}
function noticeLabel(v) {
    const m = { immediate: 'Immediate', '15_days': '15 Days', '30_days': '30 Days', '60_days': '60 Days' };
    return m[v] || v || '—';
}
function hasIdImage(fileUrl) {
    return fileUrl && fileUrl !== null;
}

// ─── Client assignments for CRM staff ─────────────────────────────────────────

const clientAssignments = ref({
    clients: [],
    assignedClientIds: [],
    loading: false,
    search: '',
});
const isSavingClients = ref(false);

async function fetchClientAssignments(staffId) {
    if (!staffId) return;
    clientAssignments.value.loading = true;
    try {
        const res = await fetch(route('ceo.access.clientAssignments', staffId));
        const data = await res.json();
        clientAssignments.value.clients = data.clients;
        clientAssignments.value.assignedClientIds = data.assigned_client_ids;
    } catch (err) {
        console.error('Failed to load client assignments', err);
    } finally {
        clientAssignments.value.loading = false;
    }
}

const filteredClientsForAssignment = computed(() => {
    const search = clientAssignments.value.search.toLowerCase();
    if (!search) return clientAssignments.value.clients;
    return clientAssignments.value.clients.filter(client =>
        (client.company_name   || '').toLowerCase().includes(search) ||
        (client.contact_person || '').toLowerCase().includes(search) ||
        (client.email          || '').toLowerCase().includes(search)
    );
});

function isClientAssigned(clientId) {
    return clientAssignments.value.assignedClientIds.includes(clientId);
}

function toggleClientAssignment(clientId) {
    const index = clientAssignments.value.assignedClientIds.indexOf(clientId);
    if (index === -1) {
        clientAssignments.value.assignedClientIds.push(clientId);
    } else {
        clientAssignments.value.assignedClientIds.splice(index, 1);
    }
}

async function saveClientAssignments(staffId) {
    if (isSavingClients.value) return;
    isSavingClients.value = true;
    router.post(route('ceo.access.updateClientAssignments'), {
        staff_id: staffId,
        client_ids: clientAssignments.value.assignedClientIds,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess('Client assignments saved successfully!');
        },
        onError: (err) => {
            showSuccess('Error: ' + (err.error || 'Could not save assignments.'));
        },
        onFinish: () => {
            isSavingClients.value = false;
        },
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="CEO Access Control" />

        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30 p-4 sm:p-6">

            <!-- ╔══════════════════ HEADER ══════════════════╗ -->
            <div class="max-w-screen-2xl mx-auto mb-6">
                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl animate-float-delayed" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />

                    <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg animate-pop shrink-0">
                                <Network class="h-7 w-7" />
                            </div>
                            <div>
                                <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                    CEO · Access Control
                                </p>
                                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                                    Organisation Chart
                                </h1>
                                <p class="text-sm text-blue-100/90 max-w-xl leading-relaxed">
                                    Monti Textile — assign positions, module access, and page-level permissions across the entire organisation hierarchy.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-2 flex-wrap shrink-0">
                            <div class="rounded-full bg-white/15 px-4 py-2 text-center ring-1 ring-white/25 backdrop-blur min-w-[72px]">
                                <div class="text-lg font-black text-white">{{ totalCount }}</div>
                                <div class="text-[10px] font-bold uppercase tracking-wide text-blue-100">Total</div>
                            </div>
                            <div class="rounded-full bg-white/15 px-4 py-2 text-center ring-1 ring-white/25 backdrop-blur min-w-[72px]">
                                <div class="text-lg font-black text-white">{{ (props.specialOfficers?.length || 0) + (props.secretary ? 1 : 0) }}</div>
                                <div class="text-[10px] font-bold uppercase tracking-wide text-blue-100">Elevated</div>
                            </div>
                            <div class="rounded-full bg-emerald-400/90 px-4 py-2 text-center min-w-[72px]">
                                <div class="text-lg font-black text-emerald-950">{{ props.supervisors?.length || 0 }}</div>
                                <div class="text-[10px] font-black uppercase tracking-wide text-emerald-950">Supervisors</div>
                            </div>
                        </div>
                    </div>

                    <div class="relative mt-6 max-w-md">
                        <Search class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search name, email, role or label…"
                            class="w-full rounded-2xl border-0 bg-white/95 py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-lg placeholder:text-gray-400 focus:ring-2 focus:ring-white/70 outline-none transition"
                        />
                    </div>
                </div>
            </div>

            <!-- ╔══════════════════ ORG CHART ══════════════════╗ -->
            <div class="max-w-screen-2xl mx-auto">

                <!-- ── TIER 1: CEO ────────────────────────────── -->
                <div class="flex flex-col items-center mb-0">
                    <div class="tier-label flex items-center gap-2 mb-3">
                        <div class="h-px w-12 bg-amber-300/50"></div>
                        <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">President</span>
                        <div class="h-px w-12 bg-amber-300/50"></div>
                    </div>

                    <div v-if="props.ceo" class="animate-fade-up relative group">
                        <div :class="['org-node-ceo group relative overflow-hidden flex items-center gap-4 px-5 py-4 rounded-3xl border-2 border-amber-300 dark:border-amber-500/40 bg-white/80 dark:bg-zinc-900/80 backdrop-blur shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1.5 transition-all duration-300 min-w-[260px] max-w-xs',
                             matchesSearch({...props.ceo, smart_label:'CEO', role:'CEO', employee_id:''}) ? 'ring-2 ring-yellow-400 ring-offset-2' : '']">
                            <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 w-6 h-6 bg-amber-400 rounded-full flex items-center justify-center shadow-md">
                                <Crown class="w-3.5 h-3.5 text-white" />
                            </div>
                            <div class="shrink-0">
                                <img v-if="props.ceo.profile_photo" :src="props.ceo.profile_photo" :alt="props.ceo.name"
                                     class="w-14 h-14 rounded-xl object-cover ring-2 ring-amber-300 shadow" />
                                <div v-else class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold text-lg shadow-md">
                                    {{ getInitials(props.ceo.name) }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-amber-900 text-base truncate">{{ props.ceo.name }}</div>
                                <div class="text-xs text-amber-700 truncate mt-0.5">{{ props.ceo.email }}</div>
                                <div class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 bg-amber-200 text-amber-800 text-xs font-bold rounded-full border border-amber-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />
                                    <Star class="w-3 h-3 fill-current" />
                                    President
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col items-center my-0">
                        <div class="w-0.5 h-7 bg-gradient-to-b from-amber-300 to-violet-300"></div>
                        <div class="w-2 h-2 rounded-full bg-violet-300"></div>
                    </div>

                    <!-- ── TIER 1.5: VICE PRESIDENT ─────────────── -->
                    <div class="tier-label flex items-center gap-2 mb-3">
                        <div class="h-px w-12 bg-indigo-300/50"></div>
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-widest">Vice President</span>
                        <span class="text-[10px] text-indigo-400 bg-indigo-50 border border-indigo-200 rounded-full px-2 py-0.5">1 only</span>
                        <div class="h-px w-12 bg-indigo-300/50"></div>
                    </div>

                    <div v-if="props.vicePresident"
                         @click="openPanel(props.vicePresident, 'vp')"
                         :class="['relative group overflow-hidden flex items-center gap-3 px-4 py-3 rounded-3xl border-2 border-indigo-300 dark:border-indigo-700 bg-white/80 dark:bg-zinc-900/80 backdrop-blur shadow-sm cursor-pointer hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1.5 transition-all duration-300 min-w-[240px] max-w-xs',
                                  matchesSearch(props.vicePresident) ? 'ring-2 ring-yellow-400 ring-offset-2' : '']">
                        <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                        <img v-if="props.vicePresident.profile_photo" :src="props.vicePresident.profile_photo" :alt="props.vicePresident.name"
                             class="w-11 h-11 rounded-xl object-cover ring-2 ring-indigo-200 shadow shrink-0" />
                        <div v-else :class="`bg-gradient-to-br ${getAvatarColor(props.vicePresident.name)} w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-md shrink-0`">
                            {{ getInitials(props.vicePresident.name) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 text-sm truncate">{{ props.vicePresident.name }}</div>
                            <div class="text-xs text-indigo-600 font-medium truncate">{{ props.vicePresident.smart_label }}</div>
                        </div>
                        <ChevronRight class="w-4 h-4 text-indigo-400 shrink-0 group-hover:translate-x-0.5 transition-transform" />
                    </div>
                    <div v-else class="flex items-center gap-2 px-5 py-3 rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/50 text-indigo-400 text-sm min-w-[200px] justify-center">
                        <UserCheck class="w-4 h-4" />
                        No Vice President Assigned
                    </div>

                    <div class="flex flex-col items-center my-0">
                        <div class="w-0.5 h-7 bg-gradient-to-b from-indigo-300 to-violet-300"></div>
                        <div class="w-2 h-2 rounded-full bg-violet-300"></div>
                    </div>

                    <!-- ── TIER 2: SECRETARY ────────────────────── -->
                    <div class="tier-label flex items-center gap-2 mb-3">
                        <div class="h-px w-12 bg-violet-300/50"></div>
                        <span class="text-xs font-bold text-violet-600 uppercase tracking-widest">Secretary</span>
                        <span class="text-[10px] text-violet-400 bg-violet-50 border border-violet-200 rounded-full px-2 py-0.5">1 only</span>
                        <div class="h-px w-12 bg-violet-300/50"></div>
                    </div>

                    <div v-if="props.secretary"
                         @click="openPanel(props.secretary, 'secretary')"
                         :class="['relative group overflow-hidden flex items-center gap-3 px-4 py-3 rounded-3xl border-2 border-violet-300 dark:border-violet-700 bg-white/80 dark:bg-zinc-900/80 backdrop-blur shadow-sm cursor-pointer hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1.5 hover:border-indigo-300 dark:hover:border-indigo-700 transition-all duration-300 min-w-[240px] max-w-xs',
                                  matchesSearch(props.secretary) ? 'ring-2 ring-yellow-400 ring-offset-2' : '']">
                        <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                        <img v-if="props.secretary.profile_photo" :src="props.secretary.profile_photo" :alt="props.secretary.name"
                             class="w-11 h-11 rounded-xl object-cover ring-2 ring-violet-200 shadow shrink-0" />
                        <div v-else :class="`bg-gradient-to-br ${getAvatarColor(props.secretary.name)} w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-md shrink-0`">
                            {{ getInitials(props.secretary.name) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 text-sm truncate">{{ props.secretary.name }}</div>
                            <div class="text-xs text-violet-600 font-medium truncate">{{ props.secretary.smart_label }}</div>
                        </div>
                        <ChevronRight class="w-4 h-4 text-violet-400 shrink-0 group-hover:translate-x-0.5 transition-transform" />
                    </div>
                    <div v-else class="flex items-center gap-2 px-5 py-3 rounded-2xl border-2 border-dashed border-violet-200 bg-violet-50/50 text-violet-400 text-sm min-w-[200px] justify-center">
                        <UserCheck class="w-4 h-4" />
                        No Secretary Assigned
                    </div>

                    <div class="flex flex-col items-center my-0">
                        <div class="w-0.5 h-7 bg-gradient-to-b from-violet-300 to-indigo-300"></div>
                        <div class="w-2 h-2 rounded-full bg-indigo-300"></div>
                    </div>

                    <!-- ── TIER 3: GENERAL MANAGERS ─────────────── -->
                    <div class="tier-label flex items-center gap-2 mb-3">
                        <div class="h-px w-12 bg-indigo-300/50"></div>
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-widest">Special Officers</span>
                        <span class="text-[10px] text-indigo-400 bg-indigo-50 border border-indigo-200 rounded-full px-2 py-0.5">{{ props.specialOfficers?.length || 0 }}</span>
                        <div class="h-px w-12 bg-indigo-300/50"></div>
                    </div>

                    <div v-if="props.specialOfficers && props.specialOfficers.length > 0" class="flex flex-wrap justify-center gap-4">
                        <div
                            v-for="so in props.specialOfficers"
                            :key="so.id"
                            @click="openPanel(so, 'so')"
                            :class="['group relative overflow-hidden flex items-center gap-3 px-4 py-3 rounded-3xl border border-gray-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur shadow-sm cursor-pointer hover:border-indigo-200 dark:hover:border-indigo-800 hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1.5 transition-all duration-300 w-56',
                                     matchesSearch(so) ? 'ring-2 ring-yellow-400 ring-offset-1' : '']"
                        >
                            <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                            <img v-if="so.profile_photo" :src="so.profile_photo" :alt="so.name"
                                 class="w-10 h-10 rounded-lg object-cover ring-2 ring-indigo-100 shadow shrink-0" />
                            <div v-else :class="`bg-gradient-to-br ${getAvatarColor(so.name)} w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-xs shadow shrink-0`">
                                {{ getInitials(so.name) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 text-sm truncate">{{ so.name }}</div>
                                <div class="text-[11px] text-indigo-500 font-medium truncate">{{ so.smart_label }}</div>
                            </div>
                            <ChevronRight class="w-3.5 h-3.5 text-indigo-300 shrink-0 group-hover:translate-x-0.5 transition-transform" />
                        </div>
                    </div>
                    <div v-else class="flex items-center gap-2 px-5 py-3 rounded-xl border-2 border-dashed border-indigo-200 bg-indigo-50/50 text-indigo-400 text-sm justify-center">
                        <Crown class="w-4 h-4" />
                        No Special Officers Promoted Yet
                    </div>
                </div>

                <!-- ── SECTION DIVIDER ─────────────────────────── -->
                <div class="animate-fade-up flex items-center gap-4 my-8">
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-indigo-300 dark:via-indigo-700 to-transparent"></div>
                    <div class="flex items-center gap-2 px-4 py-1.5 bg-white/80 dark:bg-zinc-900/80 backdrop-blur border border-gray-100 dark:border-zinc-800 rounded-full shadow-sm">
                        <Building2 class="w-3.5 h-3.5 text-indigo-500" />
                        <span class="text-[11px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-[0.2em]">Core Module Departments</span>
                    </div>
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-indigo-300 dark:via-indigo-700 to-transparent"></div>
                </div>

                <!-- ── TIER 4-6: DEPARTMENT COLUMNS ──────────────── -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div v-for="mod in orderedCoreModules" :key="mod.key"
                         :class="['flex flex-col', mod.key === 'MAN' ? 'sm:col-span-2 xl:col-span-3' : '']">

                        <div :style="{background: mod.light, borderColor: mod.border}"
                             class="rounded-3xl border-2 px-4 py-3 flex items-center gap-2 mb-2 shadow-sm dark:bg-zinc-900/80 dark:border-zinc-800">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shadow-sm shrink-0"
                                 :style="{background: mod.accent}">
                                <Factory v-if="mod.key === 'MAN'" class="w-4 h-4 text-white" />
                                <Truck    v-else-if="mod.key === 'LOG'" class="w-4 h-4 text-white" />
                                <Users    v-else-if="mod.key === 'HRM'" class="w-4 h-4 text-white" />
                                <Briefcase v-else class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <div class="font-bold text-sm" :style="{color: mod.accent}">{{ mod.name }}</div>
                                <div class="text-[10px] text-gray-400 font-medium">{{ mod.short }} Module</div>
                            </div>
                        </div>

                        <div class="flex justify-center my-0.5">
                            <div class="w-0.5 h-4" :style="{background: mod.border}"></div>
                        </div>

                        <!-- Manager Node (not applicable to MAN: handled by the 4 department supervisors below) -->
                        <div v-if="mod.key !== 'MAN'" class="mb-2">
                            <div v-if="managerByModule(mod.key)"
                                 @click="openPanel(managerByModule(mod.key), 'manager')"
                                 :class="['group relative overflow-hidden flex items-center gap-3 px-3 py-3 rounded-3xl border-2 bg-white/80 dark:bg-zinc-900/80 backdrop-blur shadow-sm cursor-pointer hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1 transition-all duration-300',
                                          matchesSearch(managerByModule(mod.key)) ? 'ring-2 ring-yellow-400 ring-offset-1' : '']"
                                 :style="{borderColor: mod.border}">
                                <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                                <img v-if="managerByModule(mod.key).profile_photo"
                                     :src="managerByModule(mod.key).profile_photo"
                                     class="w-10 h-10 rounded-lg object-cover shadow shrink-0"
                                     :style="{outline: `2px solid ${mod.border}`}" />
                                <div v-else :class="`bg-gradient-to-br ${getAvatarColor(managerByModule(mod.key).name)} w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-xs shadow shrink-0`">
                                    {{ getInitials(managerByModule(mod.key).name) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-900 text-sm truncate">{{ managerByModule(mod.key).name }}</div>
                                    <div class="text-[11px] font-medium truncate" :style="{color: mod.accent}">Module Manager</div>
                                </div>
                                <ChevronRight class="w-3.5 h-3.5 shrink-0 group-hover:translate-x-0.5 transition-transform" :style="{color: mod.border}" />
                            </div>
                            <div v-else
                                 class="flex items-center justify-center gap-2 px-3 py-3 rounded-xl border-2 border-dashed text-sm"
                                 :style="{borderColor: mod.border, color: mod.accent, background: mod.light}">
                                <UserCog class="w-4 h-4" />
                                <span class="text-xs font-medium">No Manager</span>
                            </div>
                        </div>

                        <!-- MAN has no manager: the 4 department supervisors below run the module -->
                        <div v-if="mod.key === 'MAN'" class="mb-2">
                            <div class="flex items-center justify-center gap-2 px-3 py-3 rounded-xl border-2 border-dashed border-emerald-300 text-emerald-700 bg-emerald-50/60 text-sm">
                                <Factory class="w-4 h-4" />
                                <span class="text-xs font-medium">4 Department Supervisors</span>
                            </div>
                        </div>

                        <!-- Departments (MAN only): 4 horizontal branches, each with its supervisor + staff -->
                        <template v-if="mod.key === 'MAN'">
                            <div class="flex justify-center my-0.5">
                                <div class="w-0.5 h-3 bg-emerald-200"></div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2 mb-2">
                                <div v-for="dept in MAN_DEPTS" :key="dept" class="bg-emerald-50/60 border border-emerald-200 rounded-xl px-2 py-2">
                                    <div class="flex items-center gap-1.5 mb-2 px-1">
                                        <Factory class="w-3 h-3 text-emerald-600" />
                                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">{{ MAN_DEPT_LABELS[dept] }}</span>
                                        <span class="text-[10px] text-emerald-500 bg-emerald-100 border border-emerald-200 rounded-full px-1.5">{{ manStaffByDept(dept).length }} staff</span>
                                    </div>
                                    <!-- Supervisor seat (one per department) -->
                                    <div v-if="manSupervisorOf(dept)"
                                         @click="openPanel(manSupervisorOf(dept), 'supervisor')"
                                         :class="['group flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white border border-emerald-300 cursor-pointer hover:border-emerald-500 hover:shadow-sm transition-all mb-1.5',
                                                  matchesSearch(manSupervisorOf(dept)) ? 'ring-2 ring-yellow-400 ring-offset-1' : '']">
                                        <img v-if="manSupervisorOf(dept).profile_photo" :src="manSupervisorOf(dept).profile_photo"
                                             class="w-8 h-8 rounded-lg object-cover ring-1 ring-emerald-200 shrink-0" />
                                        <div v-else :class="`bg-gradient-to-br ${getAvatarColor(manSupervisorOf(dept).name)} w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-xs shrink-0`">
                                            {{ getInitials(manSupervisorOf(dept).name) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs font-semibold text-gray-900 truncate">{{ manSupervisorOf(dept).name }}</div>
                                            <div class="text-[10px] text-emerald-600 truncate">{{ manSupervisorOf(dept).smart_label }}</div>
                                        </div>
                                        <ChevronRight class="w-3 h-3 text-emerald-300 shrink-0" />
                                    </div>
                                    <div v-else class="text-center py-2 mb-1.5 text-[11px] text-emerald-400 border border-dashed border-emerald-200 rounded-lg bg-white/60">No supervisor — promote one below</div>
                                    <!-- Department staff -->
                                    <div v-if="manStaffByDept(dept).length > 0" class="space-y-1.5">
                                        <div
                                            v-for="s in manStaffByDept(dept)"
                                            :key="s.id"
                                            @click="openPanel(s, s.position === 'manager' ? 'manager' : 'staff')"
                                            :class="['group flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white border cursor-pointer hover:shadow-sm transition-all',
                                                     matchesSearch(s) ? 'ring-2 ring-yellow-400 ring-offset-1' : 'border-gray-200 hover:border-gray-300']"
                                        >
                                            <img v-if="s.profile_photo" :src="s.profile_photo"
                                                 class="w-7 h-7 rounded-lg object-cover ring-1 ring-gray-200 shrink-0" />
                                            <div v-else :class="`bg-gradient-to-br ${getAvatarColor(s.name)} w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-[10px] shrink-0`">
                                                {{ getInitials(s.name) }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-semibold text-gray-900 truncate">{{ s.name }}</div>
                                                <div class="text-[10px] text-gray-500 truncate">{{ s.smart_label }}</div>
                                            </div>
                                            <ChevronRight class="w-3 h-3 text-gray-300 shrink-0" />
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-2 text-[11px] text-gray-400">No staff yet</div>
                                    <!-- Boiler sub-department (nested under Maintenance) -->
                                    <div v-if="dept === 'maintenance'" class="mt-2 ml-3 pl-3 border-l-2 border-emerald-300">
                                        <div class="flex items-center gap-1.5 mb-1.5 px-1">
                                            <Factory class="w-3 h-3 text-emerald-500" />
                                            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Boiler Department</span>
                                            <span class="text-[10px] text-emerald-500 bg-emerald-100 border border-emerald-200 rounded-full px-1.5">{{ manStaffByDept('boiler').length }} staff</span>
                                        </div>
                                        <div v-if="manSupervisorOf('boiler')"
                                             @click="openPanel(manSupervisorOf('boiler'), 'supervisor')"
                                             :class="['group flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white border border-emerald-300 cursor-pointer hover:border-emerald-500 hover:shadow-sm transition-all mb-1.5',
                                                      matchesSearch(manSupervisorOf('boiler')) ? 'ring-2 ring-yellow-400 ring-offset-1' : '']">
                                            <img v-if="manSupervisorOf('boiler').profile_photo" :src="manSupervisorOf('boiler').profile_photo"
                                                 class="w-8 h-8 rounded-lg object-cover ring-1 ring-emerald-200 shrink-0" />
                                            <div v-else :class="`bg-gradient-to-br ${getAvatarColor(manSupervisorOf('boiler').name)} w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-xs shrink-0`">
                                                {{ getInitials(manSupervisorOf('boiler').name) }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-semibold text-gray-900 truncate">{{ manSupervisorOf('boiler').name }}</div>
                                                <div class="text-[10px] text-emerald-600 truncate">{{ manSupervisorOf('boiler').smart_label }}</div>
                                            </div>
                                            <ChevronRight class="w-3 h-3 text-emerald-300 shrink-0" />
                                        </div>
                                        <div v-else class="text-center py-2 mb-1.5 text-[11px] text-emerald-400 border border-dashed border-emerald-200 rounded-lg bg-white/60">No head — promote one below</div>
                                        <div v-if="manStaffByDept('boiler').length > 0" class="space-y-1.5">
                                            <div
                                                v-for="s in manStaffByDept('boiler')"
                                                :key="s.id"
                                                @click="openPanel(s, s.position === 'manager' ? 'manager' : 'staff')"
                                                :class="['group flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white border cursor-pointer hover:shadow-sm transition-all',
                                                         matchesSearch(s) ? 'ring-2 ring-yellow-400 ring-offset-1' : 'border-gray-200 hover:border-gray-300']"
                                            >
                                                <img v-if="s.profile_photo" :src="s.profile_photo"
                                                     class="w-7 h-7 rounded-lg object-cover ring-1 ring-gray-200 shrink-0" />
                                                <div v-else :class="`bg-gradient-to-br ${getAvatarColor(s.name)} w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-[10px] shrink-0`">
                                                    {{ getInitials(s.name) }}
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs font-semibold text-gray-900 truncate">{{ s.name }}</div>
                                                    <div class="text-[10px] text-gray-500 truncate">{{ s.smart_label }}</div>
                                                </div>
                                                <ChevronRight class="w-3 h-3 text-gray-300 shrink-0" />
                                            </div>
                                        </div>
                                        <div v-else class="text-center py-2 text-[11px] text-gray-400">No boiler staff yet</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Staff without a department role -->
                            <div v-if="unassignedManStaff().length > 0" class="rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/60 p-2 mb-2">
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-1 mb-1.5">No department role ({{ unassignedManStaff().length }})</div>
                                <div class="space-y-1.5">
                                    <div v-for="s in unassignedManStaff()" :key="s.id" @click="openPanel(s, 'staff')"
                                         class="group flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white border border-gray-200 cursor-pointer hover:shadow-sm transition-all">
                                        <div :class="`bg-gradient-to-br ${getAvatarColor(s.name)} w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-[10px] shrink-0`">
                                            {{ getInitials(s.name) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs font-semibold text-gray-900 truncate">{{ s.name }}</div>
                                            <div class="text-[10px] text-gray-500 truncate">{{ s.smart_label }}</div>
                                        </div>
                                        <ChevronRight class="w-3 h-3 text-gray-300 shrink-0" />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div class="flex justify-center my-0.5">
                            <div class="w-0.5 h-3" :style="{background: mod.border}"></div>
                        </div>

                        <!-- Staff (flat list; MAN shows grouped department branches above instead) -->
                        <div v-if="mod.key !== 'MAN'" class="rounded-xl border p-2 flex-1" :style="{borderColor: mod.border, background: mod.light}">
                            <div class="flex items-center gap-1.5 mb-2 px-1">
                                <Users class="w-3 h-3" :style="{color: mod.accent}" />
                                <span class="text-[10px] font-bold uppercase tracking-wider" :style="{color: mod.accent}">Staff</span>
                                <span class="text-[10px] rounded-full px-1.5" :style="{color: mod.accent, background: 'rgba(0,0,0,0.05)', border: `1px solid ${mod.border}`}">
                                    {{ staffByModule(mod.key).length }}
                                </span>
                            </div>
                            <div v-if="staffByModule(mod.key).length > 0" class="space-y-1.5">
                                <div
                                    v-for="s in staffByModule(mod.key)"
                                    :key="s.id"
                                    @click="openPanel(s, s.position === 'manager' ? 'manager' : 'staff')"
                                    :class="['group flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white border cursor-pointer hover:shadow-sm transition-all',
                                             matchesSearch(s) ? 'ring-2 ring-yellow-400 ring-offset-1' : 'border-gray-200 hover:border-gray-300']"
                                >
                                    <img v-if="s.profile_photo" :src="s.profile_photo"
                                         class="w-7 h-7 rounded-lg object-cover ring-1 ring-gray-200 shrink-0" />
                                    <div v-else :class="`bg-gradient-to-br ${getAvatarColor(s.name)} w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-[10px] shrink-0`">
                                        {{ getInitials(s.name) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-semibold text-gray-900 truncate">{{ s.name }}</div>
                                        <div class="text-[10px] text-gray-500 truncate">{{ s.smart_label }}</div>
                                    </div>
                                    <ChevronRight class="w-3 h-3 text-gray-300 shrink-0" />
                                </div>
                            </div>
                            <div v-else class="text-center py-3 text-[11px]" :style="{color: mod.accent + '80'}">No staff yet</div>
                        </div>

                    </div>
                </div>

                <div class="h-16"></div>
            </div>

            <!-- ╔══════════════════ DETAIL PANEL ══════════════════╗ -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="panelOpen && panelUser" class="fixed inset-0 z-40 flex">
                        <!-- Backdrop -->
                        <div class="flex-1 bg-black/40 backdrop-blur-sm" @click="closePanel"></div>

                        <!-- Panel -->
                        <Transition
                            enter-active-class="transition duration-300 ease-out"
                            enter-from-class="translate-x-full"
                            enter-to-class="translate-x-0"
                            leave-active-class="transition duration-200 ease-in"
                            leave-from-class="translate-x-0"
                            leave-to-class="translate-x-full"
                        >
                            <div v-if="panelOpen" class="w-full max-w-lg bg-white dark:bg-zinc-900 shadow-2xl flex flex-col" style="max-height: 100vh;">

                                <!-- Panel Header -->
                                <div class="sticky top-0 z-10 bg-white dark:bg-zinc-900 border-b border-gray-100 dark:border-zinc-800 px-5 pt-5 pb-4 shrink-0">
                                    <div class="flex items-start gap-4">
                                        <div class="shrink-0">
                                            <img v-if="headerProfilePhoto" :src="headerProfilePhoto"
                                                 class="w-14 h-14 rounded-2xl object-cover ring-2 ring-gray-200 shadow" />
                                            <div v-else :class="`bg-gradient-to-br ${getAvatarColor(panelUser.name)} w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-base shadow-md`">
                                                {{ getInitials(panelUser.name) }}
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-gray-900 text-base truncate">{{ panelUser.name }}</div>
                                            <div class="text-sm text-gray-500 truncate">{{ panelUser.email }}</div>
                                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-lg border"
                                                      :class="{
                                                        'bg-amber-50 text-amber-700 border-amber-200': selectedType === 'secretary',
                                                        'bg-indigo-50 text-indigo-700 border-indigo-200': selectedType === 'so',
                                                        'bg-blue-50 text-blue-700 border-blue-200': selectedType === 'manager',
                                                        'bg-emerald-50 text-emerald-700 border-emerald-200': selectedType === 'supervisor',
                                                        'bg-slate-50 text-slate-600 border-slate-200': selectedType === 'staff',
                                                      }">
                                                    {{ panelUser.smart_label }}
                                                </span>
                                                <span class="text-[11px] text-gray-400 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded-lg">
                                                    {{ panelUser.role }}
                                                </span>
                                                <span v-if="panelUser.employee_id" class="text-[11px] text-gray-400">{{ panelUser.employee_id }}</span>
                                            </div>
                                        </div>
                                        <button @click="closePanel" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 transition-colors shrink-0">
                                            <X class="w-5 h-5" />
                                        </button>
                                    </div>

                                    <!-- Tabs -->
                                    <div class="flex gap-1 mt-4 bg-gray-100 rounded-xl p-1">
                                        <button
                                            @click="switchTab('access')"
                                            :class="['flex-1 flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-semibold rounded-lg transition-all',
                                                panelTab === 'access'
                                                    ? 'bg-white text-gray-900 shadow-sm'
                                                    : 'text-gray-500 hover:text-gray-700']"
                                        >
                                            <ShieldCheck class="w-3.5 h-3.5" />
                                            Access Control
                                        </button>
                                        <button
                                            @click="switchTab('personal')"
                                            :class="['flex-1 flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-semibold rounded-lg transition-all',
                                                panelTab === 'personal'
                                                    ? 'bg-white text-gray-900 shadow-sm'
                                                    : 'text-gray-500 hover:text-gray-700']"
                                        >
                                            <User class="w-3.5 h-3.5" />
                                            Personal Information
                                        </button>
                                    </div>
                                </div>

                                <!-- Panel Body -->
                                <div class="flex-1 overflow-y-auto">

                                    <!-- ══ TAB: ACCESS CONTROL ══ -->
                                    <div v-if="panelTab === 'access'" class="px-5 py-4 space-y-5">

                                        <!-- Position controls (secretary / gm) -->
                                        <div v-if="selectedType === 'secretary' || selectedType === 'so'" class="panel-section">
                                            <div class="panel-section-title">
                                                <GitBranch class="w-4 h-4 text-indigo-500" />
                                                Promote / Demote
                                            </div>
                                            <div class="flex flex-wrap gap-2 mt-3">
                                                <button @click="updatePosition(panelId, 'secretary')"
                                                    :disabled="panelUser.position === 'secretary'"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-violet-200 text-violet-700 bg-violet-50 hover:bg-violet-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                                                    <UserCheck class="w-3.5 h-3.5" /> Secretary
                                                </button>
                                                <button @click="updatePosition(panelId, 'special_officer')"
                                                    :disabled="panelUser.position === 'special_officer'"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-indigo-200 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                                                    <Crown class="w-3.5 h-3.5" /> Special Officer
                                                </button>
                                                <button @click="updatePosition(panelId, 'manager')"
                                                    v-if="panelUser.role !== 'MAN'"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 text-gray-600 bg-gray-50 hover:bg-gray-100 transition-all">
                                                    <UserCog class="w-3.5 h-3.5" /> Demote to Manager
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Vice President (second in command: full CEO mirror) -->
                                        <div v-if="selectedType === 'vp'" class="panel-section">
                                            <div class="panel-section-title">
                                                <Crown class="w-4 h-4 text-indigo-500" />
                                                Vice President
                                            </div>
                                            <div class="mt-3 flex items-center gap-2 px-3 py-2.5 bg-indigo-50 border border-indigo-200 rounded-xl">
                                                <ShieldCheck class="w-4 h-4 text-indigo-500 shrink-0" />
                                                <span class="text-xs text-indigo-700 font-medium">Mirrors the CEO: dashboard, reports, audit trail, geolocation, and full module oversight.</span>
                                            </div>
                                            <div class="flex flex-wrap items-end gap-2 mt-3">
                                                <div class="flex-1 min-w-[160px]">
                                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1">Return to module as staff</label>
                                                    <select v-model="vpReturnModule"
                                                        class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-red-300 text-gray-700">
                                                        <option v-for="m in VP_HOME_MODULES" :key="m" :value="m">{{ m }}</option>
                                                    </select>
                                                </div>
                                                <button @click="updatePosition(panelId, 'staff', { role: vpReturnModule })"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 text-red-600 bg-red-50 hover:bg-red-100 transition-all">
                                                    <UserMinus class="w-3.5 h-3.5" /> Demote to Staff
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Manager Actions -->
                                        <div v-if="selectedType === 'manager'" class="panel-section">
                                            <div class="panel-section-title">
                                                <GitBranch class="w-4 h-4 text-blue-500" />
                                                Manager Actions
                                            </div>
                                            <div class="flex flex-wrap gap-2 mt-3">
                                                <button @click="updatePosition(panelId, 'secretary')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-violet-200 text-violet-700 bg-violet-50 hover:bg-violet-100 transition-all">
                                                    <UserCheck class="w-3.5 h-3.5" /> Promote to Secretary
                                                </button>
                                                <button @click="updatePosition(panelId, 'special_officer')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-indigo-200 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-all">
                                                    <Crown class="w-3.5 h-3.5" /> Promote to Special Officer
                                                </button>
                                                <button @click="updatePosition(panelId, 'staff')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 text-red-600 bg-red-50 hover:bg-red-100 transition-all">
                                                    <UserMinus class="w-3.5 h-3.5" /> Demote to Staff
                                                </button>
                                            </div>
                                            <div class="mt-3 flex items-center gap-2 px-3 py-2.5 bg-blue-50 border border-blue-200 rounded-xl">
                                                <ShieldCheck class="w-4 h-4 text-blue-500 shrink-0" />
                                                <span class="text-xs text-blue-700 font-medium">Full access to <strong>{{ getModuleName(panelUser.role) }}</strong> module</span>
                                            </div>
                                        </div>

                                        <!-- Promote staff to manager (all modules EXCEPT MAN: it has no manager) -->
                                        <div v-if="selectedType === 'staff' && panelUser.position === 'staff' && panelUser.role !== 'MAN'" class="panel-section">
                                            <div class="panel-section-title">
                                                <GitBranch class="w-4 h-4 text-green-500" />
                                                Promote to Manager
                                            </div>
                                            <button
                                                @click="updatePosition(panelId, 'manager')"
                                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition-all active:scale-95"
                                            >
                                                <Crown class="w-3.5 h-3.5" />
                                                Promote to Manager
                                            </button>
                                            <p class="text-xs text-gray-500 mt-2">Promoting this staff member will give them manager‑level access to their core module.</p>
                                            <div class="border-t border-gray-100 mt-3 pt-3">
                                                <button
                                                    @click="updatePosition(panelId, 'vice_president')"
                                                    :disabled="!!props.vicePresident"
                                                    :title="props.vicePresident ? `Seat occupied by ${props.vicePresident.name} — demote them first` : 'Appoint as second in command'"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                                                >
                                                    <Crown class="w-3.5 h-3.5" />
                                                    Appoint Vice President
                                                </button>
                                                <p class="text-xs text-gray-500 mt-2">Second in command: full CEO mirror of pages and powers. Only one seat.</p>
                                            </div>
                                        </div>

                                        <!-- Promote MAN staff to department supervisor (department derived from their role) -->
                                        <div v-if="selectedType === 'staff' && panelUser.position === 'staff' && panelUser.role === 'MAN'" class="panel-section">
                                            <div class="panel-section-title">
                                                <Factory class="w-4 h-4 text-emerald-500" />
                                                Promote to Supervisor
                                            </div>
                                            <button
                                                @click="promoteManSupervisor(panelId)"
                                                :disabled="!manDeptOf(panelUser.manufacturing_role) || !!manSupervisorOf(manDeptOf(panelUser.manufacturing_role))"
                                                :title="manSupervisorOf(manDeptOf(panelUser.manufacturing_role)) ? `Seat occupied by ${manSupervisorOf(manDeptOf(panelUser.manufacturing_role)).name} — demote them first` : ''"
                                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                                            >
                                                <UserCog class="w-3.5 h-3.5" />
                                                {{ manDeptOf(panelUser.manufacturing_role) ? `Promote to ${MAN_DEPT_LABELS[manDeptOf(panelUser.manufacturing_role)]} Supervisor` : 'Promote to Supervisor' }}
                                            </button>
                                            <p class="text-xs text-gray-500 mt-2">
                                                {{ !manDeptOf(panelUser.manufacturing_role)
                                                    ? 'Assign a Department Role above first — the supervisor department is derived from it.'
                                                    : manSupervisorOf(manDeptOf(panelUser.manufacturing_role))
                                                        ? `${manSupervisorOf(manDeptOf(panelUser.manufacturing_role)).name} currently holds this seat — demote them first (one supervisor per department).`
                                                        : `This staff belongs to ${MAN_DEPT_LABELS[manDeptOf(panelUser.manufacturing_role)]} and will supervise all of its staff.` }}
                                            </p>
                                        </div>

                                        <!-- Module Access (elevated + supervisors) -->
                                        <div v-if="canAssignModules" class="panel-section">
                                            <div class="panel-section-title">
                                                <ShieldCheck class="w-4 h-4 text-indigo-500" />
                                                Module Access
                                                <span v-if="panelUser.root_module" class="ml-1 inline-flex items-center gap-1 text-[10px] text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-full px-2 py-0.5">
                                                    <Lock class="w-2.5 h-2.5" /> Core: {{ getModuleName(panelUser.root_module) }}
                                                </span>
                                            </div>

                                            <div class="mt-3 grid grid-cols-1 gap-2">
                                                <div
                                                    v-for="moduleKey in panelUser.assignable_modules"
                                                    :key="moduleKey"
                                                    :class="['rounded-xl border p-2.5 transition-all',
                                                        selectedModules[panelId]?.includes(moduleKey)
                                                            ? 'bg-indigo-50 border-indigo-200 shadow-sm'
                                                            : 'bg-gray-50 border-gray-200',
                                                        isRootModule(panelUser, moduleKey) ? 'ring-2 ring-indigo-300' : '']"
                                                >
                                                    <label class="flex items-center gap-2 cursor-pointer select-none">
                                                        <input
                                                            type="checkbox"
                                                            :value="moduleKey"
                                                            v-model="selectedModules[panelId]"
                                                            :disabled="isRootModule(panelUser, moduleKey)"
                                                            class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-400 shrink-0"
                                                        />
                                                        <span class="text-xs font-semibold text-gray-700 flex-1 truncate">{{ getModuleName(moduleKey) }}</span>
                                                        <Lock v-if="isRootModule(panelUser, moduleKey)" class="w-3 h-3 text-indigo-400 shrink-0" />
                                                    </label>
                                                    <div v-if="selectedModules[panelId]?.includes(moduleKey) && !isRootModule(panelUser, moduleKey)" class="flex gap-1 mt-2">
                                                        <button
                                                            @click="setModulePerm(panelId, moduleKey, 'view')"
                                                            :class="(modulePermissions[panelId]?.[moduleKey] || 'edit') === 'view'
                                                                ? 'bg-amber-500 text-white border-amber-500'
                                                                : 'bg-white text-gray-500 border-gray-200 hover:bg-amber-50'"
                                                            class="flex-1 inline-flex items-center justify-center gap-1 px-2 py-1 text-[10px] font-bold rounded-lg border transition-all"
                                                        >
                                                            <Eye class="w-3 h-3" /> View
                                                        </button>
                                                        <button
                                                            @click="setModulePerm(panelId, moduleKey, 'edit')"
                                                            :class="(modulePermissions[panelId]?.[moduleKey] || 'edit') === 'edit'
                                                                ? 'bg-emerald-500 text-white border-emerald-500'
                                                                : 'bg-white text-gray-500 border-gray-200 hover:bg-emerald-50'"
                                                            class="flex-1 inline-flex items-center justify-center gap-1 px-2 py-1 text-[10px] font-bold rounded-lg border transition-all"
                                                        >
                                                            <Pencil class="w-3 h-3" /> Edit
                                                        </button>
                                                    </div>
                                                    <div v-else-if="isRootModule(panelUser, moduleKey)" class="mt-1.5 text-center text-[10px] text-indigo-500 font-semibold">Full Access</div>
                                                </div>
                                            </div>

                                            <button
                                                @click="saveModules(panelId)"
                                                :disabled="isSaving(panelId)"
                                                class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-200 transition-all active:scale-95 disabled:opacity-50"
                                            >
                                                <Loader2 v-if="isSaving(panelId)" class="w-4 h-4 animate-spin" />
                                                <Save v-else class="w-4 h-4" />
                                                {{ isSaving(panelId) ? 'Saving…' : 'Save Module Permissions' }}
                                            </button>
                                        </div>

                                        <!-- Supervisor dept -->
                                        <div v-if="selectedType === 'supervisor' && staffRoles[panelId]" class="panel-section">
                                            <div class="panel-section-title">
                                                <Factory class="w-4 h-4 text-emerald-500" />
                                                Department Assignment
                                            </div>
                                            <div class="flex gap-2 mt-3">
                                                <select
                                                    v-model="staffRoles[panelId].supervisor_department"
                                                    class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-300 text-gray-700"
                                                >
                                                    <option :value="null">-- Unassigned --</option>
                                                    <option value="knitting">Knitting</option>
                                                    <option value="dyeing">Dyeing</option>
                                                    <option value="finishing">Finishing</option>
                                                    <option value="maintenance">Maintenance</option>
                                                    <option value="boiler">Boiler</option>
                                                </select>
                                                <button
                                                    @click="saveStaffRole(panelId)"
                                                    :disabled="isRoleSaving(panelId)"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-all active:scale-95 disabled:opacity-50"
                                                >
                                                    <Loader2 v-if="isRoleSaving(panelId)" class="w-3.5 h-3.5 animate-spin" />
                                                    <Save v-else class="w-3.5 h-3.5" />
                                                    Apply
                                                </button>
                                            </div>
                                        </div>

                                        <!-- MAN staff role -->
                                        <div v-if="selectedType === 'staff' && panelUser.role === 'MAN' && staffRoles[panelId]" class="panel-section">
                                            <div class="panel-section-title">
                                                <Factory class="w-4 h-4 text-emerald-500" />
                                                Department Role
                                            </div>
                                            <div class="flex gap-2 mt-3">
                                                <select
                                                    v-model="staffRoles[panelId].manufacturing_role"
                                                    class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-300 text-gray-700"
                                                >
                                                    <option :value="null">-- No Role --</option>
                                                    <option v-for="r in props.manufacturingRoles" :key="r.key" :value="r.key">{{ r.label }}</option>
                                                </select>
                                                <button
                                                    @click="saveStaffRole(panelId)"
                                                    :disabled="isRoleSaving(panelId)"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-all active:scale-95 disabled:opacity-50"
                                                >
                                                    <Loader2 v-if="isRoleSaving(panelId)" class="w-3.5 h-3.5 animate-spin" />
                                                    <Save v-else class="w-3.5 h-3.5" />
                                                    Apply
                                                </button>
                                            </div>
                                        </div>

                                        <!-- LOG staff role -->
                                        <div v-if="selectedType === 'staff' && panelUser.role === 'LOG' && staffRoles[panelId]" class="panel-section">
                                            <div class="panel-section-title">
                                                <Truck class="w-4 h-4 text-orange-500" />
                                                Logistics Role
                                            </div>
                                            <div class="flex gap-2 mt-3">
                                                <select
                                                    v-model="staffRoles[panelId].log_role"
                                                    class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-orange-300 text-gray-700"
                                                >
                                                    <option :value="null">-- Unassigned --</option>
                                                    <option value="driver">Driver</option>
                                                    <option value="conductor">Conductor</option>
                                                </select>
                                                <button
                                                    @click="saveStaffRole(panelId)"
                                                    :disabled="isRoleSaving(panelId)"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-xl transition-all active:scale-95 disabled:opacity-50"
                                                >
                                                    <Loader2 v-if="isRoleSaving(panelId)" class="w-3.5 h-3.5 animate-spin" />
                                                    <Save v-else class="w-3.5 h-3.5" />
                                                    Apply
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Page Permissions (staff) -->
                                        <div v-if="selectedType === 'staff'" class="panel-section">
                                            <div class="panel-section-title">
                                                <LayoutGrid class="w-4 h-4 text-indigo-500" />
                                                Page Permissions
                                            </div>
                                            <div class="mt-2 min-h-[32px]">
                                                <div v-if="panelUser.page_permissions && panelUser.page_permissions.length > 0" class="flex flex-wrap gap-1">
                                                    <template v-for="pp in panelUser.page_permissions" :key="pp.page">
                                                        <span
                                                            v-if="pp.page !== 'dashboard'"
                                                            :class="['inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold border',
                                                                pp.permission_level === 'view'
                                                                    ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                                    : 'bg-emerald-50 text-emerald-700 border-emerald-200']"
                                                        >
                                                            <Eye v-if="pp.permission_level === 'view'" class="w-2.5 h-2.5" />
                                                            <Pencil v-else class="w-2.5 h-2.5" />
                                                            {{ pp.page }}
                                                        </span>
                                                    </template>
                                                </div>
                                                <div v-else class="text-xs text-gray-400 italic">No page access assigned yet.</div>
                                            </div>
                                            <button
                                                @click="openPagesModal(panelUser)"
                                                class="mt-3 w-full inline-flex items-center justify-center gap-2 px-3 py-2.5 text-sm font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-xl transition-all"
                                            >
                                                <LayoutGrid class="w-4 h-4" />
                                                Manage Page Permissions
                                            </button>
                                        </div>

                                        <!-- Client Assignments (CRM staff only) -->
                                        <div v-if="selectedUser && selectedUser.role === 'CRM' && selectedUser.position === 'staff'" class="panel-section">
                                            <div class="panel-section-title">
                                                <Building2 class="w-4 h-4 text-indigo-500" />
                                                Client Assignments
                                                <span class="ml-1 text-[10px] text-gray-400">(which business clients this staff can investigate)</span>
                                            </div>

                                            <div v-if="clientAssignments.loading" class="flex justify-center py-4">
                                                <Loader2 class="w-5 h-5 animate-spin text-indigo-500" />
                                            </div>

                                            <div v-else>
                                                <div class="relative mt-2">
                                                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                                                    <input
                                                        v-model="clientAssignments.search"
                                                        type="text"
                                                        placeholder="Search clients..."
                                                        class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:ring-1 focus:ring-indigo-500"
                                                    />
                                                </div>

                                                <div class="mt-3 max-h-64 overflow-y-auto border border-gray-100 rounded-xl bg-white">
                                                    <div v-for="client in filteredClientsForAssignment" :key="client.id" class="flex items-center gap-2 px-3 py-2 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition">
                                                        <input
                                                            type="checkbox"
                                                            :id="'client_' + client.id"
                                                            :checked="isClientAssigned(client.id)"
                                                            @change="toggleClientAssignment(client.id)"
                                                            class="w-3.5 h-3.5 rounded text-indigo-600 focus:ring-indigo-400"
                                                        />
                                                        <label :for="'client_' + client.id" class="flex-1 text-xs font-medium text-gray-700 cursor-pointer truncate">
                                                            <span class="font-bold">{{ client.company_name }}</span>
                                                            <span class="text-gray-400 ml-1">({{ client.contact_person }})</span>
                                                        </label>
                                                    </div>
                                                    <div v-if="filteredClientsForAssignment.length === 0" class="px-3 py-4 text-center text-xs text-gray-400 italic">
                                                        No clients found.
                                                    </div>
                                                </div>

                                                <button
                                                    @click="saveClientAssignments(selectedUser.id)"
                                                    :disabled="isSavingClients"
                                                    class="mt-3 w-full inline-flex items-center justify-center gap-2 px-3 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-xs font-semibold rounded-xl shadow-md transition-all active:scale-95 disabled:opacity-50"
                                                >
                                                    <Loader2 v-if="isSavingClients" class="w-3.5 h-3.5 animate-spin" />
                                                    <Save v-else class="w-3.5 h-3.5" />
                                                    {{ isSavingClients ? 'Saving…' : 'Save Client Assignments' }}
                                                </button>
                                                <p class="text-[10px] text-gray-400 mt-2">Assigned clients will appear in the CRM staff's Investigation page.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- END ACCESS TAB -->

                                    <!-- ══ TAB: PERSONAL INFORMATION ══ -->
                                    <div v-else-if="panelTab === 'personal'" class="px-5 py-4">

                                        <div v-if="personalInfoLoading" class="flex flex-col items-center justify-center py-16 gap-3">
                                            <Loader2 class="w-8 h-8 text-indigo-500 animate-spin" />
                                            <p class="text-sm text-gray-400">Loading personal information…</p>
                                        </div>

                                        <div v-else-if="personalInfoError" class="flex flex-col items-center justify-center py-12 gap-3">
                                            <AlertCircle class="w-10 h-10 text-red-400" />
                                            <p class="text-sm text-red-500 font-medium text-center">{{ personalInfoError }}</p>
                                            <button @click="fetchPersonalInfo(panelUser.id)" class="text-xs text-indigo-600 underline">Retry</button>
                                        </div>

                                        <div v-else-if="personalInfo && !personalInfo.applicant" class="space-y-4">
                                            <div class="panel-section">
                                                <div class="panel-section-title mb-3">
                                                    <User class="w-4 h-4 text-indigo-500" /> Employee Record
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div class="info-field">
                                                        <div class="info-label">Full Name</div>
                                                        <div class="info-value">{{ personalInfo.user.name }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Employee ID</div>
                                                        <div class="info-value">{{ personalInfo.user.employee_id || '—' }}</div>
                                                    </div>
                                                    <div class="info-field col-span-2">
                                                        <div class="info-label">Email</div>
                                                        <div class="info-value truncate">{{ personalInfo.user.email }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Join Date</div>
                                                        <div class="info-value">{{ formatDate(personalInfo.user.join_date) }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Status</div>
                                                        <div class="info-value">
                                                            <span :class="personalInfo.user.is_active ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : 'text-red-500 bg-red-50 border-red-200'"
                                                                  class="text-[11px] font-bold px-2 py-0.5 rounded-full border">
                                                                {{ personalInfo.user.is_active ? 'Active' : 'Inactive' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex flex-col items-center gap-2 py-8 text-gray-400">
                                                <FileText class="w-10 h-10 text-gray-300" />
                                                <p class="text-sm font-medium">No application form found</p>
                                                <p class="text-xs text-center text-gray-400">This employee has no linked application record in the system.</p>
                                            </div>
                                        </div>

                                        <div v-else-if="personalInfo && personalInfo.applicant" class="space-y-5">
                                            <!-- Personal Details -->
                                            <div class="panel-section">
                                                <div class="panel-section-title mb-3">
                                                    <User class="w-4 h-4 text-indigo-500" /> Personal Details
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div class="info-field col-span-2">
                                                        <div class="info-label">Full Name</div>
                                                        <div class="info-value">
                                                            {{ personalInfo.applicant.first_name }}
                                                            {{ personalInfo.applicant.middle_name ? personalInfo.applicant.middle_name + ' ' : '' }}{{ personalInfo.applicant.last_name }}
                                                        </div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Date of Birth</div>
                                                        <div class="info-value">{{ formatDate(personalInfo.applicant.date_of_birth) }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Place of Birth</div>
                                                        <div class="info-value">{{ personalInfo.applicant.place_of_birth || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Sex</div>
                                                        <div class="info-value capitalize">{{ personalInfo.applicant.sex || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Civil Status</div>
                                                        <div class="info-value capitalize">{{ personalInfo.applicant.civil_status || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Age</div>
                                                        <div class="info-value">{{ personalInfo.applicant.age || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Religion</div>
                                                        <div class="info-value">{{ personalInfo.applicant.religion || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Citizenship</div>
                                                        <div class="info-value">{{ personalInfo.applicant.citizenship || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Height (cm)</div>
                                                        <div class="info-value">{{ personalInfo.applicant.height || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Weight (kg)</div>
                                                        <div class="info-value">{{ personalInfo.applicant.weight || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Languages</div>
                                                        <div class="info-value">{{ personalInfo.applicant.languages || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Special Skills</div>
                                                        <div class="info-value">{{ personalInfo.applicant.special_skills || '—' }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Contact & Address -->
                                            <div class="panel-section">
                                                <div class="panel-section-title mb-3">
                                                    <Phone class="w-4 h-4 text-indigo-500" /> Contact & Address
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div class="info-field">
                                                        <div class="info-label">Email</div>
                                                        <div class="info-value truncate">{{ personalInfo.applicant.email ?? personalInfo.user.email }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Phone</div>
                                                        <div class="info-value">{{ personalInfo.applicant.phone_number || personalInfo.applicant.contact_number || '—' }}</div>
                                                    </div>
                                                    <div class="info-field col-span-2">
                                                        <div class="info-label">Address</div>
                                                        <div class="info-value">
                                                            {{ personalInfo.applicant.street_address }}
                                                            {{ personalInfo.applicant.street_address_line2 ? ', ' + personalInfo.applicant.street_address_line2 : '' }},
                                                            {{ personalInfo.applicant.city }}, {{ personalInfo.applicant.state_province }} {{ personalInfo.applicant.postal_zip_code }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Government IDs -->
                                            <div class="panel-section">
                                                <div class="panel-section-title mb-3">
                                                    <FileText class="w-4 h-4 text-indigo-500" /> Government IDs
                                                </div>
                                                <div class="grid grid-cols-3 gap-3">
                                                    <div class="info-field">
                                                        <div class="info-label">SSS No.</div>
                                                        <div class="info-value">{{ personalInfo.applicant.sss_number || '—' }}</div>
                                                        <div v-if="hasIdImage(personalInfo.applicant.sss_file_url)" class="mt-2">
                                                            <img
                                                                :src="personalInfo.applicant.sss_file_url"
                                                                alt="SSS ID"
                                                                class="w-16 h-16 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-80 transition"
                                                                @click="openImageModal(personalInfo.applicant.sss_file_url, 'SSS ID Card')"
                                                            />
                                                        </div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">PhilHealth No.</div>
                                                        <div class="info-value">{{ personalInfo.applicant.philhealth_number || '—' }}</div>
                                                        <div v-if="hasIdImage(personalInfo.applicant.philhealth_file_url)" class="mt-2">
                                                            <img
                                                                :src="personalInfo.applicant.philhealth_file_url"
                                                                alt="PhilHealth ID"
                                                                class="w-16 h-16 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-80 transition"
                                                                @click="openImageModal(personalInfo.applicant.philhealth_file_url, 'PhilHealth ID Card')"
                                                            />
                                                        </div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Pag-IBIG No.</div>
                                                        <div class="info-value">{{ personalInfo.applicant.pagibig_number || '—' }}</div>
                                                        <div v-if="hasIdImage(personalInfo.applicant.pagibig_file_url)" class="mt-2">
                                                            <img
                                                                :src="personalInfo.applicant.pagibig_file_url"
                                                                alt="Pag-IBIG ID"
                                                                class="w-16 h-16 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-80 transition"
                                                                @click="openImageModal(personalInfo.applicant.pagibig_file_url, 'Pag-IBIG ID Card')"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Family Background -->
                                            <div class="panel-section">
                                                <div class="panel-section-title mb-3">
                                                    <Heart class="w-4 h-4 text-rose-500" /> Family Background
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div class="info-field">
                                                        <div class="info-label">Father's Name</div>
                                                        <div class="info-value">{{ personalInfo.applicant.father_name || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Father's Address</div>
                                                        <div class="info-value">{{ personalInfo.applicant.father_address || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Mother's Name</div>
                                                        <div class="info-value">{{ personalInfo.applicant.mother_name || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Mother's Address</div>
                                                        <div class="info-value">{{ personalInfo.applicant.mother_address || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Spouse Name</div>
                                                        <div class="info-value">{{ personalInfo.applicant.spouse_name || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Spouse Occupation</div>
                                                        <div class="info-value">{{ personalInfo.applicant.spouse_occupation || '—' }}</div>
                                                    </div>
                                                    <div class="info-field col-span-2">
                                                        <div class="info-label">Spouse Address</div>
                                                        <div class="info-value">{{ personalInfo.applicant.spouse_address || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">No. of Children</div>
                                                        <div class="info-value">{{ personalInfo.applicant.number_of_children ?? '—' }}</div>
                                                    </div>
                                                </div>
                                                <div v-if="personalInfo.applicant.children && personalInfo.applicant.children.length" class="mt-3">
                                                    <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Children</div>
                                                    <div class="space-y-1">
                                                        <div v-for="(child, i) in personalInfo.applicant.children" :key="i"
                                                             class="flex items-center gap-2 text-xs bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                                                            <span class="w-5 h-5 bg-indigo-100 text-indigo-600 font-bold rounded-full flex items-center justify-center text-[10px] shrink-0">{{ i+1 }}</span>
                                                            <span>{{ typeof child === 'object' ? (child.name || JSON.stringify(child)) : child }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Emergency Contact -->
                                            <div class="panel-section">
                                                <div class="panel-section-title mb-3">
                                                    <Phone class="w-4 h-4 text-rose-500" /> Emergency Contact
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div class="info-field">
                                                        <div class="info-label">Name</div>
                                                        <div class="info-value">{{ personalInfo.applicant.emergency_name || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Relationship</div>
                                                        <div class="info-value">{{ personalInfo.applicant.emergency_relationship || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Phone</div>
                                                        <div class="info-value">{{ personalInfo.applicant.emergency_phone || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Address</div>
                                                        <div class="info-value">{{ personalInfo.applicant.emergency_address || '—' }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Education -->
                                            <div class="panel-section">
                                                <div class="panel-section-title mb-3">
                                                    <BookOpen class="w-4 h-4 text-blue-500" /> Educational Background
                                                </div>
                                                <div class="space-y-2">
                                                    <div v-if="personalInfo.applicant.elementary_school" class="edu-row">
                                                        <span class="edu-level">Elementary</span>
                                                        <span class="edu-school">{{ personalInfo.applicant.elementary_school }}</span>
                                                        <span class="edu-year">{{ personalInfo.applicant.elementary_year || '—' }}</span>
                                                    </div>
                                                    <div v-if="personalInfo.applicant.high_school" class="edu-row">
                                                        <span class="edu-level">High School</span>
                                                        <span class="edu-school">{{ personalInfo.applicant.high_school }}</span>
                                                        <span class="edu-year">{{ personalInfo.applicant.high_year || '—' }}</span>
                                                    </div>
                                                    <div v-if="personalInfo.applicant.college" class="edu-row">
                                                        <span class="edu-level">College</span>
                                                        <span class="edu-school">{{ personalInfo.applicant.college }}</span>
                                                        <span class="edu-year">{{ personalInfo.applicant.college_year || '—' }}</span>
                                                    </div>
                                                    <div v-if="personalInfo.applicant.vocational" class="edu-row">
                                                        <span class="edu-level">Vocational</span>
                                                        <span class="edu-school">{{ personalInfo.applicant.vocational }}</span>
                                                        <span class="edu-year">{{ personalInfo.applicant.vocational_year || '—' }}</span>
                                                    </div>
                                                    <div v-if="!personalInfo.applicant.elementary_school && !personalInfo.applicant.high_school && !personalInfo.applicant.college && !personalInfo.applicant.vocational"
                                                         class="text-xs text-gray-400 italic text-center py-2">No education records provided.</div>
                                                </div>
                                            </div>

                                            <!-- Employment History -->
                                            <div class="panel-section">
                                                <div class="panel-section-title mb-3">
                                                    <BriefcaseIcon class="w-4 h-4 text-indigo-500" /> Employment History
                                                </div>
                                                <div v-if="personalInfo.applicant.employment_records && personalInfo.applicant.employment_records.length" class="space-y-2">
                                                    <div v-for="(rec, i) in personalInfo.applicant.employment_records" :key="i"
                                                         class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-3">
                                                        <div class="text-xs font-bold text-gray-800">{{ rec.company || rec.company_name || '—' }}</div>
                                                        <div class="text-[11px] text-gray-500 mt-0.5">{{ rec.position || rec.role || '—' }} · {{ rec.department || '—' }}</div>
                                                        <div class="text-[10px] text-gray-400 mt-0.5">{{ rec.when || rec.period || rec.duration || '—' }}</div>
                                                    </div>
                                                </div>
                                                <div v-else-if="personalInfo.applicant.previous_employment_company" class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-3">
                                                    <div class="text-xs font-bold text-gray-800">{{ personalInfo.applicant.previous_employment_company }}</div>
                                                    <div class="text-[11px] text-gray-500 mt-0.5">
                                                        {{ personalInfo.applicant.previous_employment_position || '—' }} · {{ personalInfo.applicant.previous_employment_department || '—' }}
                                                    </div>
                                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ personalInfo.applicant.previous_employment_when || '—' }}</div>
                                                </div>
                                                <div v-else class="text-xs text-gray-400 italic text-center py-2">No previous employment on record.</div>
                                            </div>

                                            <!-- Machine Operation -->
                                            <div v-if="personalInfo.applicant.machine_operation" class="panel-section">
                                                <div class="panel-section-title mb-2">
                                                    <Factory class="w-4 h-4 text-emerald-500" /> Machine Operation Skills
                                                </div>
                                                <p class="text-xs text-gray-700">{{ personalInfo.applicant.machine_operation }}</p>
                                            </div>

                                            <!-- Referral -->
                                            <div class="panel-section">
                                                <div class="panel-section-title mb-3">
                                                    <Globe class="w-4 h-4 text-gray-500" /> Referral & Related Employees
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div class="info-field">
                                                        <div class="info-label">Referred By</div>
                                                        <div class="info-value">{{ personalInfo.applicant.referred_by || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Referrer Address</div>
                                                        <div class="info-value">{{ personalInfo.applicant.referred_by_address || '—' }}</div>
                                                    </div>
                                                </div>
                                                <div v-if="personalInfo.applicant.related_employees" class="mt-2">
                                                    <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Related Employees</div>
                                                    <div class="text-xs text-gray-700">
                                                        {{ Array.isArray(personalInfo.applicant.related_employees) ? personalInfo.applicant.related_employees.join(', ') : personalInfo.applicant.related_employees }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Application Details -->
                                            <div class="panel-section">
                                                <div class="panel-section-title mb-3">
                                                    <Calendar class="w-4 h-4 text-indigo-500" /> Application Details
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div class="info-field">
                                                        <div class="info-label">Position Applied</div>
                                                        <div class="info-value">{{ personalInfo.applicant.position_applied || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Notice Period</div>
                                                        <div class="info-value">{{ noticeLabel(personalInfo.applicant.notice_period) }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Application Date</div>
                                                        <div class="info-value">{{ formatDate(personalInfo.applicant.application_date) }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">App. Status</div>
                                                        <div class="info-value">
                                                            <span :class="{
                                                                'text-emerald-700 bg-emerald-50 border-emerald-200': personalInfo.applicant.status === 'Passed',
                                                                'text-red-600 bg-red-50 border-red-200': personalInfo.applicant.status?.includes('Failed') || personalInfo.applicant.status === 'Rejected',
                                                                'text-amber-700 bg-amber-50 border-amber-200': personalInfo.applicant.status === 'Pending',
                                                                'text-gray-600 bg-gray-50 border-gray-200': !['Passed','Rejected','Pending'].includes(personalInfo.applicant.status),
                                                            }" class="text-[11px] font-bold px-2 py-0.5 rounded-full border">
                                                                {{ personalInfo.applicant.status || '—' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Employee ID</div>
                                                        <div class="info-value">{{ personalInfo.user.employee_id || '—' }}</div>
                                                    </div>
                                                    <div class="info-field">
                                                        <div class="info-label">Join Date</div>
                                                        <div class="info-value">{{ formatDate(personalInfo.user.join_date) }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <!-- end applicant data -->

                                    </div>
                                    <!-- END PERSONAL TAB -->

                                </div>
                                <!-- end panel body -->

                            </div>
                        </Transition>
                    </div>
                </Transition>
            </Teleport>

            <!-- ╔══════════════════ IMAGE PREVIEW MODAL ══════════════════╗ -->
            <Teleport to="body">
                <Transition name="modal">
                    <div v-if="imageModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closeImageModal"></div>
                        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden">
                            <div class="relative overflow-hidden flex items-center justify-between px-6 py-4 bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 text-white">
                                <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl animate-float" />
                                <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                                <h3 class="relative text-lg font-black tracking-tight">{{ imageModal.title }}</h3>
                                <button @click="closeImageModal" class="relative p-1.5 rounded-xl bg-white/15 hover:bg-white/25 ring-1 ring-white/25 transition active:scale-95">
                                    <X class="w-5 h-5" />
                                </button>
                            </div>
                            <div class="p-4 flex justify-center bg-gray-100">
                                <img :src="imageModal.url" :alt="imageModal.title" class="max-w-full max-h-[70vh] object-contain rounded-lg" />
                            </div>
                        </div>
                    </div>
                </Transition>
            </Teleport>

            <!-- ╔══════════════════ STAFF PAGES MODAL ══════════════════╗ -->
            <Teleport to="body">
                <Transition name="modal">
                <div v-if="pagesModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closePagesModal"></div>
                    <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden">
                        <div class="relative overflow-hidden flex items-center justify-between px-6 py-4 bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 text-white">
                            <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl animate-float" />
                            <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                            <div class="relative">
                                <h3 class="text-base font-black tracking-tight flex items-center gap-2">
                                    <LayoutGrid class="w-4 h-4" />
                                    Page Permissions
                                </h3>
                                <p class="text-xs text-blue-100 mt-0.5">
                                    {{ pagesModal.user.name }} · <span class="font-bold">{{ pagesModal.user.role }}</span> · {{ pagesModal.user.smart_label }}
                                </p>
                            </div>
                            <button @click="closePagesModal" class="relative p-1.5 rounded-xl bg-white/15 hover:bg-white/25 ring-1 ring-white/25 transition active:scale-95">
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="px-6 py-2 bg-gray-50 border-b border-gray-100 flex items-center gap-4 text-xs text-gray-500">
                            <span class="flex items-center gap-1"><input type="checkbox" checked class="w-3.5 h-3.5 rounded" disabled /> Enabled</span>
                            <span class="flex items-center gap-1 text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200"><Eye class="w-3 h-3" /> View Only</span>
                            <span class="flex items-center gap-1 text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200"><Pencil class="w-3 h-3" /> Can Edit</span>
                        </div>

                        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-2">
                            <div
                                v-for="pageObj in pagesModal.pages"
                                :key="pageObj.page"
                                :class="['flex items-center gap-3 px-3 py-3 rounded-xl border transition-all',
                                    pageObj.enabled ? 'bg-indigo-50 border-indigo-200' : 'bg-gray-50 border-gray-200 hover:border-gray-300']"
                            >
                                <input
                                    type="checkbox"
                                    :checked="pageObj.enabled"
                                    @change="togglePage(pageObj)"
                                    class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-400 shrink-0 cursor-pointer"
                                />
                                <span :class="pageObj.enabled ? 'text-gray-800 font-semibold' : 'text-gray-500'" class="flex-1 text-sm">
                                    {{ pageObj.label }}
                                </span>
                                <div v-if="pageObj.enabled" class="flex gap-1">
                                    <button
                                        @click="setPagePerm(pageObj, 'view')"
                                        :class="pageObj.permission === 'view'
                                            ? 'bg-amber-500 text-white border-amber-500 shadow-sm'
                                            : 'bg-white text-gray-500 border-gray-200 hover:bg-amber-50 hover:text-amber-600'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg border transition-all"
                                    >
                                        <Eye class="w-3 h-3" /> View
                                    </button>
                                    <button
                                        @click="setPagePerm(pageObj, 'edit')"
                                        :class="pageObj.permission === 'edit'
                                            ? 'bg-emerald-500 text-white border-emerald-500 shadow-sm'
                                            : 'bg-white text-gray-500 border-gray-200 hover:bg-emerald-50 hover:text-emerald-600'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg border transition-all"
                                    >
                                        <Pencil class="w-3 h-3" /> Edit
                                    </button>
                                </div>
                                <span v-else class="text-xs text-gray-400 italic">No access</span>
                            </div>
                            <div v-if="pagesModal.pages.length === 0" class="text-center py-8 text-gray-400 text-sm">
                                No pages available for this module.
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/80">
                            <span class="text-xs text-gray-400">
                                {{ pagesModal.pages.filter(p => p.enabled).length }} of {{ pagesModal.pages.length }} pages enabled
                            </span>
                            <div class="flex gap-2">
                                <button @click="closePagesModal" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                                    Cancel
                                </button>
                                <button
                                    @click="saveStaffPages"
                                    :disabled="savingPages"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-200 transition-all active:scale-95 disabled:opacity-50"
                                >
                                    <Loader2 v-if="savingPages" class="w-4 h-4 animate-spin" />
                                    <Save v-else class="w-4 h-4" />
                                    {{ savingPages ? 'Saving…' : 'Save Permissions' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                </Transition>
            </Teleport>

            <!-- ╔══════════════════ CONFIRMATION MODAL ══════════════════╗ -->
            <Teleport to="body">
                <Transition name="modal">
                    <div v-if="confirmModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeConfirm"></div>
                        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
                            <div class="relative overflow-hidden px-6 pt-7 pb-5 bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 text-white">
                                <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl animate-float" />
                                <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                                <div class="relative flex flex-col items-center">
                                <div :class="[
                                    'w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-md bg-white/15 ring-1 ring-white/30 backdrop-blur animate-pop',
                                ]">
                                    <AlertTriangle v-if="confirmModal.icon === 'warning'" class="w-7 h-7" />
                                    <Crown         v-else-if="confirmModal.icon === 'promote'" class="w-7 h-7" />
                                    <ShieldCheck   v-else-if="confirmModal.icon === 'shield'"  class="w-7 h-7" />
                                    <Info          v-else class="w-7 h-7" />
                                </div>
                                <h3 class="text-base font-black tracking-tight text-center">{{ confirmModal.title }}</h3>
                                <p class="text-sm text-blue-100 text-center mt-2 leading-relaxed">{{ confirmModal.message }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2 px-6 pb-6">
                                <button
                                    @click="closeConfirm"
                                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="doConfirm"
                                    :class="['flex-1 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all active:scale-95', confirmModal.confirmClass]"
                                >
                                    {{ confirmModal.confirmLabel }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </Teleport>

            <!-- ╔══════════════════ SUCCESS TOAST ══════════════════╗ -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-4"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="showSuccessToast" class="fixed bottom-6 right-6 z-50">
                        <div :class="successMessage.startsWith('Error') ? 'bg-red-600 shadow-red-200' : 'bg-emerald-600 shadow-emerald-200'"
                             class="flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-xl text-white max-w-sm">
                            <CheckCircle v-if="!successMessage.startsWith('Error')" class="w-5 h-5 shrink-0" />
                            <AlertCircle v-else class="w-5 h-5 shrink-0" />
                            <span class="text-sm font-semibold">{{ successMessage }}</span>
                            <button @click="showSuccessToast = false" class="ml-1 p-0.5 rounded-lg hover:bg-white/20 transition-colors">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </Transition>
            </Teleport>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
@keyframes pop { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
@keyframes bounceSoft { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.animate-fade-up { animation: fadeUp 0.6s cubic-bezier(0.22,1,0.36,1) both; }
.animate-float { animation: float 7s ease-in-out infinite; }
.animate-float-delayed { animation: float 8s ease-in-out 1.2s infinite; }
.animate-pop { animation: pop 0.5s cubic-bezier(0.22,1,0.36,1) both; }
.animate-bounce-soft { animation: bounceSoft 2.4s ease-in-out infinite; }
.animate-spin { animation: spin 1s linear infinite; }
.card-enter-active { transition: opacity 0.45s ease, transform 0.45s cubic-bezier(0.22,1,0.36,1); }
.card-enter-from { opacity: 0; transform: translateY(18px) scale(0.98); }
.card-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; position: absolute; }
.card-leave-to { opacity: 0; transform: scale(0.96); }
.card-move { transition: transform 0.4s ease; }
.modal-enter-active { transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.22,1,0.36,1); }
.modal-enter-from { opacity: 0; transform: translateY(14px) scale(0.98); }
.modal-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.modal-leave-to { opacity: 0; transform: scale(0.97); }

.panel-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 16px;
}
:global(.dark) .panel-section {
    background: rgba(24, 24, 27, 0.8);
    border-color: #3f3f46;
}
:global(.dark) .panel-section-title { color: #e4e4e7; }
:global(.dark) .info-label { color: #71717a; }
:global(.dark) .info-value { color: #e4e4e7; }
:global(.dark) .edu-row { background: #27272a; border-color: #3f3f46; }
:global(.dark) .edu-school { color: #e4e4e7; }

.panel-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.info-field {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.info-label {
    font-size: 10px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.info-value {
    font-size: 12px;
    font-weight: 500;
    color: #1f2937;
    word-break: break-word;
}

.edu-row {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 8px 12px;
}
.edu-level {
    font-size: 10px;
    font-weight: 700;
    color: #6366f1;
    background: #eef2ff;
    border: 1px solid #c7d2fe;
    border-radius: 6px;
    padding: 2px 6px;
    white-space: nowrap;
    min-width: 72px;
    text-align: center;
}
.edu-school {
    flex: 1;
    font-size: 12px;
    font-weight: 500;
    color: #1f2937;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.edu-year {
    font-size: 11px;
    color: #9ca3af;
    white-space: nowrap;
}

::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 999px; }
::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>