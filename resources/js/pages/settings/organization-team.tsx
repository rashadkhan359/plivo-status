import { Head, useForm, router, Link } from '@inertiajs/react';
import { Users, UserPlus, Crown, Shield, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';
import { useToast } from '@/hooks/use-toast';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import SettingsLayout from '@/layouts/settings/layout';
import { Organization } from '@/types/organization';
import { User } from '@/types/models';
import AppLayout from '@/layouts/app-layout';
import RolePermissionManager from '@/components/role-permission-manager';
import { usePermissions } from '@/hooks/use-permissions';

interface OrganizationTeamProps {
    organization: Organization;
    members: {
        data: User[];
    };
    currentUser: User;
    rolePermissions: Record<string, Record<string, boolean>>;
}

export default function OrganizationTeam({
    organization,
    members,
    currentUser,
    rolePermissions
}: OrganizationTeamProps) {
    const toast = useToast();
    const permissions = usePermissions();
    
    // Debug: Check permissions
    console.log('Current User Role:', currentUser.role);
    console.log('Organization Permissions:', permissions.permissions.organization);
    console.log('Can Manage Organization:', permissions.canManageOrganization());
    
    const [inviteDialogOpen, setInviteDialogOpen] = useState(false);
    const [removeDialogOpen, setRemoveDialogOpen] = useState(false);
    const [memberToRemove, setMemberToRemove] = useState<User | null>(null);
    const [updatingRole, setUpdatingRole] = useState<number | null>(null);

    const inviteForm = useForm({
        name: '',
        email: '',
        role: 'member',
        message: '',
    });

    const handleInviteSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        inviteForm.post(route('organization.invite.store'), {
            onSuccess: () => {
                toast.success('Invitation sent successfully!');
                setInviteDialogOpen(false);
                inviteForm.reset();
            },
            onError: () => {
                toast.error('Failed to send invitation. Please try again.');
            },
        });
    };

    const handleRemoveMember = (member: User) => {
        setMemberToRemove(member);
        setRemoveDialogOpen(true);
    };

    const confirmRemoveMember = () => {
        if (!memberToRemove) return;
        
        router.delete(route('organization.team.remove'), {
            data: { user_id: memberToRemove.id },
            onSuccess: () => {
                toast.success('Member removed from organization successfully!');
                setRemoveDialogOpen(false);
                setMemberToRemove(null);
            },
            onError: () => {
                toast.error('Failed to remove member from organization. Please try again.');
            },
        });
    };

    const updateRole = (userId: number, role: string) => {
        setUpdatingRole(userId);
        router.patch(route('organization.team.role'), {
            user_id: userId,
            role,
        }, {
            onSuccess: () => {
                toast.success('Member role updated successfully!');
                setUpdatingRole(null);
            },
            onError: () => {
                toast.error('Failed to update member role. Please try again.');
                setUpdatingRole(null);
            },
            preserveScroll: true,
        });
    };

    const getRoleBadge = (role: string) => {
        switch (role) {
            case 'owner':
                return <Badge variant="success">Owner</Badge>;
            case 'admin':
                return <Badge variant="warning">Admin</Badge>;
            case 'member':
                return <Badge variant="secondary">Member</Badge>;
            default:
                return <Badge variant="outline">{role}</Badge>;
        }
    };

    const breadcrumbs = [
        { title: 'Settings', href: route('profile.edit') },
        { title: 'Organization', href: route('organization.edit') },
        { title: 'Team', href: route('organization.team') },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Team Management" />
            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">Team Management</h1>
                            <p className="text-muted-foreground">
                                Manage team members and their roles in {organization.name}.
                            </p>
                        </div>
                        <Button asChild>
                            <Link href={route('organization.invite')}>
                                <UserPlus className="h-4 w-4 mr-2" />
                                Invite Member
                            </Link>
                        </Button>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                Team Members ({members.data.length})
                            </CardTitle>
                            <CardDescription>
                                Manage roles and permissions for team members.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {members.data.map((member: User) => (
                                    <div key={member.id} className="flex items-center justify-between p-4 border rounded-lg">
                                        <div className="flex items-center space-x-3">
                                            <Avatar>
                                                <AvatarFallback>
                                                    {member.name.split(' ').map((n: string) => n[0]).join('').toUpperCase()}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div className="font-medium">{member.name}</div>
                                                <div className="text-sm text-muted-foreground">{member.email}</div>
                                            </div>
                                        </div>

                                        <div className="flex items-center space-x-3">
                                            {getRoleBadge(member.role)}

                                            {currentUser.id !== member.id && (
                                                <div className="flex items-center space-x-2">
                                                    <Select
                                                        value={member.role}
                                                        onValueChange={(value) => updateRole(member.id, value)}
                                                        disabled={updatingRole === member.id}
                                                    >
                                                        <SelectTrigger className="w-32">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="member">Member</SelectItem>
                                                            <SelectItem value="admin">Admin</SelectItem>
                                                            <SelectItem value="owner">Owner</SelectItem>
                                                        </SelectContent>
                                                    </Select>

                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleRemoveMember(member)}
                                                        disabled={updatingRole === member.id}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            )}

                                            {currentUser.id === member.id && (
                                                <Badge variant="outline">You</Badge>
                                            )}
                                        </div>
                                    </div>
                                ))}

                                {members.data.length === 0 && (
                                    <div className="text-center py-8 text-muted-foreground">
                                        <Users className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                        <p>No team members found.</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {true && (
                        <RolePermissionManager
                        type="organization"
                        entityId={organization.id}
                        roles={[
                            {
                                role: 'owner',
                                permissions: rolePermissions.owner || {},
                                usersCount: members.data.filter((m: User) => m.role === 'owner').length,
                            },
                            {
                                role: 'admin',
                                permissions: rolePermissions.admin || {},
                                usersCount: members.data.filter((m: User) => m.role === 'admin').length,
                            },
                            {
                                role: 'team_lead',
                                permissions: rolePermissions.team_lead || {},
                                usersCount: members.data.filter((m: User) => m.role === 'team_lead').length,
                            },
                            {
                                role: 'member',
                                permissions: rolePermissions.member || {},
                                usersCount: members.data.filter((m: User) => m.role === 'member').length,
                            },
                        ]}
                        onUpdatePermissions={async (role, permissions) => {
                            try {
                                await router.patch(route('organization.permissions'), {
                                    role,
                                    permissions,
                                });
                                toast.success('Role permissions updated successfully!');
                            } catch (error) {
                                toast.error('Failed to update role permissions. Please try again.');
                                throw error;
                            }
                        }}
                        />
                    )}

                    {/* Remove Member Dialog */}
                    <Dialog open={removeDialogOpen} onOpenChange={setRemoveDialogOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Remove Team Member</DialogTitle>
                                <DialogDescription>
                                    Are you sure you want to remove {memberToRemove?.name} from the organization? 
                                    This action cannot be undone.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="flex justify-end space-x-2">
                                <Button
                                    variant="outline"
                                    onClick={() => setRemoveDialogOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    variant="destructive"
                                    onClick={confirmRemoveMember}
                                >
                                    Remove Member
                                </Button>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
} 