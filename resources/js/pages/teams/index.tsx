import { DeleteDialog } from '@/components/delete-dialog';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { Team } from '@/types/service';
import { Head, Link, router } from '@inertiajs/react';
import { Edit, Trash2, Plus, Users, Settings, UserPlus } from 'lucide-react';
import React from 'react';

interface Props {
    teams: Team[];
    canCreate: boolean;
}

export default function TeamIndex({ teams, canCreate }: PageProps<Props>) {
    const [deleteDialogOpen, setDeleteDialogOpen] = React.useState(false);
    const [teamToDelete, setTeamToDelete] = React.useState<Team | null>(null);
    const [deleting, setDeleting] = React.useState(false);

    const handleDeleteClick = (team: Team) => {
        setTeamToDelete(team);
        setDeleteDialogOpen(true);
    };

    const handleConfirmDelete = async () => {
        if (!teamToDelete) return;
        setDeleting(true);
        router.delete(`/teams/${teamToDelete.id}`, {
            onFinish: () => {
                setDeleting(false);
                setDeleteDialogOpen(false);
                setTeamToDelete(null);
            },
        });
    };

    const breadcrumbs = [
        {
            title: 'Teams',
            href: '/teams',
        },
    ];

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
                    <Head title="Teams" />
                    
                    {/* Page Header */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold">Teams</h1>
                            <p className="text-muted-foreground mt-2">Organize users and manage service responsibilities</p>
                        </div>
                        {canCreate && (
                            <Link href="/teams/create">
                                <Button className="flex items-center gap-2">
                                    <Plus className="h-4 w-4" />
                                    Create Team
                                </Button>
                            </Link>
                        )}
                    </div>

                    {/* Content */}
                    {teams.length === 0 ? (
                        <div className="text-center py-12">
                            <Users className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <h3 className="text-lg font-semibold mb-2">No teams found</h3>
                            <p className="text-muted-foreground mb-6">Create teams to organize users and manage service responsibilities</p>
                            {canCreate && (
                                <Link href="/teams/create">
                                    <Button>Create your first team</Button>
                                </Link>
                            )}
                        </div>
                    ) : (
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {teams.map((team) => (
                                <Card key={team.id} className="flex flex-col p-6 hover:shadow-md transition-shadow duration-200">
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 mb-2">
                                                <div 
                                                    className="w-3 h-3 rounded-full flex-shrink-0" 
                                                    style={{ backgroundColor: team.color || '#64748b' }}
                                                />
                                                <h3 className="font-semibold text-lg truncate">{team.name}</h3>
                                            </div>
                                            <p className="text-sm text-muted-foreground mb-3 line-clamp-2">
                                                {team.description || 'No description provided'}
                                            </p>
                                            
                                            {/* Team Stats */}
                                            <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                                <div className="flex items-center gap-1">
                                                    <Users className="h-4 w-4" />
                                                    <span>{team.members?.length || 0} members</span>
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    <Settings className="h-4 w-4" />
                                                    <span>{team.services?.length || 0} services</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Team Members Preview */}
                                    {team.members && team.members.length > 0 && (
                                        <div className="mb-4">
                                            <p className="text-xs text-muted-foreground mb-2">Members</p>
                                            <div className="flex flex-wrap gap-1">
                                                {team.members.slice(0, 3).map((member) => (
                                                    <Badge 
                                                        key={member.id} 
                                                        variant={member.pivot.role === 'lead' ? 'default' : 'secondary'}
                                                        className="text-xs"
                                                    >
                                                        {member.name}
                                                        {member.pivot.role === 'lead' && ' (Lead)'}
                                                    </Badge>
                                                ))}
                                                {team.members.length > 3 && (
                                                    <Badge variant="outline" className="text-xs">
                                                        +{team.members.length - 3} more
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                    
                                    <div className="flex gap-2 mt-auto pt-4">
                                        <Link href={`/teams/${team.id}`} className="flex-1">
                                            <Button size="sm" variant="outline" className="w-full flex items-center gap-2">
                                                <Users className="h-4 w-4" />
                                                View
                                            </Button>
                                        </Link>
                                        <Link href={`/teams/${team.id}/edit`}>
                                            <Button size="sm" variant="outline">
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button 
                                            size="sm" 
                                            variant="outline" 
                                            onClick={() => handleDeleteClick(team)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </Card>
                            ))}
                        </div>
                    )}

                    {/* Delete Dialog */}
                    <DeleteDialog
                        open={deleteDialogOpen}
                        onOpenChange={setDeleteDialogOpen}
                        onConfirm={handleConfirmDelete}
                        loading={deleting}
                        itemType="team"
                        itemName={teamToDelete?.name}
                    />
                </div>
            </AppLayout>
        </>
    );
} 