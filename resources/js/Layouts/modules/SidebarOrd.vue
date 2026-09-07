<script>
import { ClipboardList, Factory, Truck, ShieldCheck, ClipboardCheck } from 'lucide-vue-next'

export const ordModule = {
    key: 'ORD',
    label: 'Order Management',
    icon: ClipboardCheck,
    group: 'feature',

    condition: (ctx) => ctx.hasOrdAccess,

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, canAccessModule, hasModulePermission, grantedModules } = ctx
        const all = [
            { label: 'Orders', href: route('ord.orders'), icon: ClipboardList, permKey: 'orders' },
            { label: 'Productions', href: route('ord.productions'), icon: Factory, permKey: 'productions' },
            { label: 'Delivery', href: route('ord.delivery'), icon: Truck, permKey: 'delivery' },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('ord.ceo-access.index'), icon: ShieldCheck, permKey: 'access' })
        }
        if (isCEO) return all
        if (userPosition === 'manager' && user?.role === 'ORD') return all
        if (isSecretaryOrGM && canAccessModule('ORD')) return all
        if (user?.is_manufacturing_supervisor && grantedModules.includes('ORD')) return all
        return all.filter(child => hasModulePermission('ORD', child.permKey))
    },
}
</script>
