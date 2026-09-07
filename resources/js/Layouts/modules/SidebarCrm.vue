<script>
import {
    LayoutDashboard, FileUser, Eye, Award, ClipboardCheck, Users,
    AlertCircle, ShieldCheck, UserPen, Share2,
} from 'lucide-vue-next'

export const crmModule = {
    key: 'CRM',
    label: 'Customer Relationship',
    icon: UserPen,
    group: 'core',

    condition: (ctx) => ctx.canAccessModule('CRM'),

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, canAccessModule, hasCrmPermission, grantedModules } = ctx
        const all = [
            { label: 'Dashboard', href: route('crm.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Leads', href: route('crm.lead'), icon: FileUser, permKey: 'leads' },
            { label: 'Approvals', href: route('crm.approval.index'), icon: ClipboardCheck, permKey: 'approvals' },
            { label: 'Customer Profiles', href: route('crm.customerprofile.index'), icon: Users, permKey: 'customer_profiles' },
            { label: 'Investigation', href: route('crm.investigation.index'), icon: AlertCircle, permKey: 'investigation' },
            { label: 'Socials', href: route('crm.socials.index'), icon: Share2, permKey: 'socials' },
            { label: 'Access Control', href: route('crm.access.index'), icon: ShieldCheck, permKey: 'access' },
        ]
        if (isCEO) return all
        if (userPosition === 'manager' && user?.role === 'CRM') return all
        if (isSecretaryOrGM && canAccessModule('CRM')) return all
        if (user?.is_manufacturing_supervisor && grantedModules.includes('CRM')) return all
        const filtered = all.filter(child => hasCrmPermission(child.permKey))
        if (filtered.length === 0) return [all[0]]
        return filtered
    },
}
</script>
