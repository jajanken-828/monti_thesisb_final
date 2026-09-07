<script>
import { ShoppingCart, ClipboardList, Building2, ShieldCheck, Truck } from 'lucide-vue-next'

export const scmModule = {
    key: 'SCM',
    label: 'Supply Chain',
    icon: Truck,
    group: 'feature',

    condition: (ctx) => ctx.canAccessModule('SCM'),

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, canAccessModule, hasModulePermission, grantedModules } = ctx
        const all = [
            { label: 'Sales Orders', href: route('scm.sales-orders'), icon: ShoppingCart, permKey: 'sales' },
            { label: 'Procurement Orders', href: route('scm.procurement-orders'), icon: ClipboardList, permKey: 'procurement' },
            { label: 'Vendors', href: route('scm.vendors'), icon: Building2, permKey: 'vendor' },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('scm.access.index'), icon: ShieldCheck, permKey: 'access' })
        }
        if (isCEO) return all
        if (userPosition === 'manager' && user?.role === 'SCM') return all
        if (isSecretaryOrGM && canAccessModule('SCM')) return all
        if (user?.is_manufacturing_supervisor && grantedModules.includes('SCM')) return all
        return all.filter(child => hasModulePermission('SCM', child.permKey))
    },
}
</script>
