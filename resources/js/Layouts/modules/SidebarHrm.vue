<script>
import {
    LayoutDashboard, Users, FileText, Eye, Award, UserPlus, Archive,
    HandCoins, ChartNoAxesCombined, ShieldCheck,
} from 'lucide-vue-next'

/**
 * This file has no <template> on purpose — it's a logic-only module,
 * not a rendered component. Sidebar.vue imports `hrmModule` and feeds
 * it into the generic nav-item renderer.
 */
export const hrmModule = {
    key: 'HRM',
    label: 'Human Resource',
    icon: Users,
    group: 'core',

    condition: (ctx) => ctx.canAccessModule('HRM'),

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, canAccessModule, hasHrmPermission, grantedModules } = ctx
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
            { label: 'Access Control', href: route('hrm.access.index'), icon: ShieldCheck, permKey: 'access' },
        ]
        if (isCEO) return all
        if (userPosition === 'manager' && user?.role === 'HRM') return all
        if (isSecretaryOrGM && canAccessModule('HRM')) return all
        if (user?.is_manufacturing_supervisor && grantedModules.includes('HRM')) return all
        const filtered = all.filter(child => hasHrmPermission(child.permKey))
        if (filtered.length === 0) return [all[0]]
        return filtered
    },
}
</script>
