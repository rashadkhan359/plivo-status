import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, UserPlus, Mail, Crown, Shield } from 'lucide-react';
import { FormEventHandler } from 'react';
import { useToast } from '@/hooks/use-toast';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import SettingsLayout from '@/layouts/settings/layout';
import { Organization } from '@/types/organization';
import AppLayout from '@/layouts/app-layout';

interface InviteMemberProps {
    organization: Organization;
}

type InviteForm = {
    name: string;
    email: string;
    role: string;
    message: string;
};

export default function InviteMember({ organization }: InviteMemberProps) {
    const toast = useToast();
    const { data, setData, post, processing, errors, reset } = useForm<InviteForm>({
        name: '',
        email: '',
        role: 'member',
        message: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('organization.invite.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Invitation sent successfully!');
                reset();
            },
            onError: () => {
                toast.error('Failed to send invitation. Please try again.');
            },
        });
    };
    const breadcrumbs = [
        { title: 'Settings', href: route('profile.edit') },
        { title: 'Organization', href: route('organization.edit') },
        { title: 'Team', href: route('organization.team') },
        { title: 'Invite Member', href: route('organization.invite') },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Invite Team Member" />
            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">Invite Team Member</h1>
                            <p className="text-muted-foreground">
                                Invite a new member to join {organization.name}.
                            </p>
                        </div>
                        <Button asChild variant="outline">
                            <a href={route('organization.team')}>
                                Back to Team
                            </a>
                        </Button>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <UserPlus className="h-5 w-5" />
                                Invite New Member
                            </CardTitle>
                            <CardDescription>
                                Send an invitation to join your organization. They will receive an email to set up their account.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Full Name</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            disabled={processing}
                                            placeholder="Enter full name"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="email">Email Address</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            disabled={processing}
                                            placeholder="Enter email address"
                                        />
                                        <InputError message={errors.email} />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="role">Role</Label>
                                    <Select
                                        value={data.role}
                                        onValueChange={(value) => setData('role', value)}
                                        disabled={processing}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="member">
                                                <div className="flex items-center gap-2">
                                                    <Shield className="h-4 w-4" />
                                                    Member
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="admin">
                                                <div className="flex items-center gap-2">
                                                    <Crown className="h-4 w-4" />
                                                    Admin
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.role} />
                                    <p className="text-xs text-muted-foreground">
                                        Members can view and create incidents, update service status, and create maintenance schedules.
                                        Admins have full access including team management and organization settings.
                                    </p>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="message">Personal Message (Optional)</Label>
                                    <Textarea
                                        id="message"
                                        value={data.message}
                                        onChange={(e) => setData('message', e.target.value)}
                                        disabled={processing}
                                        placeholder="Add a personal message to the invitation..."
                                        rows={3}
                                    />
                                    <InputError message={errors.message} />
                                    <p className="text-xs text-muted-foreground">
                                        This message will be included in the invitation email.
                                    </p>
                                </div>

                                <div className="flex justify-end space-x-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => reset()}
                                        disabled={processing}
                                    >
                                        Reset
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                                        <Mail className="h-4 w-4 mr-2" />
                                        Send Invitation
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>What happens next?</CardTitle>
                            <CardDescription>
                                Here's what will happen when you send an invitation.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="flex items-start space-x-3">
                                    <div className="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium">
                                        1
                                    </div>
                                    <div>
                                        <h4 className="font-medium">Invitation Email Sent</h4>
                                        <p className="text-sm text-muted-foreground">
                                            The person will receive an email with a link to join your organization.
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start space-x-3">
                                    <div className="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium">
                                        2
                                    </div>
                                    <div>
                                        <h4 className="font-medium">Account Setup</h4>
                                        <p className="text-sm text-muted-foreground">
                                            They'll click the link and set up their password to activate their account.
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start space-x-3">
                                    <div className="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium">
                                        3
                                    </div>
                                    <div>
                                        <h4 className="font-medium">Access Granted</h4>
                                        <p className="text-sm text-muted-foreground">
                                            Once activated, they'll have access to your organization based on their assigned role.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
} 