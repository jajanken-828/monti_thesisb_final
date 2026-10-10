<script setup>
import { usePage, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js';
import { computed, watch } from 'vue'
import {
    Menu, X, LayoutDashboard, ChevronRight, Building2, ShieldCheck, UserCog2,
    ShoppingBag, ShoppingCart, Receipt, User, HelpCircle, Truck, Navigation, MessageSquare, Clock, CalendarDays,
    History, HandCoins, Bell, FileText, Stamp, Printer, Target, Wrench, Repeat, ClipboardList,
    Zap, ScanSearch, Megaphone, UserCog,
} from 'lucide-vue-next'

import { useDropdown, useDynamicDropdowns } from './composables/useSidebarPersistence'
import { useSidebarToggle } from './composables/useSidebarToggle'
import { usePermissions } from './composables/usePermissions'
import { useManufacturingSupervisor } from './composables/useManufacturingSupervisor'
import { buildNavItems } from './navItems/buildNavItems'

const page = usePage()

// Drawer state is shared with the TopBar sidebar toggle.
const { mobileOpen: isOpen } = useSidebarToggle()

// One persisted dropdown per top-level module (same sessionStorage keys as
// desktop Sidebar.vue, so open/close state stays consistent across views).
// Dynamic MAN role dropdowns likewise share the desktop `role_` prefix.
const moduleDropdowns = {
    HRM: useDropdown('hrm'),
    CRM: useDropdown('crm'),
    MAN: useDropdown('man'),
    LOG: useDropdown('logistics'),
    ECO: useDropdown('eco'),
    ORD: useDropdown('ord'),
    SCM: useDropdown('scm'),
    WAR: useDropdown('warehouse'),
    INV: useDropdown('inventory'),
    PRO: useDropdown('pro'),
    FIN: useDropdown('fin'),
    IT: useDropdown('it'),
    WRF: useDropdown('workforce'),
}
const qualityCheckerDropdown = useDropdown('quality_checker')
const roleDropdowns = useDynamicDropdowns('role_')

// Auth state + shared data layer (same as desktop Sidebar.vue)
const user = computed(() => page.props.auth.user)
const unreadCount = computed(() => page.props.notifications_unread || 0)
const client = computed(() => page.props.auth.client)
const supplier = computed(() => page.props.auth.supplier || (page.props.auth.user?.business_name ? page.props.auth.user : null))
const applicantUser = computed(() => page.props.auth.applicant)
const currentUrl = computed(() => page.url)

const isEmployeePortal = computed(() => currentUrl.value.startsWith('/dashboard/employee-ui'))
const isClient = computed(() => !!client.value)
const isSupplier = computed(() => !!supplier.value || currentUrl.value.startsWith('/supplier'))
const isApplicant = computed(() => !!applicantUser.value || currentUrl.value.startsWith('/applicant'))

// Shared data layer — identical rules to desktop Sidebar.vue, so mobile
// navigation can never diverge from desktop.
const {
    grantedModules, rootModule, canAccessModule, canAccessWorkforce, hasWorkforcePermission,
    hasHrmPermission, hasCrmPermission, hasModulePermission, hasExplicitModuleGrants, hasAnyModuleGrant,
    hasWarehouseAccess, hasInventoryAccess, hasOrdAccess, hasLogisticsAccess,
} = usePermissions(user, page)

const {
    isManufacturingSupervisor, supervisorRoles, activeManufacturingRole,
    supervisedDepartment, switchManufacturingRole, formatRoleLabel,
} = useManufacturingSupervisor(user)

const isDriver = computed(() => user.value?.driver !== null || user.value?.log_role === 'driver')
const isConductor = computed(() => user.value?.conductor !== null || user.value?.log_role === 'conductor')

// --- Module children below come from the shared modules/ definitions via
// buildNavItems (same as desktop), so mobile can never diverge.

// Navigation items computed
const navItems = computed(() => {
    if (isSupplier.value) {
        return [
            { label: 'Vendor Hub', href: route('supplier.dashboard'), icon: LayoutDashboard },
            { label: 'Messages', href: route('supplier.messages'), icon: MessageSquare },
            { label: 'Purchase Orders', href: route('supplier.orders'), icon: ShoppingCart },
            { label: 'My Products', href: route('supplier.products'), icon: ShoppingBag },
        ]
    }
    if (isClient.value) {
        return [
            { label: 'Dashboard', href: route('client.dashboard'), icon: LayoutDashboard },
            { label: 'Products', href: route('client.products'), icon: ShoppingBag },
            { label: 'Conversations', href: route('client.conversations'), icon: MessageSquare },
            { label: 'Orders', href: route('client.orders'), icon: ShoppingCart },
            { label: 'Order Tracking', href: route('client.tracking'), icon: Navigation },
            { label: 'Invoices', href: route('client.invoices'), icon: Receipt },
            { label: 'Receiving', href: route('client.receiving'), icon: Truck },
            { label: 'Profile', href: route('client.profile.edit'), icon: User },
            { label: 'Support', href: route('client.support'), icon: HelpCircle },
        ]
    }
    if (isEmployeePortal.value) {
        return [
            { label: 'Employee Dashboard', href: route('employee.ui.dashboard'), icon: Clock },
            { label: 'My Attendance', href: route('employee.ui.clock'), icon: CalendarDays },
            { label: 'Leave Request', href: route('employee.ui.leave'), icon: History },
            { label: 'Payslip', href: route('employee.ui.payslip'), icon: HandCoins },
        ]
    }
    if (isApplicant.value) {
        const applicantRoute = (name, fallback) => {
            try { return route(name) } catch { return fallback }
        }
        return [
            { label: 'Dashboard', href: applicantRoute('applicant.dashboard', '/applicant/dashboard'), icon: LayoutDashboard },
            { label: 'Find Jobs', href: applicantRoute('applicant.jobs.index', '/applicant/jobs'), icon: ShoppingBag },
            { label: 'My Applications', href: applicantRoute('applicant.applications.index', '/applicant/applications'), icon: FileText },
            { label: 'Interviews', href: applicantRoute('applicant.interviews', '/applicant/interviews'), icon: CalendarDays },
            { label: 'Onboarding', href: applicantRoute('applicant.onboarding.index', '/applicant/onboarding'), icon: ClipboardList },
            { label: 'Notifications', href: applicantRoute('applicant.notifications.index', '/applicant/notifications'), icon: Bell },
            { label: 'My Profile', href: applicantRoute('applicant.profile.index', '/applicant/profile'), icon: User },
            { label: 'Account', href: applicantRoute('applicant.account.index', '/applicant/account'), icon: UserCog },
        ]
    }

    const items = []
    const userRole = user.value?.role?.toUpperCase()
    const userPosition = user.value?.position?.toLowerCase()
    const isCEO = user.value?.role === 'CEO'
    const isCOO = user.value?.role === 'COO'

    if (isCEO || isCOO) {
        const isVP = isCOO || user.value?.position === 'vice_president'
        if (isVP) {
            // Vice Presidency: execution arm (operations, directives, workforce).
            // The full CRM module is appended below via buildNavItems — no
            // early return so executives keep CRM access.
            items.push({ label: 'VP Dashboard', href: route('vp.operations'), icon: LayoutDashboard })
            items.push({ label: 'Downtime Board', href: route('vp.downtime'), icon: Wrench })
            items.push({ label: 'Shift Handovers', href: route('vp.handovers'), icon: Repeat })
            items.push({ label: 'Plan vs Actual', href: route('vp.plan'), icon: ClipboardList })
            items.push({ label: 'Utilities Brief', href: route('vp.utilities'), icon: Zap })
            items.push({ label: 'Directives', href: route('vp.directives'), icon: Megaphone })
            items.push({ label: 'Bulletins', href: route('vp.bulletins'), icon: Bell })
            items.push({ label: 'Workforce Overview', href: route('vp.workforce'), icon: User })
            items.push({ label: 'Joint Approvals', href: route('ceo.approvals'), icon: Stamp })
        } else {
            items.push({ label: 'CEO Dashboard', href: route('dashboard'), icon: LayoutDashboard })
            items.push({ label: 'Inbox', href: route('ceo.inbox'), icon: Bell, badge: unreadCount.value || undefined })
            items.push({ label: 'Approvals Center', href: route('ceo.approvals'), icon: Stamp })
            items.push({ label: 'Executive Reports', href: route('ceo.reports'), icon: FileText })
            items.push({ label: 'Board Pack', href: route('ceo.board-pack'), icon: Printer })
            items.push({ label: 'Deliveries', href: route('ceo.deliveries'), icon: Truck })
            items.push({ label: 'Traceability', href: route('ceo.traceability'), icon: ScanSearch })
            items.push({ label: 'Goals & Targets', href: route('ceo.goals'), icon: Target })
            items.push({ label: 'Audit Trail', href: route('ceo.audit'), icon: History })
            items.push({ label: 'Organization Chart', href: route('ceo.access'), icon: ShieldCheck })
        }
    }

    if (user.value?.position === 'secretary') {
        items.push({ label: 'Secretary Dashboard', href: route('secretary.dashboard'), icon: LayoutDashboard })
        items.push({ label: 'Documents', href: route('secretary.documents'), icon: FileText })
        items.push({ label: 'Meetings', href: route('secretary.meetings'), icon: CalendarDays })
        items.push({ label: 'Memos', href: route('secretary.memos'), icon: Megaphone })
    }

    if (userRole === 'LOG' && userPosition === 'staff') {
        if (isDriver.value) {
            items.push({ label: 'My Deliveries', href: route('logistics.driver.portal'), icon: Truck })
        } else if (isConductor.value) {
            items.push({ label: 'My Trips', href: route('logistics.conductor.portal'), icon: Navigation })
        }
        return items
    }

    if (userPosition === 'trainee') {
        items.push(
            { label: 'Time In/Out', href: route('trainee.timekeeping'), icon: Clock },
            { label: 'Attendance', href: route('trainee.attendance'), icon: CalendarDays },
            { label: 'Payslips', href: route('trainee.payslip'), icon: HandCoins }
        )
        return items
    }

    // ─── MODULE ITEMS — shared definitions, same as desktop ──────────────
    // buildNavItems reads the modules/*.vue registry with this ctx, so every
    // page desktop shows (respecting the same permission rules) shows here.
    const ctx = {
        route,
        user: user.value,
        isCEO,
        isSecretaryOrGM: user.value?.position === 'secretary' || user.value?.position === 'special_officer',
        userPosition,
        grantedModules: grantedModules.value,
        canAccessModule,
        canAccessWorkforce,
        rootModule: rootModule.value,
        hasExplicitModuleGrants,
        hasAnyModuleGrant,
        hasModulePermission,
        hasHrmPermission,
        hasCrmPermission,
        hasWorkforcePermission,
        hasWarehouseAccess: hasWarehouseAccess.value,
        hasInventoryAccess: hasInventoryAccess.value,
        hasOrdAccess: hasOrdAccess.value,
        hasLogisticsAccess: hasLogisticsAccess.value,
        isManufacturingSupervisor: isManufacturingSupervisor.value,
        supervisedDepartment: supervisedDepartment.value,
        qualityChecker: { isOpen: qualityCheckerDropdown.isOpen.value, toggle: qualityCheckerDropdown.toggle },
        roleDropdowns,
        dropdowns: moduleDropdowns,
    }

    items.push(...buildNavItems(ctx))

    return items
})

const isActive = (href) => href !== '#' && (currentUrl.value === href || currentUrl.value.startsWith(href + '/'))
const handleNavClick = () => { isOpen.value = false }

// Close the drawer on every navigation (covers programmatic visits too)
// and lock body scroll while it is open.
watch(() => page.url, () => { isOpen.value = false })
watch(isOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
})

