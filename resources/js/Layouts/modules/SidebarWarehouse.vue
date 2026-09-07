<script>
import { Warehouse, Truck, Package, XCircle, ShieldCheck } from 'lucide-vue-next'

export const warehouseModule = {
    key: 'WAR',
    label: 'Warehouse',
    icon: Warehouse,
    group: 'feature',

    condition: (ctx) => ctx.hasWarehouseAccess,

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, canAccessModule, hasModulePermission, grantedModules } = ctx
        const all = [
            { label: 'All Warehouses', href: route('warehouse.index'), icon: Warehouse, permKey: 'warehouse' },
            { label: 'Receiving', href: route('warehouse.receiving'), icon: Truck, permKey: 'receiving' },
            { label: 'Packages', href: route('warehouse.packages'), icon: Package, permKey: 'packages' },
            { label: 'Rejects', href: route('warehouse.rejects'), icon: XCircle, permKey: 'reject' },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('warehouse.access'), icon: ShieldCheck, permKey: 'access' })
        }
        if (isCEO) return all
        if (userPosition === 'manager' && user?.role === 'WAR') return all
        if (isSecretaryOrGM && canAccessModule('WAR')) return all
        if (user?.is_manufacturing_supervisor && grantedModules.includes('WAR')) return all
        return all.filter(child => hasModulePermission('WAR', child.permKey))
    },
}
</script>
