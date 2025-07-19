import React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Team } from '@/types/service';
import { User } from '@/types';
import { useForm, router } from '@inertiajs/react';
import { Users, UserPlus, Trash2, Crown, Shield } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';

interface TeamMemberManagementProps {
    team: Team & {
        members: (User & { pivot: { role: 'member' | 'lead' } })[];
    };
    availableUsers: User[];
    canManageMembers: boolean;
}

export function TeamMemberManagement({ team, availableUsers, canManageMembers }: TeamMemberManagementProps) {
    const toast = useToast();
    
    const [addMemberDialogOpen, setAddMemberDialogOpen] = React.useState(false);
    const [removeMemberDialogOpen, setRemoveMemberDialogOpen] = React.useState(false);
    const [memberToRemove, setMemberToRemove] = React.useState<(User & { pivot: { role: 'member' | 'lead' } }) | null>(null);

    const addMemberForm = useForm({
        user_id: '',
        role: 'member' as 'member' | 'lead',
    });

    const handleAddMember = (e: React.FormEvent) => {
        e.preventDefault();
        addMemberForm.post(`/teams/${team.id}/members`, {
            onSuccess: () => {
                toast.success('Member added to team successfully!');
                setAddMemberDialogOpen(false);
                addMemberForm.reset();
            },
            onError: () => {
                toast.error('Failed to add member to team. Please try again.');
            },
        });
    };

    const handleRemoveMember = (member: User & { pivot: { role: 'member' | 'lead' } }) => {
        setMemberToRemove(member);
        setRemoveMemberDialogOpen(true);
    };

    const confirmRemoveMember = () => {
        if (!memberToRemove) return;
        
        router.delete(`/teams/${team.id}/members/${memberToRemove.id}`, {
            onSuccess: () => {
                toast.success('Member removed from team successfully!');
                setRemoveMemberDialogOpen(false);
                setMemberToRemove(null);
            },
            onError: () => {
                toast.error('Failed to remove member from team. Please try again.');
            },
        });
    };

    const handleUpdateRole = (userId: number, role: 'member' | 'lead') => {
        router.patch(`/teams/${team.id}/members/${userId}/role`, {
            data: { role },
            onSuccess: () => {
                toast.success('Member role updated successfully!');
            },
            onError: () => {
                toast.error('Failed to update member role. Please try again.');
            },
        });
    };

    const getRoleBadge = (role: string) => {
        switch (role) {
            case 'lead':
                return <Badge variant="default" className="bg-orange-100 text-orange-800">Lead</Badge>;
            case 'member':
                return <Badge variant="secondary">Member</Badge>;
            default:
                return <Badge variant="outline">{role}</Badge>;
        }
    };

    const getRoleIcon = (role: string) => {
        switch (role) {
            case 'lead':
                return <Crown className="h-4 w-4" />;
            case 'member':
                return <Users className="h-4 w-4" />;
            default:
                return <Users className="h-4 w-4" />;
        }
    };

    const filteredAvailableUsers = availableUsers.filter(
        user => !team.members.find(member => member.id === user.id)
    );

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            Team Members ({team.members.length})
                        </CardTitle>
                        <CardDescription>
                            People who are part of this team.
                        </CardDescription>
                    </div>
                    {canManageMembers && (
                        <Dialog open={addMemberDialogOpen} onOpenChange={setAddMemberDialogOpen}>
                            <DialogTrigger asChild>
                                <Button size="sm" className="flex items-center gap-2">
                                    <UserPlus className="h-4 w-4" />
                                    Add Member
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-[425px]">
                                <DialogHeader>
                                    <DialogTitle>Add Team Member</DialogTitle>
                                    <DialogDescription>
                                        Add a new member to the {team.name} team.
                                    </DialogDescription>
                                </DialogHeader>
                                <form onSubmit={handleAddMember} className="space-y-4">
                                    <div>
                                        <Label htmlFor="user">Select User</Label>
                                        <Select
                                            value={addMemberForm.data.user_id}
                                            onValueChange={value => addMemberForm.setData('user_id', value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Choose a user" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {filteredAvailableUsers.length > 0 ? (
                                                    filteredAvailableUsers.map((user) => (
                                                        <SelectItem key={user.id} value={user.id.toString()}>
                                                            {user.name} ({user.email})
                                                        </SelectItem>
                                                    ))
                                                ) : (
                                                    <div className="px-2 py-1 text-sm text-muted-foreground">
                                                        No available users to add
                                                    </div>
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label htmlFor="role">Role</Label>
                                        <Select
                                            value={addMemberForm.data.role}
                                            onValueChange={value => addMemberForm.setData('role', value as 'member' | 'lead')}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="member">Member</SelectItem>
                                                <SelectItem value="lead">Lead</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="flex justify-end gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setAddMemberDialogOpen(false)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button 
                                            type="submit" 
                                            disabled={addMemberForm.processing || !addMemberForm.data.user_id}
                                        >
                                            {addMemberForm.processing ? 'Adding...' : 'Add Member'}
                                        </Button>
                                    </div>
                                </form>
                            </DialogContent>
                        </Dialog>
                    )}
                </div>
            </CardHeader>
            <CardContent>
                <div className="space-y-3">
                    {team.members.length > 0 ? (
                        team.members.map((member) => (
                            <div key={member.id} className="flex items-center justify-between p-3 border rounded-lg">
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
                                
                                <div className="flex items-center space-x-2">
                                    {canManageMembers ? (
                                        <>
                                            <Select
                                                value={member.pivot?.role || 'member'}
                                                onValueChange={(value) => handleUpdateRole(member.id, value as 'member' | 'lead')}
                                            >
                                                <SelectTrigger className="w-24">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="member">Member</SelectItem>
                                                    <SelectItem value="lead">Lead</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => handleRemoveMember(member)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </>
                                    ) : (
                                        <div className="flex items-center gap-2">
                                            {getRoleIcon(member.pivot?.role || 'member')}
                                            {getRoleBadge(member.pivot?.role || 'member')}
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="text-center py-8 text-muted-foreground">
                            <Users className="h-12 w-12 mx-auto mb-4 opacity-50" />
                            <p>No members in this team.</p>
                            {canManageMembers && (
                                <Button 
                                    variant="outline" 
                                    className="mt-2"
                                    onClick={() => setAddMemberDialogOpen(true)}
                                >
                                    <UserPlus className="h-4 w-4 mr-2" />
                                    Add First Member
                                </Button>
                            )}
                        </div>
                    )}
                </div>
            </CardContent>

            {/* Remove Member Confirmation Dialog */}
            <Dialog open={removeMemberDialogOpen} onOpenChange={setRemoveMemberDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove Team Member</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to remove {memberToRemove?.name} from the {team.name} team?
                            This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setRemoveMemberDialogOpen(false)}
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
        </Card>
    );
} 