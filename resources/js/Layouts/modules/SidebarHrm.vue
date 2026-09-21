<script>
import {
    LayoutDashboard, Users, FileText, Eye, Award, UserPlus, Archive,
    HandCoins, ChartNoAxesCombined,
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

    // Extra-module grants also display the section (filtered per page below).
    condition: (ctx) => ctx.canAccessModule('HRM') || ctx.hasAnyModuleGrant('HRM'),

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
        ]
        if (isCEO) return all
        // Explicit rows (even all-'disabled') are the exact access set and
        // override this shortcut — mirrors backend CheckPagePermission.
        if (userPosition === 'manager' && user?.role === 'HRM' && !ctx.hasExplicitModuleGrants('HRM')) return all
        // Auto-full on the root module only (mirrors backend CheckPagePermission);
        // extra granted modules fall through to the per-page filter below.
        if (isSecretaryOrGM && ctx.rootModule === 'HRM') return all
        // No supervisor shortcut: the backend grants supervisors the module shell
        // but every page still needs an explicit row — fall through to filter.
        // No fallback: when every page is 'disabled' (the IT default), the
        // filtered list is empty and buildNavItems skips this module entirely,
        // leaving the sidebar empty → AwaitingAccess landing page.
        return all.filter(child => hasHrmPermission(child.permKey))
    },
}
</script>
