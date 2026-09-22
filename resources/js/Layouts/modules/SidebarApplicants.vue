<script>
import {
    LayoutDashboard, FileText, Eye, UserPlus, Archive, RotateCcw,
} from 'lucide-vue-next'

/**
 * Applicants module sidebar (desktop).
 * Applicant master connected to HRM recruitment: shares the canonical
 * `applicants` table, so visibility follows HRM access + the `application`
 * page grant — no new role required (mirrors backend page.permission:application).
 */
export const applicantsModule = {
    key: 'APL',
    label: 'Applicants',
    icon: FileText,
    group: 'core',

    condition: (ctx) => ctx.canAccessModule('HRM') || ctx.hasAnyModuleGrant('HRM'),

    getChildren(ctx) {
        const { route, isCEO, isSecretaryOrGM, userPosition, user, hasHrmPermission } = ctx
        const safe = (name, params) => {
            try { return route(name, params) } catch { return '#' }
        }
        const all = [
            { label: 'Applicant Queue', href: safe('applicants.index'), icon: LayoutDashboard, permKey: 'application' },
            { label: 'HRM Applications', href: safe('hrm.recruitment.applications.index'), icon: FileText, permKey: 'application' },
            { label: 'Interviews', href: safe('hrm.recruitment.interviews.index'), icon: Eye, permKey: 'interview' },
            { label: 'To Onboarding', href: safe('hrm.onboarding.status.index'), icon: UserPlus, permKey: 'onboarding' },
            { label: 'Archive', href: safe('hrm.applications.rejected'), icon: Archive, permKey: 'application' },
            { label: 'Restore Applic.', href: safe('applicants.index', { archived: 1 }), icon: RotateCcw, permKey: 'application' },
        ]
        const existing = all.filter(child => child.href && child.href !== '#')
        const list = existing.length ? existing : all
        if (isCEO) return list
        if (userPosition === 'manager' && user?.role === 'HRM' && !ctx.hasExplicitModuleGrants('HRM')) return list
        if (isSecretaryOrGM && ctx.rootModule === 'HRM') return list
        return list.filter(child => hasHrmPermission(child.permKey))
    },
}
</script>
