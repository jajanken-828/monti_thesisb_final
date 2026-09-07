<script>
import {
    Factory, LayoutDashboard, ClipboardList, XCircle, Boxes, CheckCircle2,
    Eye, Award, ShieldCheck, FileText, Sparkles, Palette, Wrench,
} from 'lucide-vue-next'

// Static config for the "single manufacturing role" staff view (Case 2 below)
const STAFF_ROLE_CONFIG = {
    knitting_yarn: { label: 'Knitting Yarn', icon: Sparkles, hasReports: true },
    dyeing_color: { label: 'Dyeing Color', icon: Palette, hasReports: true },
    dyeing_fabric_softener: { label: 'Dyeing Fabric Softener', icon: Palette, hasReports: true },
    dyeing_squeezer: { label: 'Dyeing Squeezer', icon: Palette, hasReports: true },
    dyeing_ironing: { label: 'Dyeing Ironing', icon: Palette, hasReports: true },
    dyeing_forming: { label: 'Dyeing Forming', icon: Palette, hasReports: true },
    dyeing_packaging: { label: 'Dyeing Packaging', icon: Palette, hasReports: false },
    maintenance_checker: { label: 'Maintenance Checker', icon: Wrench, hasReports: true },
}

// Which roles a supervisor sees, per department they supervise
const DEPARTMENT_ROLES = {
    knitting: ['knitting_yarn'],
    dyeing: [
        'dyeing_color', 'dyeing_fabric_softener', 'dyeing_squeezer',
        'dyeing_ironing', 'dyeing_forming', 'dyeing_packaging',
    ],
    maintenance: ['maintenance_checker'],
}

function getRoleLinks(route, roleWithUnderscores, label, icon, hasReports = true) {
    const roleWithHyphens = roleWithUnderscores.replace(/_/g, '-')
    const routePrefix = `man.staff.${roleWithHyphens}`
    const links = [
        { label: 'Dashboard', href: route(`${routePrefix}.dashboard`), icon: LayoutDashboard },
        { label: label, href: route(`${routePrefix}.page`), icon: icon },
    ]
    if (hasReports) {
        links.push({ label: 'Reports', href: route(`${routePrefix}.reports`), icon: FileText })
    }
    return links
}

function createRoleDropdown(ctx, roleKey, roleLabel, roleIcon, hasReports = true) {
    const { route, roleDropdowns } = ctx
    return {
        label: roleLabel,
        icon: roleIcon,
        isDropdown: true,
        isOpen: roleDropdowns.getState(roleKey, false),
        toggle: () => roleDropdowns.toggle(roleKey),
        children: getRoleLinks(route, roleKey, roleLabel, roleIcon, hasReports),
    }
}

function departmentStaffDropdowns(ctx) {
    const dept = ctx.supervisedDepartment
    const roleKeys = DEPARTMENT_ROLES[dept] || []
    return roleKeys.map(roleKey => {
        const config = STAFF_ROLE_CONFIG[roleKey]
        return createRoleDropdown(ctx, roleKey, config.label, config.icon, config.hasReports)
    })
}

export const manModule = {
    key: 'MAN',
    label: 'Manufacturing',
    icon: Factory,
    group: 'core',

    condition: (ctx) => ctx.canAccessModule('MAN'),

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, isManufacturingSupervisor, qualityChecker } = ctx

        const qualityCheckerChildren = [
            { label: 'Dashboard', href: '/dashboard/man/checker-quality', icon: LayoutDashboard },
            { label: 'Production', href: '/dashboard/man/checker-quality/production', icon: ClipboardList },
        ]

        const managerChildren = [
            { label: 'Dashboard', href: route('man.manager.dashboard'), icon: Factory, permKey: 'dashboard' },
            { label: 'Production Orders', href: route('man.manager.production'), icon: ClipboardList, permKey: 'production' },
            { label: 'Rejected Items', href: route('man.manager.rejected'), icon: XCircle, permKey: 'reject' },
            { label: 'Production Inventory', href: route('man.inventory.index'), icon: Boxes, permKey: 'inventory' },
            {
                label: 'Quality Checker',
                icon: CheckCircle2,
                isDropdown: true,
                isOpen: qualityChecker.isOpen,
                toggle: qualityChecker.toggle,
                children: qualityCheckerChildren,
            },
           
        ]

        const isManufacturingManager = userPosition === 'manager' && user?.role === 'MAN'
        if (isCEO || isSecretaryOrGM || isManufacturingManager) {
            managerChildren.push({ label: 'Access Control', href: route('man.access.manage'), icon: ShieldCheck, permKey: 'access' })
        }

        let children = []

        // Case 1: manager-level access (CEO, MAN manager, secretary/GM, or supervisor)
        if (isCEO || isManufacturingManager || isSecretaryOrGM || isManufacturingSupervisor) {
            children = [...managerChildren]

            if (isManufacturingSupervisor && ctx.supervisedDepartment) {
                const staffDropdowns = departmentStaffDropdowns(ctx)
                if (staffDropdowns.length) {
                    children.push({ isDivider: true, label: '── Department Staff ──' })
                    children.push(...staffDropdowns)
                }
            }
        } else {
            // Case 2: regular staff with a single manufacturing_role
            const manufacturingRole = user?.manufacturing_role
            const config = STAFF_ROLE_CONFIG[manufacturingRole]
            if (config) {
                children = [createRoleDropdown(ctx, manufacturingRole, config.label, config.icon, config.hasReports)]
            }
        }

        return children
    },
}
</script>
