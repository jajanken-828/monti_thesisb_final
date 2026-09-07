<script>
import {
    LayoutDashboard, Package, Send, Truck, Users, Eye, Award,
    Navigation, MapPin, Camera, FileText, ShieldCheck,
} from 'lucide-vue-next'

export const logModule = {
    key: 'LOG',
    label: 'Logistics',
    icon: Truck,
    group: 'core',

    condition: (ctx) => ctx.hasLogisticsAccess,

    getChildren(ctx) {
        const { route, isCEO } = ctx
        const all = [
            { label: 'Dashboard', href: route('logistics.dashboard'), icon: LayoutDashboard },
            { label: 'Load', href: route('logistics.load.index'), icon: Package },
            { label: 'Dispatch', href: route('logistics.dispatch.index'), icon: Send },
            { label: 'Fleet', href: route('logistics.fleet.index'), icon: Truck },
            { label: 'Drivers', href: route('logistics.drivers.index'), icon: Users },
            { label: 'Routes', href: route('logistics.routes'), icon: Navigation },
            { label: 'Tracking', href: route('logistics.tracking'), icon: MapPin },
            { label: 'Proof', href: route('logistics.proof.index'), icon: Camera },
            { label: 'Reports', href: route('logistics.reports.index'), icon: FileText },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('logistics.access.index'), icon: ShieldCheck })
        }
        return all
    },
}
</script>
