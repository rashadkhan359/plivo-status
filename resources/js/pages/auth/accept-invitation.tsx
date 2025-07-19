import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Mail, Users, Shield, Crown } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface AcceptInvitationProps {
    invitation: {
        id: number;
        token: string;
        email: string;
        name: string;
        role: string;
        message?: string;
        organization: {
            id: number;
            name: string;
        };
        invited_by: {
            name: string;
        };
        expires_at: string;
    };
}

export default function AcceptInvitation({ invitation }: AcceptInvitationProps) {
    const { data, setData, post, processing, errors } = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('invitation.accept', invitation.token));
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
                return <Badge variant="default" className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Admin</Badge>;
            case 'member':
                return <Badge variant="secondary">Member</Badge>;
            default:
                return <Badge variant="outline">{role}</Badge>;
        }
    };

    const expiresAt = new Date(invitation.expires_at);

    return (
        <div className="min-h-screen flex items-center justify-center bg-background py-12 px-4 sm:px-6 lg:px-8">
            <Head title="Accept Invitation" />
            
            <div className="max-w-md w-full space-y-8">
                <div className="text-center">
                    <h2 className="text-3xl font-bold text-foreground">Accept Invitation</h2>
                    <p className="text-muted-foreground mt-2">
                        Join {invitation.organization.name} on StatusPage
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Mail className="h-5 w-5" />
                            Invitation Details
                        </CardTitle>
                        <CardDescription>
                            You've been invited to join {invitation.organization.name}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label>Organization</Label>
                            <div className="text-sm font-medium text-foreground">{invitation.organization.name}</div>
                        </div>
                        
                        <div className="space-y-2">
                            <Label>Invited by</Label>
                            <div className="text-sm font-medium text-foreground">{invitation.invited_by.name}</div>
                        </div>
                        
                        <div className="space-y-2">
                            <Label>Your role</Label>
                            <div className="flex items-center gap-2">
                                {getRoleIcon(invitation.role)}
                                {getRoleBadge(invitation.role)}
                            </div>
                        </div>
                        
                        {invitation.message && (
                            <div className="space-y-2">
                                <Label>Personal message</Label>
                                <div className="text-sm text-muted-foreground bg-muted p-3 rounded-md border">
                                    "{invitation.message}"
                                </div>
                            </div>
                        )}
                        
                        <div className="space-y-2">
                            <Label>Expires</Label>
                            <div className="text-sm text-muted-foreground">
                                {expiresAt.toLocaleDateString()} at {expiresAt.toLocaleTimeString()}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Set Your Password</CardTitle>
                        <CardDescription>
                            Create a password to complete your account setup
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="email">Email Address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={invitation.email}
                                    disabled
                                    className="bg-muted"
                                />
                                <p className="text-xs text-muted-foreground">
                                    This is the email address you were invited with
                                </p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    disabled={processing}
                                    required
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="password_confirmation">Confirm Password</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    disabled={processing}
                                    required
                                />
                                <InputError message={errors.password_confirmation} />
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                                Accept Invitation & Join Organization
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <div className="text-center">
                    <p className="text-xs text-muted-foreground">
                        By accepting this invitation, you agree to join {invitation.organization.name} 
                        and will have access to their status page management tools.
                    </p>
                </div>
            </div>
        </div>
    );
} 