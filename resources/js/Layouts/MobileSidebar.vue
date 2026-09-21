<script setup>
import { usePage, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js';
import { computed, ref, onMounted } from 'vue'
import {
    Menu, X, LayoutDashboard, BarChart3, Package, LogOut, ChevronRight,
    CreditCard, UserPlus, Spool, ClipboardList, ChartNoAxesCombined,
    ShoppingBasket, HandCoins, FileUser, DoorOpen, BicepsFlexed, Truck,
    Wallet, Factory, Book, Boxes, ShoppingCart, Warehouse, Globe, Clock,
    CalendarDays, History, Users, Settings, Receipt, HelpCircle, ShieldCheck,
    Building2, RefreshCw, ClipboardCheck, FileText, Send, ShoppingBag, User,
    TrendingUp, XCircle, Eye, Award, Archive, CalendarCheck, UserX, AlertCircle,
    UserCog, MessageSquare, Navigation, MapPin, Briefcase, Plus, ArrowLeft,
    Paperclip, Loader2, Info, Phone, Mail, Calendar, Tag, Weight, Ruler, Layers,
    ArrowRight, Zap, Activity, DollarSign, Users as UsersIcon, UserCog2, Camera,
    Sparkles, Palette, Wrench, CheckCircle2, UserPen, Share2, Megaphone, Cog, Flag, FlaskConical, ArrowRightLeft, Leaf, HardHat, Flame, Stamp, Bell, Printer, Target, Repeat, CalendarClock, PackageCheck, ScanSearch
} from 'lucide-vue-next'

const page = usePage()

// UI State
const isOpen = ref(false)
const showLogoutModal = ref(false)

// Dropdown states
const isWorkforceSubOpen = ref(false)
const isHrmOpen = ref(false)
const isCrmOpen = ref(false)
const isEcoOpen = ref(false)
const isScmOpen = ref(false)
const isWarehouseOpen = ref(false)
const isInventoryOpen = ref(false)
const isProOpen = ref(false)
const isFinOpen = ref(false)
const isItOpen = ref(false)
const isManOpen = ref(false)
const isOrdOpen = ref(false)
const isLogisticsOpen = ref(false)

// For dynamically created role dropdowns inside MAN
const roleDropdownStates = ref({})

const toggleRoleDropdown = (roleKey) => {
    roleDropdownStates.value[roleKey] = !roleDropdownStates.value[roleKey]
}

const getRoleDropdownState = (roleKey, defaultState = false) => {
    if (roleDropdownStates.value[roleKey] === undefined) {
        roleDropdownStates.value[roleKey] = defaultState
    }
    return roleDropdownStates.value[roleKey]
}

const toggleWorkforceSub = () => { isWorkforceSubOpen.value = !isWorkforceSubOpen.value }
const toggleHrm = () => { isHrmOpen.value = !isHrmOpen.value }
const toggleCrm = () => { isCrmOpen.value = !isCrmOpen.value }
const toggleEco = () => { isEcoOpen.value = !isEcoOpen.value }
const toggleScm = () => { isScmOpen.value = !isScmOpen.value }
const toggleWarehouse = () => { isWarehouseOpen.value = !isWarehouseOpen.value }
const toggleInventory = () => { isInventoryOpen.value = !isInventoryOpen.value }
const togglePro = () => { isProOpen.value = !isProOpen.value }
const toggleFin = () => { isFinOpen.value = !isFinOpen.value }
const toggleIt = () => { isItOpen.value = !isItOpen.value }
const toggleMan = () => { isManOpen.value = !isManOpen.value }
const toggleOrd = () => { isOrdOpen.value = !isOrdOpen.value }
const toggleLogistics = () => { isLogisticsOpen.value = !isLogisticsOpen.value }

// Auth helpers
const user = computed(() => page.props.auth.user)
const unreadCount = computed(() => page.props.notifications_unread || 0)
const client = computed(() => page.props.auth.client)
const supplier = computed(() => page.props.auth.supplier || (page.props.auth.user?.business_name ? page.props.auth.user : null))
const currentUrl = computed(() => page.url)

const isEmployeePortal = computed(() => currentUrl.value.startsWith('/dashboard/employee-ui'))
const isClient = computed(() => !!client.value)
const isSupplier = computed(() => !!supplier.value || currentUrl.value.startsWith('/supplier'))

const userModuleAccess = computed(() => {
    if (user.value?.position === 'secretary' || user.value?.position === 'special_officer') {
        return page.props.auth.user?.granted_modules || []
    }
    return []
})

const grantedModules = computed(() => {
    if (user.value?.is_manufacturing_supervisor) {
        return page.props.auth.user?.granted_modules || []
    }
    return []
})

const canAccessModule = (moduleName) => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'special_officer') {
        return userModuleAccess.value.includes(moduleName)
    }
    if (user.value?.is_manufacturing_supervisor) {
        if (moduleName === 'MAN') return true
        return grantedModules.value.includes(moduleName)
    }
    if (moduleName === 'MAN' && user.value?.manufacturing_role) {
        return true
    }
    return user.value?.role === moduleName
}

