import { Head, Link } from '@inertiajs/react';
import { Settings, Edit, Users } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { Team, Service } from '@/types/service';
import { User } from '@/types';
import { TeamMemberManagement } from '@/components/team-member-management';
import React from 'react';

interface TeamShowProps {
    team: Team & {
        members: User[];
        services: Service[];
    };
    canManageMembers: boolean;
    availableUsers: User[];
}

export default function TeamShow({ team, canManageMembers, availableUsers }: TeamShowProps) {
    const breadcrumbs = [
        { title: 'Settings', href: route('profile.edit') },
        { title: 'Organization', href: route('organization.edit') },
        { title: 'Team', href: route('organization.team') },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Team - ${team.name}`} />
            
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{team.name}</h1>
                        <p className="text-muted-foreground">
                            {team.description || 'No description provided.'}
                        </p>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Button asChild variant="outline">
                            <Link href={route('organization.team')}>
                                <Users className="h-4 w-4 mr-2" />
                                Organization Team
                            </Link>
                        </Button>
                        {canManageMembers && (
                            <Button asChild variant="outline">
                                <Link href={route('teams.edit', team.id)}>
                                    <Edit className="h-4 w-4 mr-2" />
                                    Edit Team
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Team Members */}
                    <TeamMemberManagement 
                        team={team}
                        availableUsers={availableUsers}
                        canManageMembers={canManageMembers}
                    />

                    {/* Team Services */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Settings className="h-5 w-5" />
                                Team Services ({team.services.length})
                            </CardTitle>
                            <CardDescription>
                                Services managed by this team.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {team.services.map((service) => (
                                    <div key={service.id} className="flex items-center justify-between p-3 border rounded-lg">
                                        <div>
                                            <div className="font-medium">{service.name}</div>
                                            <div className="text-sm text-muted-foreground">
                                                {service.description || 'No description'}
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center space-x-2">
                                            <Badge variant={service.status === 'operational' ? 'default' : 'destructive'}>
                                                {service.status}
                                            </Badge>
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={route('services.edit', service.id)}>
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                                
                                {team.services.length === 0 && (
                                    <div className="text-center py-8 text-muted-foreground">
                                        <Settings className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                        <p>No services assigned to this team.</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Team Stats */}
                <Card>
                    <CardHeader>
                        <CardTitle>Team Overview</CardTitle>
                        <CardDescription>
                            Quick overview of team statistics.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div className="text-center">
                                <div className="text-2xl font-bold">{team.members.length}</div>
                                <div className="text-sm text-muted-foreground">Members</div>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold">{team.services.length}</div>
                                <div className="text-sm text-muted-foreground">Services</div>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold">
                                    {team.services.filter(s => s.status === 'operational').length}
                                </div>
                                <div className="text-sm text-muted-foreground">Operational</div>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold">
                                    {team.services.filter(s => s.status !== 'operational').length}
                                </div>
                                <div className="text-sm text-muted-foreground">Issues</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
} 