import { Head, useForm, router, Link } from '@inertiajs/react';
import { Users, UserPlus, Crown, Shield, Trash2, Edit } from 'lucide-react';
import { FormEventHandler } from 'react';
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

interface OrganizationTeamProps {
    organization: Organization;
    members: User[] | any; // Allow any type for debugging
    currentUser: User;
}

export default function OrganizationTeam({
    organization,
    members,
    currentUser
}: OrganizationTeamProps) {
    useToast(); // Initialize toast notifications
    const { patch, delete: destroy, processing } = useForm();

    // Debug: Log the members data structure
    console.log('Members data:', members.data.length);
    console.log('Members type:', typeof members);
    console.log('Is array:', Array.isArray(members));


    const updateRole = (userId: number, role: string) => {
        router.patch(route('organization.team.role'), {
            user_id: userId,
            role,
        }, {
            preserveScroll: true,
        });
    };

    const removeMember = (userId: number) => {
        if (confirm('Are you sure you want to remove this member from the organization?')) {
            router.delete(route('organization.team.remove'), {
                data: { user_id: userId },
                preserveScroll: true,
            });
        }
    };

    const getRoleIcon = (role: string) => {
        switch (role) {
            case 'admin':
                return <Crown className="h-4 w-4 text-yellow-500" />;
            case 'member':
                return <Shield className="h-4 w-4 text-blue-500" />;
            default:
                return <Users className="h-4 w-4 text-gray-500" />;
        }
    };

    const getRoleBadge = (role: string) => {
        switch (role) {
            case 'admin':
                return <Badge variant="default" className="bg-yellow-100 text-yellow-800">Admin</Badge>;
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
                                                        disabled={processing}
                                                    >
                                                        <SelectTrigger className="w-32">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="member">Member</SelectItem>
                                                            <SelectItem value="admin">Admin</SelectItem>
                                                        </SelectContent>
                                                    </Select>

                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => removeMember(member.id)}
                                                        disabled={processing}
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

                    <Card>
                        <CardHeader>
                            <CardTitle>Role Permissions</CardTitle>
                            <CardDescription>
                                Understanding of different roles and their permissions.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <Crown className="h-4 w-4 text-yellow-500" />
                                        <span className="font-medium">Admin</span>
                                    </div>
                                    <ul className="text-sm text-muted-foreground space-y-1 ml-6">
                                        <li>• Full access to all features</li>
                                        <li>• Can manage team members</li>
                                        <li>• Can update organization settings</li>
                                        <li>• Can delete services and incidents</li>
                                    </ul>
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <Shield className="h-4 w-4 text-blue-500" />
                                        <span className="font-medium">Member</span>
                                    </div>
                                    <ul className="text-sm text-muted-foreground space-y-1 ml-6">
                                        <li>• Can view and create incidents</li>
                                        <li>• Can update service status</li>
                                        <li>• Can create maintenance schedules</li>
                                        <li>• Limited access to settings</li>
                                    </ul>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
} 