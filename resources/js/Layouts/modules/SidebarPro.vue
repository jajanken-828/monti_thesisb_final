<script>
import { LayoutDashboard, FileText, Send, ShieldCheck, ShoppingCart } from 'lucide-vue-next'

export const proModule = {
    key: 'PRO',
    label: 'Procurement',
    icon: ShoppingCart,
    group: 'feature',

    condition: (ctx) => ctx.canAccessModule('PRO'),

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, canAccessModule, hasModulePermission, grantedModules } = ctx
        const all = [
            { label: 'Dashboard', href: route('pro.manager.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Quotations', href: route('pro.manager.supplier-quotations'), icon: FileText, permKey: 'quotations' },
            { label: 'Receipts', href: route('pro.manager.receipt'), icon: Send, permKey: 'receipt' },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('pro.access.index'), icon: ShieldCheck, permKey: 'access' })
        }
        if (isCEO) return all
        if (userPosition === 'manager' && user?.role === 'PRO') return all
        if (isSecretaryOrGM && canAccessModule('PRO')) return all
        if (user?.is_manufacturing_supervisor && grantedModules.includes('PRO')) return all
        return all.filter(child => hasModulePermission('PRO', child.permKey))
    },
}
</script>
