import { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Shield, Crown, Users, Settings, AlertTriangle, Wrench, BarChart3 } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';
import { router } from '@inertiajs/react';

interface Permission {
    key: string;
    label: string;
    description: string;
    icon: React.ComponentType<{ className?: string }>;
}

interface RolePermissionManagerProps {
    type: 'organization' | 'team';
    entityId: number;
    roles: {
        role: string;
        permissions: Record<string, boolean>;
        usersCount: number;
    }[];
    onUpdatePermissions: (role: string, permissions: Record<string, boolean>) => Promise<void>;
}

// Icon mapping for permissions
const PERMISSION_ICONS: Record<string, React.ComponentType<{ className?: string }>> = {
    manage_organization: Settings,
    manage_users: Users,
    manage_teams: Shield,
    manage_services: Settings,
    manage_incidents: AlertTriangle,
    manage_maintenance: Wrench,
    view_analytics: BarChart3,
};

const ROLE_INFO: Record<string, {
    label: string;
    description: string;
    icon: React.ComponentType<{ className?: string }>;
    color: string;
}> = {
    owner: {
        label: 'Owner',
        description: 'Full access to all features and settings',
        icon: Crown,
        color: 'bg-purple-100 text-purple-800',
    },
    admin: {
        label: 'Admin',
        description: 'Full administrative access',
        icon: Shield,
        color: 'bg-yellow-100 text-yellow-800',
    },
    team_lead: {
        label: 'Team Lead',
        description: 'Can manage team members and team-specific resources',
        icon: Users,
        color: 'bg-green-100 text-green-800',
    },
    member: {
        label: 'Member',
        description: 'Basic access to view and create content',
        icon: Users,
        color: 'bg-gray-100 text-gray-800',
    },
    lead: {
        label: 'Team Lead',
        description: 'Can manage team members and team-specific resources',
        icon: Crown,
        color: 'bg-orange-100 text-orange-800',
    },
};

