<script>
import {
    LayoutDashboard, HandCoins, Receipt, Wallet, PieChart,
    Users, BarChart3,
} from 'lucide-vue-next'

export const finModule = {
    key: 'FIN',
    label: 'Finance',
    icon: Wallet,
    group: 'feature',

    condition: (ctx) => ctx.canAccessModule('FIN'),

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, canAccessModule, hasModulePermission, grantedModules } = ctx
        const all = [
            { label: 'Overview', href: route('fin.manager.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Receivables', href: route('fin.manager.receivables'), icon: HandCoins, permKey: 'receivables' },
            { label: 'Payables', href: route('fin.manager.payables'), icon: Receipt, permKey: 'payables' },
            { label: 'Expenses', href: route('fin.manager.expenses'), icon: PieChart, permKey: 'expenses' },
            { label: 'Payroll', href: route('fin.manager.payroll'), icon: Users, permKey: 'payroll' },
            { label: 'Reports', href: route('fin.manager.reports'), icon: BarChart3, permKey: 'reports' },
        ]
        if (isCEO) return all
        if (userPosition === 'manager' && user?.role === 'FIN') return all
        if (isSecretaryOrGM && canAccessModule('FIN')) return all
        if (user?.is_manufacturing_supervisor && grantedModules.includes('FIN')) return all
        return all.filter(child => hasModulePermission('FIN', child.permKey))
    },
}
</script>