const hasLogisticsAccess = computed(() => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'special_officer') {
        return canAccessModule('LOG')
    }
    if (user.value?.is_manufacturing_supervisor) {
        return grantedModules.value.includes('LOG')
    }
    if (user.value?.role === 'LOG' && user.value?.position === 'manager') return true
    return user.value?.logistics_access === true
})

const isDriver = computed(() => user.value?.driver !== null || user.value?.log_role === 'driver')
const isConductor = computed(() => user.value?.conductor !== null || user.value?.log_role === 'conductor')

const canAccessWorkforce = () => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'special_officer') {
        return userModuleAccess.value.includes('WRF')
    }
    if (user.value?.is_manufacturing_supervisor) {
        return grantedModules.value.includes('WRF')
    }
    const perms = user.value?.workforce_permissions
    return perms && perms.length > 0
}

const hasWorkforcePermission = (pageName) => {
    if (user.value?.role === 'CEO') return true
    const perms = user.value?.workforce_permissions
    if (!perms) return false
    return perms.includes(pageName)
}

const hasPagePermission = (moduleKey, pageKey) => {
    if (user.value?.role === 'CEO') return true
    const perms = user.value?.page_permissions || page.props.auth?.page_permissions || []
    return perms.some(p => p.module === moduleKey && p.page === pageKey)
}

const hasHrmPermission = (pageKey) => hasPagePermission('HRM', pageKey)
const hasCrmPermission = (pageKey) => hasPagePermission('CRM', pageKey)

// Home module of a secretary / special officer (shared by the backend,
// mirroring CheckPagePermission::getRootModuleForUser). Auto-full access
// applies to this module only — extra granted modules filter per page.
const rootModule = computed(() => {
    const r = user.value?.root_module
    return r ? String(r).toUpperCase() : null
})

// Any usable grant in a module (shared list already excludes 'disabled').
// Lets extra-module grants display their section, filtered per page below.
const hasAnyModuleGrant = (moduleKey) => {
    const perms = user.value?.page_permissions || page.props.auth?.page_permissions || []
    return perms.some(p => String(p.module || '').toUpperCase() === String(moduleKey).toUpperCase())
}

// Modules with ANY explicit PagePermission row (including all-'disabled'
// ones). Explicit rows are the exact access set and override the native
// manager shortcut — mirrors backend CheckPagePermission.
const hasExplicitModuleGrants = (moduleKey) => {
    const mods = user.value?.explicit_modules || []
    return mods.some(m => String(m).toUpperCase() === String(moduleKey).toUpperCase())
}
const hasModulePermission = (moduleKey, permissionKey) => {
    if (user.value?.role === 'CEO') return true
    const modulePerms = user.value?.permissions?.[moduleKey]
    return modulePerms ? modulePerms.includes(permissionKey) : false
}

const hasWarehouseAccess = computed(() => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'special_officer') {
        return canAccessModule('WAR')
    }
    if (user.value?.is_manufacturing_supervisor) {
        return grantedModules.value.includes('WAR')
    }
    return user.value?.has_warehouse_access === true
})

const hasInventoryAccess = computed(() => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'special_officer') {
        return canAccessModule('INV')
    }
    if (user.value?.is_manufacturing_supervisor) {
        return grantedModules.value.includes('INV')
    }
    return user.value?.has_inventory_access === true
})

const hasOrdAccess = computed(() => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'special_officer') {
        return canAccessModule('ORD')
    }
    if (user.value?.is_manufacturing_supervisor) {
        return grantedModules.value.includes('ORD')
    }
    return user.value?.has_ord_access === true
})

const isManufacturingSupervisor = computed(() => user.value?.is_manufacturing_supervisor === true)
const supervisorRoles = computed(() => user.value?.supervisor_roles || [])
const activeManufacturingRole = computed(() => user.value?.active_manufacturing_role || null)
const supervisedDepartment = computed(() => user.value?.supervisor_department || null)

const switchManufacturingRole = (role) => {
    router.post(route('man.supervisor.switch'), { role }, {
        preserveScroll: true,
        onSuccess: () => window.location.reload()
    })
}
const formatRoleLabel = (role) => role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())

// Canonical route slugs per manufacturing_role (see routes/Man.php).
// pollution_control_operator uses the shorter `pollution-control` prefix.
const ROLE_ROUTE_SLUGS = {
    pollution_control_operator: 'pollution-control',
}
const roleToSlug = (roleWithUnderscores) => ROLE_ROUTE_SLUGS[roleWithUnderscores] ?? roleWithUnderscores.replace(/_/g, '-')

