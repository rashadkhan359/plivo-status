import { usePage } from '@inertiajs/react';
import { User } from '@/types';

interface PermissionContext {
    organization: Record<string, boolean>;
    teams: Record<number, Record<string, boolean>>;
    resources: {
        teams: {
            accessible: number[];
            can_create: boolean;
        };
        services: {
            accessible: number[];
            can_create: boolean;
        };
        incidents: {
            can_create: boolean;
        };
        maintenance: {
            can_create: boolean;
        };
    };
}

interface AuthData {
    user: User;
    currentOrganization: any;
    currentRole: string;
    currentPermissions: PermissionContext;
}

export function usePermissions() {
    const { auth } = usePage<{ auth: AuthData }>().props;
    
    // Check if we're on a public page (no authenticated user)
    const isPublicPage = !auth?.user;
    
    const permissions = auth?.currentPermissions || {
        organization: {},
        teams: {},
        resources: {
            teams: { accessible: [], can_create: false },
            services: { accessible: [], can_create: false },
            incidents: { can_create: false },
            maintenance: { can_create: false },
        },
    };

    return {
        // User info
        user: auth?.user,
        currentRole: auth?.currentRole,
        isSystemAdmin: auth?.user?.is_system_admin || false,
        isPublicPage,
        
        // Organization permissions
        hasOrganizationPermission: (permission: string): boolean => {
            return permissions.organization[permission] || false;
        },
        
        // Team permissions
        hasTeamPermission: (teamId: number, permission: string): boolean => {
            return permissions.teams[teamId]?.[permission] || false;
        },
        
        // Resource access
        canAccessTeam: (teamId: number): boolean => {
            return permissions.resources.teams.accessible.includes(teamId) || 
                   permissions.organization.manage_teams || false;
        },
        
        canAccessService: (serviceId: number): boolean => {
            // On public pages, all services are accessible for viewing
            if (isPublicPage) return true;
            
            return permissions.resources.services.accessible.includes(serviceId) || 
                   permissions.organization.manage_services || false;
        },
        
        canCreateTeam: (): boolean => {
            return permissions.resources.teams.can_create || false;
        },
        
        canCreateService: (): boolean => {
            return permissions.resources.services.can_create || false;
        },
        
        canCreateIncident: (): boolean => {
            return permissions.resources.incidents.can_create || false;
        },
        
        canCreateMaintenance: (): boolean => {
            return permissions.resources.maintenance.can_create || false;
        },
        
        // Check if user can manage a specific resource
        canManageService: (service: { id: number; team_id?: number }): boolean => {
            // On public pages, no management is allowed
            if (isPublicPage) return false;
            
            // System admin can manage everything
            if (auth?.user?.is_system_admin) return true;
            
            // Organization-level permission
            if (permissions?.organization?.manage_services) return true;
            
            // Team-level permission
            if (service.team_id && permissions?.teams[service.team_id]?.manage_services) {
                return true;
            }
            
            return false;
        },
        
        canManageIncident: (incident: { services?: { id: number; team_id?: number }[] }): boolean => {
            // On public pages, no management is allowed
            if (isPublicPage) return false;
            
            // System admin can manage everything
            if (auth?.user?.is_system_admin) return true;
            
            // Organization-level permission
            if (permissions.organization.manage_incidents) return true;
            
            // Team-level permission - check if any of the incident's services belong to user's teams
            if (incident.services) {
                return incident.services.some(service => 
                    service.team_id && permissions.teams[service.team_id]?.manage_incidents
                );
            }
            
            return false;
        },
        
        canManageMaintenance: (maintenance: { service_id?: number; service?: { team_id?: number } }): boolean => {
            // On public pages, no management is allowed
            if (isPublicPage) return false;
            
            // System admin can manage everything
            if (auth?.user?.is_system_admin) return true;
            
            // Organization-level permission
            if (permissions.organization.manage_maintenance) return true;
            
            // Team-level permission - check if the maintenance's service belongs to user's teams
            if (maintenance.service?.team_id && permissions.teams[maintenance.service.team_id]?.manage_maintenance) {
                return true;
            }
            
            return false;
        },
        
        // Get accessible resource IDs
        getAccessibleTeamIds: (): number[] => {
            return permissions.resources.teams.accessible;
        },
        
        getAccessibleServiceIds: (): number[] => {
            return permissions.resources.services.accessible;
        },
        
        // Direct permission checks (for backward compatibility)
        canManageServices: (): boolean => {
            return permissions.organization.manage_services || false;
        },
        
        canManageTeams: (): boolean => {
            return permissions.organization.manage_teams || false;
        },
        
        canManageUsers: (): boolean => {
            return permissions.organization.manage_users || false;
        },
        
        canManageOrganization: (): boolean => {
            return permissions.organization.manage_organization || false;
        },
        
        canViewAnalytics: (): boolean => {
            return permissions.organization.view_analytics || false;
        },
        
        // Full permissions object for advanced use cases
        permissions,
    };
}

// Higher-order component for conditional rendering based on permissions
export function withPermission<T extends {}>(
    Component: React.ComponentType<T>,
    permissionCheck: (permissions: ReturnType<typeof usePermissions>) => boolean
) {
    return function PermissionWrapper(props: T) {
        const permissions = usePermissions();
        
        if (!permissionCheck(permissions)) {
            return null;
        }
        
        return <Component {...props} />;
    };
}

// Hook for conditional component rendering
export function useConditionalRender() {
    const permissions = usePermissions();

    return {
        renderIf: (condition: (perms: typeof permissions) => boolean, component: React.ReactNode) => {
            return condition(permissions) ? component : null;
        },
        permissions,
    };
} 