export default function RolePermissionManager({
    type,
    entityId,
    roles,
    onUpdatePermissions,
}: RolePermissionManagerProps) {
    const toast = useToast();
    const [editingRole, setEditingRole] = useState<string | null>(null);
    const [updating, setUpdating] = useState(false);
    const [tempPermissions, setTempPermissions] = useState<Record<string, boolean>>({});
    const [availablePermissions, setAvailablePermissions] = useState<Permission[]>([]);
    const [loadingPermissions, setLoadingPermissions] = useState(true);

    // Fetch available permissions on component mount
    useEffect(() => {
        const fetchPermissions = async () => {
            try {
                setLoadingPermissions(true);
                if (type === 'team') {
                    const response = await fetch('/teams/available-permissions', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    // Convert backend permission format to frontend format
                    const permissions: Permission[] = Object.entries(data.permissions).map(([key, value]: [string, any]) => ({
                        key,
                        label: value.label,
                        description: value.description,
                        icon: PERMISSION_ICONS[key] || Settings,
                    }));
                    
                    setAvailablePermissions(permissions);
                } else {
                    // For organization permissions, use the same endpoint but with organization scope
                    const response = await fetch('/teams/available-permissions?scope=organization', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    
                    if (!response.ok) {
                        // Fallback to static permissions if endpoint fails
                        const orgPermissions: Permission[] = [
                            {
                                key: 'manage_organization',
                                label: 'Manage Organization',
                                description: 'Can update organization settings, branding, and general configuration',
                                icon: Settings,
                            },
                            {
                                key: 'manage_users',
                                label: 'Manage Users',
                                description: 'Can invite, remove, and manage user roles and permissions',
                                icon: Users,
                            },
                            {
                                key: 'manage_teams',
                                label: 'Manage Teams',
                                description: 'Can create, edit, and delete teams and assign team members',
                                icon: Shield,
                            },
                            {
                                key: 'manage_services',
                                label: 'Manage Services',
                                description: 'Can create, edit, and delete services and update their status',
                                icon: Settings,
                            },
                            {
                                key: 'manage_incidents',
                                label: 'Manage Incidents',
                                description: 'Can create, update, and resolve incidents',
                                icon: AlertTriangle,
                            },
                            {
                                key: 'manage_maintenance',
                                label: 'Manage Maintenance',
                                description: 'Can schedule and manage maintenance windows',
                                icon: Wrench,
                            },
                            {
                                key: 'view_analytics',
                                label: 'View Analytics',
                                description: 'Can access analytics and reporting features',
                                icon: BarChart3,
                            },
                        ];
                        setAvailablePermissions(orgPermissions);
                    } else {
                        const data = await response.json();
                        
                        // Convert backend permission format to frontend format
                        const permissions: Permission[] = Object.entries(data.permissions).map(([key, value]: [string, any]) => ({
                            key,
                            label: value.label,
                            description: value.description,
                            icon: PERMISSION_ICONS[key] || Settings,
                        }));
                        
                        setAvailablePermissions(permissions);
                    }
                }
            } catch (error) {
                console.error('Failed to fetch permissions:', error);
                const errorMessage = error instanceof Error ? error.message : 'Unknown error';
                toast.error(`Failed to load permissions: ${errorMessage}`);
            } finally {
                setLoadingPermissions(false);
            }
        };

        fetchPermissions();
    }, [type]); // Removed 'toast' from dependencies to prevent infinite loop

    const handleEditRole = (role: string, currentPermissions: Record<string, boolean>) => {
        setEditingRole(role);
        
        // If current permissions are empty, we'll let the backend handle defaults
        // The backend will merge with default permissions when saving
        setTempPermissions({ ...currentPermissions });
    };

    const handleSavePermissions = async () => {
        if (!editingRole) return;

        setUpdating(true);
        try {
            await onUpdatePermissions(editingRole, tempPermissions);
            setEditingRole(null);
            setTempPermissions({});
        } catch (error) {
            toast.error('Failed to update role permissions. Please try again.');
            throw error;
        } finally {
            setUpdating(false);
        }
    };

    const handleCancelEdit = () => {
        setEditingRole(null);
        setTempPermissions({});
    };

    const togglePermission = (permissionKey: string) => {
        setTempPermissions(prev => ({
            ...prev,
            [permissionKey]: !prev[permissionKey],
        }));
    };

    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-lg font-semibold">Role Permissions</h3>
                <p className="text-sm text-muted-foreground">
                    Manage permissions for different roles in this {type}.
                </p>
            </div>

            <div className="grid gap-4">
                {roles.map(({ role, permissions, usersCount }) => {
                    const roleInfo = ROLE_INFO[role] || {
                        label: role.charAt(0).toUpperCase() + role.slice(1),
                        description: `Role: ${role}`,
                        icon: Users,
                        color: 'bg-gray-100 text-gray-800',
                    };
                    const isEditing = editingRole === role;

                    return (
                        <Card key={role}>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center space-x-3">
                                        <roleInfo.icon className="h-5 w-5" />
                                        <div>
                                            <CardTitle className="text-base">{roleInfo.label}</CardTitle>
                                            <CardDescription>{roleInfo.description}</CardDescription>
                                        </div>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Badge variant="outline">{usersCount} users</Badge>
                                        {!isEditing && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleEditRole(role, permissions)}
                                            >
                                                Edit Permissions
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {isEditing ? (
                                    <div className="space-y-4">
                                        <div className="grid gap-3">
                                            {loadingPermissions ? (
                                                <div>Loading permissions...</div>
                                            ) : (
                                                availablePermissions.map((permission) => {
                                                    const Icon = permission.icon;
                                                    return (
                                                        <div key={permission.key} className="flex items-start space-x-3">
                                                            <Checkbox
                                                                id={`${role}-${permission.key}`}
                                                                checked={tempPermissions[permission.key] || false}
                                                                onCheckedChange={() => togglePermission(permission.key)}
                                                            />
                                                            <div className="flex-1">
                                                                <Label
                                                                    htmlFor={`${role}-${permission.key}`}
                                                                    className="flex items-center space-x-2 cursor-pointer"
                                                                >
                                                                    <Icon className="h-4 w-4" />
                                                                    <span className="font-medium">{permission.label}</span>
                                                                </Label>
                                                                <p className="text-sm text-muted-foreground mt-1">
                                                                    {permission.description}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    );
                                                })
                                            )}
                                        </div>
                                        <Separator />
                                        <div className="flex justify-end space-x-2">
                                            <Button variant="outline" onClick={handleCancelEdit}>
                                                Cancel
                                            </Button>
                                            <Button onClick={handleSavePermissions} disabled={updating}>
                                                {updating ? 'Saving...' : 'Save Changes'}
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="grid gap-2">
                                        {loadingPermissions ? (
                                            <div className="text-sm text-muted-foreground">Loading permissions...</div>
                                        ) : (
                                            availablePermissions.map((permission) => {
                                                const Icon = permission.icon;
                                                const hasPermission = permissions[permission.key];
                                                return (
                                                    <div key={permission.key} className="flex items-center space-x-2">
                                                        <Icon className={`h-4 w-4 ${hasPermission ? 'text-green-500' : 'text-gray-400'}`} />
                                                        <span className={`text-sm ${hasPermission ? 'text-foreground' : 'text-muted-foreground'}`}>
                                                            {permission.label}
                                                        </span>
                                                        {hasPermission && (
                                                            <Badge variant="secondary" className="text-xs">
                                                                Enabled
                                                            </Badge>
                                                        )}
                                                    </div>
                                                );
                                            })
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        </div>
    );
} 