// Helper to generate three links for a role (as an array of link objects)
const getRoleLinks = (roleWithUnderscores, label, icon, hasReports = true, hasHistory = false, hasFlags = false, hasLab = false) => {
    const roleWithHyphens = roleToSlug(roleWithUnderscores)
    const routePrefix = `man.staff.${roleWithHyphens}`
    const links = [
        { label: 'Dashboard', href: route(`${routePrefix}.dashboard`), icon: LayoutDashboard },
        { label: label, href: route(`${routePrefix}.page`), icon: icon }
    ]
    if (hasHistory) {
        links.push({ label: 'My History', href: route(`${routePrefix}.history`), icon: History })
    }
    if (hasFlags) {
        links.push({ label: 'Flag Reports', href: route(`${routePrefix}.flag-reports`), icon: Flag })
    }
    if (hasLab) {
        links.push({ label: 'Tests & CoA', href: route(`${routePrefix}.tests`), icon: ClipboardCheck })
        links.push({ label: 'Lab Inventory', href: route(`${routePrefix}.inventory`), icon: Boxes })
        links.push({ label: 'Bulk Transfer', href: route(`${routePrefix}.transfer`), icon: ArrowRightLeft })
    }
    if (hasReports) {
        links.push({ label: 'Reports', href: route(`${routePrefix}.reports`), icon: FileText })
    }
    return links
}

// Helper to create a dropdown item for a role (used inside MAN module)
const createRoleDropdown = (roleKey, roleLabel, roleIcon, hasReports = true, hasHistory = false, hasFlags = false, hasLab = false) => {
    const isOpen = getRoleDropdownState(roleKey, false)
    return {
        label: roleLabel,
        icon: roleIcon,
        isDropdown: true,
        isOpen: isOpen,
        toggle: () => toggleRoleDropdown(roleKey),
        children: getRoleLinks(roleKey, roleLabel, roleIcon, hasReports, hasHistory, hasFlags, hasLab)
    }
}

// Department staff roles for supervisor view (returns array of dropdown items)
// 4 departments — no manufacturing manager. hasHistory mirrors the backend:
// only the 6 production roles expose a `history` route.
const MAN_STAFF_CONFIG = {
    knitting_yarn:          { label: 'Knitting Yarn',          icon: Sparkles, hasReports: true,  hasHistory: true,  hasFlags: true },
    knitting_mechanic:      { label: 'Knitting Mechanic',      icon: Cog,      hasReports: true,  hasHistory: true },
    dyeing_color:           { label: 'Dyeing Color',           icon: Palette,  hasReports: true,  hasHistory: true },
    dyeing_fabric_softener: { label: 'Dyeing Fabric Softener', icon: Palette,  hasReports: true,  hasHistory: true },
    dyeing_squeezer:        { label: 'Dyeing Squeezer',        icon: Palette,  hasReports: true,  hasHistory: true },
    dyeing_ironing:         { label: 'Dyeing Ironing',         icon: Palette,  hasReports: true,  hasHistory: true },
    dyeing_packaging:       { label: 'Dyeing Packaging',       icon: Palette,  hasReports: true,  hasHistory: true },
    dyeing_lab_chemist:     { label: 'Lab Chemist',            icon: FlaskConical, hasReports: false, hasHistory: true, hasLab: true },
    checker_quality:        { label: 'Checker Quality',        icon: CheckCircle2, hasReports: false, hasHistory: false },
    maintenance_checker:    { label: 'Maintenance Checker',    icon: Wrench,   hasReports: true,  hasHistory: false },
    pollution_control_operator: { label: 'Pollution Control', icon: Leaf,     hasReports: false, hasHistory: true },
    safety_officer:         { label: 'Safety Officer',         icon: HardHat,  hasReports: false, hasHistory: true },
    boiler_operator:        { label: 'Boiler Operator',        icon: Flame,    hasReports: true,  hasHistory: true },
}

const DEPARTMENT_ROLES = {
    knitting: ['knitting_yarn', 'knitting_mechanic'],
    dyeing: ['dyeing_color', 'dyeing_fabric_softener', 'dyeing_squeezer', 'dyeing_ironing', 'dyeing_packaging', 'dyeing_lab_chemist'],
    finishing: ['checker_quality'],
    maintenance: ['maintenance_checker', 'pollution_control_operator', 'safety_officer'],
    boiler: ['boiler_operator'],
}

// Department staff roles for supervisor view (returns array of dropdown items)
const departmentStaffRoles = computed(() => {
    const dept = supervisedDepartment.value
    return (DEPARTMENT_ROLES[dept] || []).map((roleKey) => {
        const config = MAN_STAFF_CONFIG[roleKey]
        return createRoleDropdown(roleKey, config.label, config.icon, config.hasReports, config.hasHistory, config.hasFlags, config.hasLab)
    })
})

