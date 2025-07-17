import { usePage } from '@inertiajs/react';
import { SharedData } from '@/types';

/**
 * Hook for checking user permissions and roles
 */
export function usePermissions() {
    const { auth } = usePage<SharedData>().props;
    const { currentRole, currentPermissions } = auth;

    /**
     * Check if user has a specific permission
     */
    const hasPermission = (permission: keyof NonNullable<typeof currentPermissions>): boolean => {
        return currentPermissions?.[permission] === true;
    };

    /**
     * Check if user has any of the specified permissions
     */
    const hasAnyPermission = (permissions: (keyof NonNullable<typeof currentPermissions>)[]): boolean => {
        return permissions.some(permission => hasPermission(permission));
    };

    /**
     * Check if user has all of the specified permissions
     */
    const hasAllPermissions = (permissions: (keyof NonNullable<typeof currentPermissions>)[]): boolean => {
        return permissions.every(permission => hasPermission(permission));
    };

    /**
     * Check if user has a specific role
     */
    const hasRole = (role: NonNullable<typeof currentRole>): boolean => {
        return currentRole === role;
    };

    /**
     * Check if user has any of the specified roles
     */
    const hasAnyRole = (roles: NonNullable<typeof currentRole>[]): boolean => {
        return roles.includes(currentRole as NonNullable<typeof currentRole>);
    };

    /**
     * Check if user is owner or admin (high-level access)
     */
    const isAdmin = (): boolean => {
        return hasAnyRole(['owner', 'admin']);
    };

    /**
     * Check if user can manage teams (team lead or higher)
     */
    const canManageTeams = (): boolean => {
        return hasPermission('manage_teams') || isAdmin();
    };

    /**
     * Check if user can manage users (admin or higher)
     */
    const canManageUsers = (): boolean => {
        return hasPermission('manage_users') || isAdmin();
    };

    /**
     * Check if user can manage services
     */
    const canManageServices = (): boolean => {
        return hasPermission('manage_services') || isAdmin();
    };

    /**
     * Check if user can manage incidents
     */
    const canManageIncidents = (): boolean => {
        return hasPermission('manage_incidents') || isAdmin();
    };

    /**
     * Check if user can manage maintenance
     */
    const canManageMaintenance = (): boolean => {
        return hasPermission('manage_maintenance') || isAdmin();
    };

    /**
     * Check if user can view analytics
     */
    const canViewAnalytics = (): boolean => {
        return hasPermission('view_analytics') || isAdmin();
    };

    return {
        currentRole,
        currentPermissions,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        hasRole,
        hasAnyRole,
        isAdmin,
        canManageTeams,
        canManageUsers,
        canManageServices,
        canManageIncidents,
        canManageMaintenance,
        canViewAnalytics,
    };
} 