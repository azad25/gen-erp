import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const user = ref(null)
const permissions = ref([])
const roles = ref([])

export function useAuth() {
  const page = usePage()
  
  // Initialize from Inertia page props
  if (page.props.auth) {
    user.value = page.props.auth.user
    permissions.value = page.props.auth.permissions || []
    roles.value = page.props.auth.roles || []
  }
  
  const isAuthenticated = computed(() => {
    return !!user.value
  })
  
  const hasPermission = (permission) => {
    if (!isAuthenticated.value) return false
    return permissions.value.includes(permission)
  }
  
  const hasRole = (role) => {
    if (!isAuthenticated.value) return false
    return roles.value.includes(role)
  }
  
  const hasAnyPermission = (permissionList) => {
    if (!isAuthenticated.value) return false
    return permissionList.some(permission => permissions.value.includes(permission))
  }
  
  const hasAllPermissions = (permissionList) => {
    if (!isAuthenticated.value) return false
    return permissionList.every(permission => permissions.value.includes(permission))
  }
  
  const hasAnyRole = (roleList) => {
    if (!isAuthenticated.value) return false
    return roleList.some(role => roles.value.includes(role))
  }
  
  const getApiToken = () => {
    return page.props.auth?.api_token || 
           document.querySelector('meta[name="api-token"]')?.content ||
           localStorage.getItem('api_token')
  }
  
  const updateUser = (userData) => {
    user.value = { ...user.value, ...userData }
  }
  
  const updatePermissions = (newPermissions) => {
    permissions.value = newPermissions
  }
  
  const updateRoles = (newRoles) => {
    roles.value = newRoles
  }
  
  const logout = async () => {
    try {
      await fetch('/auth/logout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        }
      })
      
      // Clear local state
      user.value = null
      permissions.value = []
      roles.value = []
      
      // Redirect to login
      window.location.href = '/login'
    } catch (error) {
      console.error('Logout failed:', error)
    }
  }
  
  const can = (permission) => hasPermission(permission)
  const cannot = (permission) => !hasPermission(permission)
  const is = (role) => hasRole(role)
  const isNot = (role) => !hasRole(role)
  
  return {
    user: computed(() => user.value),
    permissions: computed(() => permissions.value),
    roles: computed(() => roles.value),
    isAuthenticated,
    hasPermission,
    hasRole,
    hasAnyPermission,
    hasAllPermissions,
    hasAnyRole,
    getApiToken,
    updateUser,
    updatePermissions,
    updateRoles,
    logout,
    can,
    cannot,
    is,
    isNot
  }
}