const displayName = computed(() => {
    if (isSupplier.value) return supplier.value?.representative_name
    if (isClient.value) return client.value?.company_name
    if (isApplicant.value) return applicantUser.value?.first_name ? `${applicantUser.value.first_name} ${applicantUser.value.last_name || ''}`.trim() : applicantUser.value?.email
    return user.value?.name
})
const displayInitial = computed(() => displayName.value?.charAt(0) ?? '?')
const userPhotoUrl = computed(() => {
    if (user.value?.profile_photo_path) return `/storage/${user.value.profile_photo_path}`
    if (supplier.value?.profile_photo_path) return `/storage/${supplier.value.profile_photo_path}`
    if (client.value?.profile_photo_path) return `/storage/${client.value.profile_photo_path}`
    return null
})
const displayDepartment = computed(() => {
    if (isApplicant.value) return 'Applicant'
    if (isSupplier.value) return 'Supplier'
    if (isClient.value) return client.value?.business_type
    return user.value?.role
})
const displayPosition = computed(() => {
    if (isApplicant.value) return applicantUser.value?.status || 'Applicant'
    if (isSupplier.value) return supplier.value?.business_name ?? 'Vendor'
    if (isEmployeePortal.value) return user.value?.employee_id ?? 'Staff'
    if (isClient.value) return 'Partner'
    if (user.value?.is_manufacturing_supervisor) return 'Supervisor'
    return user.value?.position
})
const sidebarLabel = computed(() => {
    if (isApplicant.value) return 'Applicant'
    if (isSupplier.value) return 'Vendor'
    if (isClient.value) return 'Partner'
    if (isEmployeePortal.value) return 'Employee'
    return 'System'
})
</script>

