import { computed } from 'vue'

/**
 * @param {import('vue').ComputedRef} user  - computed(() => page.props.auth.user)
 * @param {object} page - the Inertia page object from usePage()
 */
export function usePermissions(user, page) {
    const userModuleAccess = computed(() => {
        if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') {
            return page.props.auth.user?.granted_modules || []
        }
        return []
    })

    const grantedModules = computed(() => {
        if (user.value?.is_manufacturing_supervisor) {
            return page.props.auth.user?.granted_modules || []
        }
        return []
    })

    const canAccessModule = (moduleName) => {
        if (user.value?.role === 'CEO') return true
        if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') {
            return userModuleAccess.value.includes(moduleName)
        }
        if (user.value?.is_manufacturing_supervisor) {
            if (moduleName === 'MAN') return true
            return grantedModules.value.includes(moduleName)
        }
        if (moduleName === 'MAN' && user.value?.manufacturing_role) return true
        return user.value?.role === moduleName
    }

    const canAccessWorkforce = () => {
        if (user.value?.role === 'CEO') return true
        if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') {
            return userModuleAccess.value.includes('WRF')
        }
        if (user.value?.is_manufacturing_supervisor) {
            return grantedModules.value.includes('WRF')
        }
        const perms = user.value?.workforce_permissions
        return perms && perms.length > 0
    }

    const hasWorkforcePermission = (pageName) => {
        if (user.value?.role === 'CEO') return true
        const perms = user.value?.workforce_permissions
        if (!perms) return false
        return perms.includes(pageName)
    }

    const hasPagePermission = (moduleKey, pageKey) => {
        if (user.value?.role === 'CEO') return true
        const perms = user.value?.page_permissions || page.props.auth?.page_permissions || []
        return perms.some(p => p.module === moduleKey && p.page === pageKey)
    }

    const hasHrmPermission = (pageKey) => hasPagePermission('HRM', pageKey)
    const hasCrmPermission = (pageKey) => hasPagePermission('CRM', pageKey)

    const hasModulePermission = (moduleKey, permissionKey) => {
        if (user.value?.role === 'CEO') return true
        const modulePerms = user.value?.permissions?.[moduleKey]
        return modulePerms ? modulePerms.includes(permissionKey) : false
    }

    const hasWarehouseAccess = computed(() => {
        if (user.value?.role === 'CEO') return true
        if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') return canAccessModule('WAR')
        if (user.value?.is_manufacturing_supervisor) return grantedModules.value.includes('WAR')
        return user.value?.has_warehouse_access === true
    })

    const hasInventoryAccess = computed(() => {
        if (user.value?.role === 'CEO') return true
        if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') return canAccessModule('INV')
        if (user.value?.is_manufacturing_supervisor) return grantedModules.value.includes('INV')
        return user.value?.has_inventory_access === true
    })

    const hasOrdAccess = computed(() => {
        if (user.value?.role === 'CEO') return true
        if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') return canAccessModule('ORD')
        if (user.value?.is_manufacturing_supervisor) return grantedModules.value.includes('ORD')
        return user.value?.has_ord_access === true
    })

    const hasLogisticsAccess = computed(() => {
        if (user.value?.role === 'CEO') return true
        if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') return canAccessModule('LOG')
        if (user.value?.is_manufacturing_supervisor) return grantedModules.value.includes('LOG')
        if (user.value?.role === 'LOG' && user.value?.position === 'manager') return true
        return user.value?.logistics_access === true
    })

    const canAccessModuleChild = (moduleKey, childPermKey, moduleSpecificCheck = null) => {
        if (user.value?.role === 'CEO') return true
        if (moduleSpecificCheck) return moduleSpecificCheck(childPermKey)
        const granularPerms = user.value?.permissions?.[moduleKey]
        if (granularPerms && granularPerms.length) return granularPerms.includes(childPermKey)
        const isManager = user.value?.role === moduleKey && user.value?.position === 'manager'
        const isSecretaryOrGMWithModule = (user.value?.position === 'secretary' || user.value?.position === 'general_manager') &&
            userModuleAccess.value.includes(moduleKey)
        const isSupervisorWithModule = user.value?.is_manufacturing_supervisor && grantedModules.value.includes(moduleKey)
        return isManager || isSecretaryOrGMWithModule || isSupervisorWithModule
    }

    return {
        userModuleAccess,
        grantedModules,
        canAccessModule,
        canAccessWorkforce,
        hasWorkforcePermission,
        hasPagePermission,
        hasHrmPermission,
        hasCrmPermission,
        hasModulePermission,
        hasWarehouseAccess,
        hasInventoryAccess,
        hasOrdAccess,
        hasLogisticsAccess,
        canAccessModuleChild,
    }
}
