<script>
import {
    LayoutDashboard, ShoppingBag, MessageSquare, Users as UsersIcon,
    CreditCard, Send, ShieldCheck,
} from 'lucide-vue-next'

export const ecoModule = {
    key: 'ECO',
    label: 'E-Commerce',
    icon: ShoppingBag,
    group: 'feature',

    condition: (ctx) => ctx.canAccessModule('ECO'),

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, canAccessModule, hasModulePermission, grantedModules } = ctx
        const all = [
            { label: 'Dashboard', href: route('eco.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Store', href: route('eco.store'), icon: ShoppingBag, permKey: 'store' },
            { label: 'Inquiries', href: route('eco.inquiries'), icon: MessageSquare, permKey: 'inquiry' },
            { label: 'Suppliers', href: route('eco.suppliers'), icon: UsersIcon, permKey: 'supplier' },
            { label: 'Credit', href: route('eco.credit'), icon: CreditCard, permKey: 'credit' },
            { label: 'Push Center', href: route('eco.push'), icon: Send, permKey: 'push' },
            { label: 'Access Control', href: route('eco.access'), icon: ShieldCheck, permKey: 'access' },
        ]
        if (isCEO) return all
        if (userPosition === 'manager' && user?.role === 'ECO') return all
        if (isSecretaryOrGM && canAccessModule('ECO')) return all
        if (user?.is_manufacturing_supervisor && grantedModules.includes('ECO')) return all
        return all.filter(child => hasModulePermission('ECO', child.permKey))
    },
}
</script>