<template>
    <div class="md:hidden">
        <nav
            class="fixed top-0 left-0 right-0 z-[60] bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border-b border-gray-200/50 dark:border-gray-800/50 shadow-sm h-16 flex items-center justify-between px-4 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <div class="animate-logo h-8 w-8 rounded-xl flex items-center justify-center shadow-lg bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 shadow-indigo-500/30">
                    <img src="/images/applogo.png" alt="Logo" class="h-4.5 w-4.5 object-contain brightness-0 invert" />
                </div>
                <div class="flex flex-col">
                    <span
                        class="text-[14px] font-black tracking-tight text-gray-900 dark:text-white uppercase leading-none">
                        Monti <span class="text-indigo-700">Textile</span>
                    </span>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">
                        {{ sidebarLabel }}
                    </span>
                </div>
            </div>
            <button @click.stop="isOpen = !isOpen"
                class="p-2 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                <Menu v-if="!isOpen" class="h-6 w-6" />
                <X v-else class="h-6 w-6" />
            </button>
        </nav>

        <div v-show="isOpen"
            class="fixed inset-0 z-[50] bg-black/40 backdrop-blur-sm transition-opacity duration-300"
            @click="isOpen = false"
            aria-hidden="true"></div>

        <div v-show="isOpen"
            :class="[
                'fixed inset-y-0 left-0 z-[55] w-[80vw] max-w-sm bg-white/90 dark:bg-gray-950/90 backdrop-blur-xl flex flex-col shadow-2xl h-full pt-16 border-r border-gray-200/50 dark:border-gray-800/50 transition-transform duration-300 ease-out',
                isOpen ? 'translate-x-0' : '-translate-x-full'
            ]">
            <div class="flex-1 flex flex-col overflow-y-auto px-3 py-6 custom-scrollbar">
                <div class="mb-4 px-2">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.15em]">Main Menu</p>
                </div>
                <nav class="space-y-1">
                    <template v-for="item in navItems" :key="item.label || item.isHeading">
                        <!-- Heading -->
                        <div v-if="item.isHeading" class="mb-3 px-2 pt-4 first:pt-0">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.15em]">{{ item.label }}</p>
                        </div>

                        <!-- Dropdown (level 1) -->
                        <div v-else-if="item.isDropdown" class="space-y-1">
                            <button @click="item.toggle" :class="[
                                item.isOpen ? 'text-indigo-700 bg-indigo-50 dark:bg-indigo-900/30' : 'text-gray-500 dark:text-gray-400',
                                'w-full flex items-center justify-between px-3 py-3.5 text-[14px] font-bold rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all duration-300'
                            ]">
                                <div class="flex items-center">
                                    <div :class="[item.isOpen ? 'bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 text-white shadow-md shadow-indigo-500/25' : 'text-gray-400']"
                                        class="p-2 rounded-xl mr-3 transition-all duration-300">
                                        <component :is="item.icon" class="h-5 w-5" />
                                    </div>
                                    <span class="truncate tracking-tight">{{ item.label }}</span>
                                </div>
                                <ChevronRight
                                    :class="['h-4 w-4 transition-transform duration-300', item.isOpen ? 'rotate-90' : 'text-gray-400']" />
                            </button>

                            <!-- Children (may contain further dropdowns or links) -->
                            <div v-show="item.isOpen" class="pl-6 space-y-1 mt-1 transition-all">
                                <template v-for="subItem in item.children" :key="subItem.label">
                                    <!-- Sub-dropdown (level 2) -->
                                    <div v-if="subItem.isDropdown" class="space-y-1">
                                        <button @click="subItem.toggle" :class="[
                                            subItem.isOpen ? 'text-indigo-700 bg-indigo-50 dark:bg-indigo-900/30' : 'text-gray-500 dark:text-gray-400',
                                            'w-full flex items-center justify-between px-3 py-3 text-[13px] font-bold rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all duration-300'
                                        ]">
                                            <div class="flex items-center">
                                                <div :class="[subItem.isOpen ? 'bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 text-white shadow-md shadow-indigo-500/25' : 'text-gray-400']"
                                                    class="p-1.5 rounded-lg mr-2 transition-all duration-300">
                                                    <component :is="subItem.icon" class="h-4 w-4" />
                                                </div>
                                                <span class="truncate tracking-tight">{{ subItem.label }}</span>
                                            </div>
                                            <ChevronRight
                                                :class="['h-3.5 w-3.5 transition-transform duration-300', subItem.isOpen ? 'rotate-90' : 'text-gray-400']" />
                                        </button>
                                        <div v-show="subItem.isOpen" class="pl-8 space-y-1">
                                            <Link v-for="link in subItem.children" :key="link.label"
                                                :href="link.href" @click="handleNavClick"
                                                :class="[isActive(link.href) ? 'text-indigo-700' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white']"
                                                class="flex items-center py-2 text-[12px] font-medium transition-colors">
                                                <component :is="link.icon" class="h-3.5 w-3.5 mr-2" />
                                                {{ link.label }}
                                            </Link>
                                        </div>
                                    </div>
                                    <!-- Divider -->
                                    <div v-else-if="subItem.isDivider" class="text-[10px] text-gray-400 py-1 px-2">{{ subItem.label }}</div>
                                    <!-- Direct link (level 2) -->
                                    <Link v-else :href="subItem.href" @click="handleNavClick"
                                        :class="[isActive(subItem.href) ? 'text-indigo-700' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white']"
                                        class="flex items-center py-2.5 text-[13px] font-bold transition-colors">
                                        <component :is="subItem.icon" class="h-4 w-4 mr-3" />
                                        {{ subItem.label }}
                                    </Link>
                                </template>
                            </div>
                        </div>

                        <!-- Direct link -->
                        <Link v-else :href="item.href" @click="handleNavClick" :class="[
                            isActive(item.href)
                                ? 'bg-gradient-to-r from-blue-700 via-indigo-700 to-violet-800 text-white shadow-lg shadow-indigo-500/25'
                                : 'text-gray-500 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-gray-900 dark:hover:text-white'
                        ]" class="group relative flex items-center justify-between px-3 py-3 text-[14px] font-bold rounded-xl transition-all duration-300">
                            <div v-if="isActive(item.href)"
                                class="absolute left-0 top-1/4 bottom-1/4 w-1 rounded-r-full bg-white"></div>
                            <div class="flex items-center relative z-10">
                                <div :class="[
                                    isActive(item.href)
                                        ? 'bg-white/20 text-white'
                                        : 'text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-300'
                                ]" class="p-2 rounded-lg transition-colors duration-300 mr-3">
                                    <component :is="item.icon" class="h-5 w-5 flex-shrink-0" />
                                </div>
                                <span class="truncate tracking-tight">{{ item.label }}</span>
                                <span v-if="item.badge" class="ml-2 min-w-[20px] h-5 px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-black inline-flex items-center justify-center">{{ item.badge > 99 ? '99+' : item.badge }}</span>
                            </div>
                        </Link>
                    </template>
                    <!-- Empty state: every page is still 'disabled' (IT default) -->
                    <div v-if="navItems.length === 0" class="px-3 py-8 text-center">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center mb-3">
                            <ShieldCheck class="w-6 h-6 text-indigo-500" />
                        </div>
                        <p class="text-xs font-bold text-gray-900 dark:text-white mb-1">No pages assigned yet</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">
                            All of your page permissions are still disabled.<br />
                            Please wait for the admins to grant you access.
                        </p>
                    </div>
                </nav>
            </div>

            <div class="p-4 mt-auto border-t border-gray-100 dark:border-gray-800">
                <div
                    class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md rounded-2xl p-3 border border-gray-100/50 dark:border-gray-800/50 shadow-lg">
                    <div class="flex items-center gap-3 relative z-10">
                        <div class="relative flex-shrink-0">
                            <img v-if="userPhotoUrl" :src="userPhotoUrl" alt="Profile"
                                class="h-10 w-10 rounded-xl object-cover shadow-lg shadow-indigo-500/30" />
                            <div v-else
                                class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 flex items-center justify-center text-white text-sm font-black shadow-lg shadow-indigo-500/30 uppercase">
                                {{ displayInitial }}
                            </div>
                            <div
                                class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 bg-green-500 border-2 border-white dark:border-gray-900 rounded-full">
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p
                                class="text-xs font-black text-gray-900 dark:text-white truncate uppercase tracking-tighter">
                                {{ displayName }}
                            </p>
                            <div class="flex items-center gap-1 mt-0.5 mb-1">
                                <Building2 class="h-3 w-3 text-gray-400" />
                                <span class="text-indigo-700 text-[9px] font-black uppercase truncate">
                                    {{ displayDepartment }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <ShieldCheck class="h-3 w-3 text-indigo-600" />
                                <span class="text-[9px] font-black text-gray-400 uppercase truncate">
                                    {{ displayPosition }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="isManufacturingSupervisor && supervisorRoles.length > 0"
                        class="mt-3 pt-3 border-t border-gray-200/50 dark:border-gray-700/50">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                            <UserCog2 class="w-3 h-3" /> SWITCH ROLE
                        </p>
                        <div class="space-y-1">
                            <button v-for="role in supervisorRoles" :key="role.manufacturing_role"
                                @click="switchManufacturingRole(role.manufacturing_role)"
                                :class="[
                                    activeManufacturingRole === role.manufacturing_role
                                        ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 ring-1 ring-indigo-500/30'
                                        : 'bg-gray-100/70 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20'
                                ]"
                                class="w-full text-left text-[11px] font-medium px-2 py-1.5 rounded-lg transition-all">
                                {{ formatRoleLabel(role.manufacturing_role) }}
                                <span v-if="activeManufacturingRole === role.manufacturing_role"
                                    class="float-right text-indigo-600">✓</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-logo {
    animation: logoFloat 4.5s ease-in-out infinite, logoGlow 3.2s ease-in-out infinite;
    transition: transform 0.3s ease;
}
.animate-logo:hover {
    animation-play-state: paused;
    transform: scale(1.12) rotate(-8deg);
}
@keyframes logoFloat {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    25% { transform: translateY(-2px) rotate(-4deg); }
    50% { transform: translateY(1px) rotate(0deg); }
    75% { transform: translateY(-1px) rotate(4deg); }
}
@keyframes logoGlow {
    0%, 100% { box-shadow: 0 8px 20px -6px rgba(99, 102, 241, 0.45); }
    50% { box-shadow: 0 8px 30px -4px rgba(139, 92, 246, 0.7); }
}
.custom-scrollbar::-webkit-scrollbar {
    width: 3px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.2);
    border-radius: 10px;
}
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
.modal-fade-enter-active .bg-white,
.modal-fade-leave-active .bg-white {
    transition: transform 0.2s ease;
}
.modal-fade-enter-from .bg-white,
.modal-fade-leave-to .bg-white {
    transform: scale(0.95) translateY(10px);
}
</style>