// --- Helper to get filtered children for each module
const getFilteredHrmChildren = () => {
    const all = [
        { label: 'Dashboard', href: route('hrm.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
        { label: 'Employees', href: route('hrm.employees.index'), icon: Users, permKey: 'employee' },
        { label: 'Applications', href: route('hrm.applications.index'), icon: FileText, permKey: 'application' },
        { label: 'Interviews', href: route('hrm.interview.index'), icon: Eye, permKey: 'interview' },
        { label: 'Trainees', href: route('hrm.trainee.index'), icon: Award, permKey: 'trainee' },
        { label: 'Onboarding', href: route('hrm.onboarding.index'), icon: UserPlus, permKey: 'onboarding' },
        { label: 'Archive', href: route('hrm.applications.rejected'), icon: Archive, permKey: 'application' },
        { label: 'Payroll', href: route('hrm.payroll'), icon: HandCoins, permKey: 'payroll' },
        { label: 'Analytics', href: route('hrm.analytics'), icon: ChartNoAxesCombined, permKey: 'analytics' },
    ]
    if (user.value?.role === 'CEO') return all
    if (user.value?.position === 'manager' && user.value?.role === 'HRM' && !hasExplicitModuleGrants('HRM')) return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'HRM') return all
    // No supervisor shortcut — per-page filter below governs (backend
    // grants the module shell but each page needs an explicit row).
    return all.filter(child => hasHrmPermission(child.permKey))
}

const getFilteredCrmChildren = () => {
    const all = [
        { label: 'Dashboard', href: route('crm.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
        { label: 'Leads', href: route('crm.lead'), icon: FileUser, permKey: 'leads' },
        { label: 'Approvals', href: route('crm.approval.index'), icon: ClipboardCheck, permKey: 'approvals' },
        { label: 'Customer Profiles', href: route('crm.customerprofile.index'), icon: Users, permKey: 'customer_profiles' },
        { label: 'Investigation', href: route('crm.investigation.index'), icon: AlertCircle, permKey: 'investigation' },
        { label: 'Socials', href: route('crm.socials.index'), icon: Share2, permKey: 'socials' },
    ]
    if (user.value?.role === 'CEO') return all
    if (user.value?.position === 'manager' && user.value?.role === 'CRM' && !hasExplicitModuleGrants('CRM')) return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'CRM') return all
    // No supervisor shortcut — per-page filter below governs (backend
    // grants the module shell but each page needs an explicit row).
    return all.filter(child => hasCrmPermission(child.permKey))
}

const getFilteredWorkforceChildren = () => {
    const all = [
        { label: 'Dashboard', href: route('workforce.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
        { label: 'Scheduler', href: route('workforce.scheduler'), icon: CalendarCheck, permKey: 'scheduler' },
        { label: 'Leave Requests', href: route('workforce.leave'), icon: FileText, permKey: 'leave' },
        { label: 'Absence Tracking', href: route('workforce.absent'), icon: UserX, permKey: 'absent' },
    ]
    if (user.value?.role === 'CEO') return all
    if (user.value?.position === 'manager' && user.value?.role === 'WRF') return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && canAccessModule('WRF')) return all
    if (isManufacturingSupervisor.value && grantedModules.value.includes('WRF')) return all
    return all.filter(child => hasWorkforcePermission(child.permKey))
}

const getFilteredManChildren = () => {
    // Mirrors SidebarMan.vue (desktop): every MAN page needs a usable
    // (view/edit) grant. All-'disabled' (IT default) yields [] → the module
    // is skipped → empty sidebar + AwaitingAccess landing page.
    const isCEOUser = user.value?.role === 'CEO'
    const isRootManSecretary = (user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'MAN'
    const can = (permKey) => hasModulePermission('MAN', permKey)
    const hasStaffEntry = can('production') || can('dashboard')
    // Quality Checker is its own Finishing department: the Finishing
    // supervisor reaches it via their Department Staff dropdown below,
    // so the overview entry is shown only to CEO / root-MAN secretary / GM
    // holding a usable production grant (its routes require production,view).
    const showQualityChecker = isCEOUser || (isRootManSecretary && hasStaffEntry)
    const managerChildren = [
        { label: 'Dashboard', href: route('man.manager.dashboard'), icon: Factory, permKey: 'dashboard' },
        { label: 'Production Orders', href: route('man.manager.production'), icon: ClipboardList, permKey: 'production' },
        { label: 'Rejected Items', href: route('man.manager.rejected'), icon: XCircle, permKey: 'reject' },
    ]
    if (showQualityChecker) {
        managerChildren.push(
            { label: 'Quality Checker Dashboard', href: route('man.staff.checker-quality.dashboard'), icon: CheckCircle2, permKey: 'production' },
            { label: 'Quality Checker Production', href: route('man.staff.checker-quality.production'), icon: ClipboardList, permKey: 'production' },
        )
    }
    // NOTE: no manufacturing manager exists — legacy MAN-manager accounts
    // fall through to an empty menu (their routes return 403; reassign them
    // as department supervisors). Mirrors desktop SidebarMan.vue.
    if (isCEOUser || isRootManSecretary) {
        return managerChildren
    }
    // Non-root secretary / GM holding a MAN grant: exact per-page set only.
    if (user.value?.position === 'secretary' || user.value?.position === 'special_officer') {
        return managerChildren.filter(child => !child.permKey || can(child.permKey))
    }
    if (isManufacturingSupervisor.value) {
        const children = managerChildren.filter(child => !child.permKey || can(child.permKey))
        if (supervisedDepartment.value && hasStaffEntry) {
            const staffDropdowns = departmentStaffRoles.value
            if (staffDropdowns.length) {
                children.push({ isDivider: true, label: '── Department Staff ──' })
                children.push(...staffDropdowns)
            }
        }
        return children
    }
    // Regular staff: role pages require production (or dashboard) — without
    // a usable grant the menu stays empty so this module is hidden.
    if (!hasStaffEntry) {
        return []
    }
    {
        const manufacturingRole = user.value?.manufacturing_role
        const config = MAN_STAFF_CONFIG[manufacturingRole]
        if (config) {
            return [createRoleDropdown(manufacturingRole, config.label, config.icon, config.hasReports, config.hasHistory, config.hasFlags, config.hasLab)]
        }
    }
    return []
}

const getFilteredLogisticsChildren = () => {
    const all = [
        { label: 'Dashboard', href: route('logistics.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
        { label: 'Load', href: route('logistics.load.index'), icon: Package, permKey: 'load' },
        { label: 'Dispatch', href: route('logistics.dispatch.index'), icon: Send, permKey: 'dispatch' },
        { label: 'Fleet', href: route('logistics.fleet.index'), icon: Truck, permKey: 'fleet' },
        { label: 'Drivers', href: route('logistics.drivers.index'), icon: Users, permKey: 'drivers' },
        { label: 'Routes', href: route('logistics.routes'), icon: Navigation, permKey: 'routes' },
        { label: 'Proof', href: route('logistics.proof.index'), icon: Camera, permKey: 'proof' },
        { label: 'Reports', href: route('logistics.reports.index'), icon: FileText, permKey: 'reports' },
    ]
    if (user.value?.role === 'CEO') {
        return all
    }
    if (user.value?.position === 'manager' && user.value?.role === 'LOG' && !hasExplicitModuleGrants('LOG')) return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'LOG') return all
    // No supervisor shortcut — per-page filter below governs (backend
    // grants the module shell but each page needs an explicit row).
    // No fallback: all-'disabled' (the IT default) yields an empty list.
    return all.filter(child => hasModulePermission('LOG', child.permKey))
}

const getFilteredEcoChildren = () => {
    const all = [
        { label: 'Dashboard', href: route('eco.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
        { label: 'Store', href: route('eco.store'), icon: ShoppingBag, permKey: 'store' },
        { label: 'Inquiries', href: route('eco.inquiries'), icon: MessageSquare, permKey: 'inquiry' },
        { label: 'Suppliers', href: route('eco.suppliers'), icon: UsersIcon, permKey: 'supplier' },
        { label: 'Credit', href: route('eco.credit'), icon: CreditCard, permKey: 'credit' },
        { label: 'Push Center', href: route('eco.push'), icon: Send, permKey: 'push' },
    ]
    if (user.value?.role === 'CEO') return all
    if (user.value?.position === 'manager' && user.value?.role === 'ECO' && !hasExplicitModuleGrants('ECO')) return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'ECO') return all
    // No supervisor shortcut — per-page filter below governs (backend
    // grants the module shell but each page needs an explicit row).
    return all.filter(child => hasModulePermission('ECO', child.permKey))
}

const getFilteredOrdChildren = () => {
    const all = [
        { label: 'Orders', href: route('ord.orders'), icon: ClipboardList, permKey: 'orders' },
        { label: 'Productions', href: route('ord.productions'), icon: Factory, permKey: 'productions' },
        { label: 'Delivery', href: route('ord.delivery'), icon: Truck, permKey: 'delivery' },
    ]
    if (user.value?.role === 'CEO') {
        return all
    }
    if (user.value?.position === 'manager' && user.value?.role === 'ORD' && !hasExplicitModuleGrants('ORD')) return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'ORD') return all
    // No supervisor shortcut — per-page filter below governs (backend
    // grants the module shell but each page needs an explicit row).
    return all.filter(child => hasModulePermission('ORD', child.permKey))
}

const getFilteredScmChildren = () => {
    const all = [
        { label: 'Dashboard', href: route('scm.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
        { label: 'Sales Orders', href: route('scm.sales-orders'), icon: ShoppingCart, permKey: 'sales' },
        { label: 'Procurement Orders', href: route('scm.procurement-orders'), icon: ClipboardList, permKey: 'procurement' },
        { label: 'Planning', href: route('scm.planning'), icon: CalendarClock, permKey: 'planning' },
        { label: 'Purchase Orders', href: route('scm.purchase-orders'), icon: PackageCheck, permKey: 'purchase' },
        { label: 'Deliveries', href: route('scm.deliveries'), icon: ClipboardCheck, permKey: 'deliveries' },
        { label: 'Vendors', href: route('scm.vendors'), icon: Building2, permKey: 'vendor' },
        { label: 'Analytics', href: route('scm.analytics'), icon: BarChart3, permKey: 'analytics' },
    ]
    if (user.value?.role === 'CEO') {
        return all
    }
    if (user.value?.position === 'manager' && user.value?.role === 'SCM' && !hasExplicitModuleGrants('SCM')) return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'SCM') return all
    // No supervisor shortcut — per-page filter below governs (backend
    // grants the module shell but each page needs an explicit row).
    return all.filter(child => hasModulePermission('SCM', child.permKey))
}

const getFilteredWarehouseChildren = () => {
    const all = [
        { label: 'All Warehouses', href: route('warehouse.index'), icon: Warehouse, permKey: 'warehouse' },
        { label: 'Monitor', href: route('warehouse.monitor', { warehouse: 'placeholder' }), icon: Eye, permKey: 'monitor' },
        { label: 'Receiving', href: route('warehouse.receiving'), icon: Truck, permKey: 'receiving' },
        { label: 'Packages', href: route('warehouse.packages'), icon: Package, permKey: 'packages' },
        { label: 'Rejects', href: route('warehouse.rejects'), icon: XCircle, permKey: 'reject' },
    ]
    if (user.value?.role === 'CEO') {
        return all
    }
    if (user.value?.position === 'manager' && user.value?.role === 'WAR' && !hasExplicitModuleGrants('WAR')) return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'WAR') return all
    // No supervisor shortcut — per-page filter below governs (backend
    // grants the module shell but each page needs an explicit row).
    return all.filter(child => hasModulePermission('WAR', child.permKey))
}

const getFilteredInventoryChildren = () => {
    const all = [
        { label: 'Dashboard', href: route('inv.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
        { label: 'Materials', href: route('inv.materials'), icon: Spool, permKey: 'materials' },
        { label: 'Products', href: route('inv.products'), icon: Package, permKey: 'products' },
        { label: 'Bill of Materials', href: route('inv.bom'), icon: Layers, permKey: 'bom' },
        { label: 'Stock Checker', href: route('inv.checker'), icon: AlertCircle, permKey: 'checker' },
    ]
    if (user.value?.role === 'CEO') {
        return all
    }
    if (user.value?.position === 'manager' && user.value?.role === 'INV' && !hasExplicitModuleGrants('INV')) return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'INV') return all
    // No supervisor shortcut — per-page filter below governs (backend
    // grants the module shell but each page needs an explicit row).
    return all.filter(child => hasModulePermission('INV', child.permKey))
}

const getFilteredProChildren = () => {
    const all = [
        { label: 'Dashboard', href: route('pro.manager.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
        { label: 'Material Requests', href: route('pro.manager.material-requests'), icon: ClipboardList, permKey: 'requests' },
        { label: 'Quotations', href: route('pro.manager.supplier-quotations'), icon: FileText, permKey: 'quotations' },
        { label: 'Receipts', href: route('pro.manager.receipt'), icon: Send, permKey: 'receipt' },
    ]
    if (user.value?.role === 'CEO') {
        return all
    }
    if (user.value?.position === 'manager' && user.value?.role === 'PRO' && !hasExplicitModuleGrants('PRO')) return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'PRO') return all
    // No supervisor shortcut — per-page filter below governs (backend
    // grants the module shell but each page needs an explicit row).
    return all.filter(child => hasModulePermission('PRO', child.permKey))
}

const getFilteredFinChildren = () => {
    const all = [
        { label: 'Overview', href: route('fin.manager.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
        { label: 'Receivables', href: route('fin.manager.receivables'), icon: HandCoins, permKey: 'receivables' },
        { label: 'Payables', href: route('fin.manager.payables'), icon: Receipt, permKey: 'payables' },
        { label: 'Expenses', href: route('fin.manager.expenses'), icon: Wallet, permKey: 'expenses' },
        { label: 'Payroll', href: route('fin.manager.payroll'), icon: Users, permKey: 'payroll' },
        { label: 'Reports', href: route('fin.manager.reports'), icon: TrendingUp, permKey: 'reports' },
    ]
    if (user.value?.role === 'CEO') return all
    if (user.value?.position === 'manager' && user.value?.role === 'FIN' && !hasExplicitModuleGrants('FIN')) return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'FIN') return all
    // No supervisor shortcut — per-page filter below governs (backend
    // grants the module shell but each page needs an explicit row).
    return all.filter(child => hasModulePermission('FIN', child.permKey))
}

const getFilteredItChildren = () => {
    const all = [
        { label: 'Dashboard', href: route('it.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
        { label: 'Service Desk', href: route('it.tickets'), icon: HelpCircle, permKey: 'tickets' },
        { label: 'Assets', href: route('it.assets'), icon: Boxes, permKey: 'assets' },
        { label: 'Monitoring', href: route('it.monitoring'), icon: Activity, permKey: 'monitoring' },
        { label: 'Knowledge Base', href: route('it.knowledge'), icon: Book, permKey: 'knowledge' },
        { label: 'Geolocation', href: route('it.location.index'), icon: MapPin, permKey: 'location' },
        { label: 'Changes', href: route('it.changes'), icon: RefreshCw, permKey: 'changes' },
        { label: 'Access Control', href: route('it.access-control'), icon: Users, permKey: 'access_control' },
        { label: 'Access Logs', href: route('it.access-logs'), icon: FileText, permKey: 'access_logs' },
    ]
    if (user.value?.role === 'CEO') {
        all.push({ label: 'Access Control', href: route('it.access'), icon: ShieldCheck, permKey: 'access' })
        return all
    }
    if (user.value?.position === 'manager' && user.value?.role === 'IT' && !hasExplicitModuleGrants('IT')) return all
    if ((user.value?.position === 'secretary' || user.value?.position === 'special_officer') && rootModule.value === 'IT') return all
    // No supervisor shortcut — per-page filter below governs (backend
    // grants the module shell but each page needs an explicit row).
    return all.filter(child => hasModulePermission('IT', child.permKey))
}

// Navigation items computed
const navItems = computed(() => {
    if (isSupplier.value) {
        return [
            { label: 'Vendor Hub', href: route('supplier.dashboard'), icon: LayoutDashboard },
            { label: 'Purchase Orders', href: route('supplier.orders'), icon: ShoppingCart },
        ]
    }
    if (isClient.value) {
        return [
            { label: 'Dashboard', href: route('client.dashboard'), icon: LayoutDashboard },
            { label: 'Products', href: route('client.products'), icon: ShoppingBag },
            { label: 'Orders', href: route('client.orders'), icon: ShoppingCart },
            { label: 'Invoices', href: route('client.invoices'), icon: Receipt },
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

    const items = []
    const userRole = user.value?.role?.toUpperCase()
    const userPosition = user.value?.position?.toLowerCase()
    const isCEO = user.value?.role === 'CEO'
    const isCOO = user.value?.role === 'COO'

    if (isCEO || isCOO) {
        const isVP = isCOO || user.value?.position === 'vice_president'
        if (isVP) {
            // Vice Presidency: execution arm (operations, directives, workforce).
            items.push({ label: 'VP Dashboard', href: route('vp.operations'), icon: LayoutDashboard })
            items.push({ label: 'Downtime Board', href: route('vp.downtime'), icon: Wrench })
            items.push({ label: 'Shift Handovers', href: route('vp.handovers'), icon: Repeat })
            items.push({ label: 'Plan vs Actual', href: route('vp.plan'), icon: ClipboardList })
            items.push({ label: 'Utilities Brief', href: route('vp.utilities'), icon: Zap })
            items.push({ label: 'Directives', href: route('vp.directives'), icon: Megaphone })
            items.push({ label: 'Bulletins', href: route('vp.bulletins'), icon: Bell })
            items.push({ label: 'Workforce Overview', href: route('vp.workforce'), icon: User })
            items.push({ label: 'Joint Approvals', href: route('ceo.approvals'), icon: Stamp })
            return items
        }
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

    const modules = [
        { key: 'HRM', label: 'Human Resource', icon: Users, childrenGetter: getFilteredHrmChildren, isOpen: isHrmOpen, toggle: toggleHrm, condition: canAccessModule('HRM') || hasAnyModuleGrant('HRM') },
        { key: 'CRM', label: 'Customer Relationship', icon: UserPen, childrenGetter: getFilteredCrmChildren, isOpen: isCrmOpen, toggle: toggleCrm, condition: canAccessModule('CRM') || hasAnyModuleGrant('CRM') },
        { key: 'MAN', label: 'Manufacturing', icon: Factory, childrenGetter: getFilteredManChildren, isOpen: isManOpen, toggle: toggleMan, condition: canAccessModule('MAN') || hasAnyModuleGrant('MAN') },
        { key: 'LOG', label: 'Logistics', icon: Truck, childrenGetter: getFilteredLogisticsChildren, isOpen: isLogisticsOpen, toggle: toggleLogistics, condition: hasLogisticsAccess.value || hasAnyModuleGrant('LOG') },
        { key: 'ECO', label: 'E-Commerce', icon: ShoppingBag, childrenGetter: getFilteredEcoChildren, isOpen: isEcoOpen, toggle: toggleEco, condition: canAccessModule('ECO') || hasAnyModuleGrant('ECO') },
        { key: 'ORD', label: 'Order Management', icon: ClipboardCheck, childrenGetter: getFilteredOrdChildren, isOpen: isOrdOpen, toggle: toggleOrd, condition: hasOrdAccess.value || hasAnyModuleGrant('ORD') },
        { key: 'SCM', label: 'Supply Chain', icon: Truck, childrenGetter: getFilteredScmChildren, isOpen: isScmOpen, toggle: toggleScm, condition: canAccessModule('SCM') || hasAnyModuleGrant('SCM') },
        { key: 'WAR', label: 'Warehouse', icon: Warehouse, childrenGetter: getFilteredWarehouseChildren, isOpen: isWarehouseOpen, toggle: toggleWarehouse, condition: hasWarehouseAccess.value || hasAnyModuleGrant('WAR') },
        { key: 'INV', label: 'Inventory', icon: Boxes, childrenGetter: getFilteredInventoryChildren, isOpen: isInventoryOpen, toggle: toggleInventory, condition: hasInventoryAccess.value || hasAnyModuleGrant('INV') },
        { key: 'PRO', label: 'Procurement', icon: ShoppingCart, childrenGetter: getFilteredProChildren, isOpen: isProOpen, toggle: togglePro, condition: canAccessModule('PRO') || hasAnyModuleGrant('PRO') },
        { key: 'FIN', label: 'Finance', icon: Wallet, childrenGetter: getFilteredFinChildren, isOpen: isFinOpen, toggle: toggleFin, condition: canAccessModule('FIN') || hasAnyModuleGrant('FIN') },
        { key: 'IT', label: 'IT & Systems', icon: Cog, childrenGetter: getFilteredItChildren, isOpen: isItOpen, toggle: toggleIt, condition: canAccessModule('IT') || hasAnyModuleGrant('IT') },
    ]

    const coreModules = []
    const featureModules = []

    for (const mod of modules) {
        if (!mod.condition) continue
        const children = mod.childrenGetter()
        if (children.length === 0) continue
        const moduleItem = {
            label: mod.label,
            icon: mod.icon,
            isDropdown: true,
            isOpen: mod.isOpen.value,
            toggle: mod.toggle,
            children: children
        }
        if (['HRM', 'CRM', 'MAN', 'LOG'].includes(mod.key)) {
            coreModules.push(moduleItem)
        } else {
            featureModules.push(moduleItem)
        }
    }

    const workforceChildren = getFilteredWorkforceChildren()
    if (canAccessWorkforce() && workforceChildren.length) {
        featureModules.push({
            label: 'Workforce Management',
            icon: CalendarDays,
            isDropdown: true,
            isOpen: isWorkforceSubOpen.value,
            toggle: toggleWorkforceSub,
            children: workforceChildren
        })
    }

    if (coreModules.length) {
        items.push({ isHeading: true, label: 'Core Modules' })
        items.push(...coreModules)
    }
    if (featureModules.length) {
        items.push({ isHeading: true, label: 'Feature Modules' })
        items.push(...featureModules)
    }

    return items
})

const isActive = (href) => href !== '#' && (currentUrl.value === href || currentUrl.value.startsWith(href + '/'))
const handleNavClick = () => { isOpen.value = false }

const displayName = computed(() => {
    if (isSupplier.value) return supplier.value?.representative_name
    if (isClient.value) return client.value?.company_name
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
    if (isSupplier.value) return 'Supplier'
    if (isClient.value) return client.value?.business_type
    return user.value?.role
})
const displayPosition = computed(() => {
    if (isSupplier.value) return supplier.value?.business_name ?? 'Vendor'
    if (isEmployeePortal.value) return user.value?.employee_id ?? 'Staff'
    if (isClient.value) return 'Partner'
    if (user.value?.is_manufacturing_supervisor) return 'Supervisor'
    return user.value?.position
})
const sidebarLabel = computed(() => {
    if (isSupplier.value) return 'Vendor'
    if (isClient.value) return 'Partner'
    if (isEmployeePortal.value) return 'Employee'
    return 'System'
})
const logoutRoute = computed(() => {
    if (isClient.value) return route('client.logout')
    if (isSupplier.value) return route('supplier.logout')
    return route('logout')
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
                        <button @click.stop="showLogoutModal = true; isOpen = false"
                            class="p-2.5 rounded-xl bg-gray-100/80 dark:bg-gray-800/80 text-gray-400 hover:text-red-500 hover:bg-red-50/80 dark:hover:bg-red-900/20 transition-all duration-300 backdrop-blur-sm">
                            <LogOut class="h-4 w-4" />
                        </button>
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

        <Teleport to="body">
            <transition name="modal-fade">
                <div v-if="showLogoutModal"
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                    @click.self="showLogoutModal = false">
                    <div
                        class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 w-full max-w-sm p-6 flex flex-col items-center text-center transform transition-all duration-300 scale-100">
                        <div
                            class="w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                            <LogOut class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">Sign Out</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 px-2">
                            Are you sure you want to sign out of your account?
                        </p>
                        <div class="flex flex-col sm:flex-row gap-3 w-full">
                            <button @click="showLogoutModal = false"
                                class="w-full sm:flex-1 py-3 text-sm font-bold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                Cancel
                            </button>
                            <Link :href="logoutRoute" method="post" as="button"
                                class="w-full sm:flex-1 py-3 text-sm font-bold rounded-xl bg-red-600 text-white hover:bg-red-700 transition shadow-lg shadow-red-500/20">
                                Confirm Sign Out
                            </Link>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
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
