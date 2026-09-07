<script>
import { LayoutDashboard, Spool, Package, Layers, AlertCircle, ShieldCheck, Boxes } from 'lucide-vue-next'

export const inventoryModule = {
    key: 'INV',
    label: 'Inventory',
    icon: Boxes,
    group: 'feature',

    condition: (ctx) => ctx.hasInventoryAccess,

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, canAccessModule, hasModulePermission, grantedModules } = ctx
        const all = [
            { label: 'Dashboard', href: route('inv.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Materials', href: route('inv.materials'), icon: Spool, permKey: 'materials' },
            { label: 'Products', href: route('inv.products'), icon: Package, permKey: 'products' },
            { label: 'Recipes', href: route('inv.bom'), icon: Layers, permKey: 'bom' },
            { label: 'Stock Checker', href: route('inv.checker'), icon: AlertCircle, permKey: 'checker' },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('inv.access'), icon: ShieldCheck, permKey: 'access' })
        }
        if (isCEO) return all
        if (userPosition === 'manager' && user?.role === 'INV') return all
        if (isSecretaryOrGM && canAccessModule('INV')) return all
        if (user?.is_manufacturing_supervisor && grantedModules.includes('INV')) return all
        return all.filter(child => hasModulePermission('INV', child.permKey))
    },
}
</script>
