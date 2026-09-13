<script>
import { LayoutDashboard, LifeBuoy, MonitorSmartphone, Activity, BookOpen, GitPullRequest, ShieldCheck, Users, ScrollText } from 'lucide-vue-next'

export const itModule = {
    key: 'IT',
    label: 'IT & Systems',
    icon: MonitorSmartphone,
    group: 'feature',

    condition: (ctx) => ctx.canAccessModule('IT'),

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, canAccessModule, hasModulePermission, grantedModules } = ctx
        const all = [
            { label: 'Dashboard', href: route('it.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Service Desk', href: route('it.tickets'), icon: LifeBuoy, permKey: 'tickets' },
            { label: 'Assets', href: route('it.assets'), icon: MonitorSmartphone, permKey: 'assets' },
            { label: 'Monitoring', href: route('it.monitoring'), icon: Activity, permKey: 'monitoring' },
            { label: 'Knowledge Base', href: route('it.knowledge'), icon: BookOpen, permKey: 'knowledge' },
            { label: 'Changes', href: route('it.changes'), icon: GitPullRequest, permKey: 'changes' },
            { label: 'Access Control', href: route('it.access-control'), icon: Users, permKey: 'access_control' },
            { label: 'Access Logs', href: route('it.access-logs'), icon: ScrollText, permKey: 'access_logs' },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('it.access'), icon: ShieldCheck, permKey: 'access' })
        }
        if (isCEO) return all
        if (userPosition === 'manager' && user?.role === 'IT') return all
        if (isSecretaryOrGM && canAccessModule('IT')) return all
        if (user?.is_manufacturing_supervisor && grantedModules.includes('IT')) return all
        return all.filter(child => hasModulePermission('IT', child.permKey))
    },
}
</script>
