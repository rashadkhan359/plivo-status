import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle, ExternalLink, Users, Settings, Building, UserPlus } from 'lucide-react';
import { FormEventHandler } from 'react';
import { useToast } from '@/hooks/use-toast';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import SettingsLayout from '@/layouts/settings/layout';
import { Organization } from '@/types/organization';
import AppLayout from '@/layouts/app-layout';

interface OrganizationSettingsProps {
    organization: {
        data: Organization;
    };
    canUpdate: boolean;
    canDelete: boolean;
    statusPageUrl: string;
}

type OrganizationForm = {
    name: string;
    slug: string;
    domain: string | null;
};

export default function OrganizationSettings({
    organization,
    canUpdate,
    canDelete,
    statusPageUrl
}: OrganizationSettingsProps) {
    const toast = useToast();
    const { data, setData, patch, processing, errors, reset } = useForm<OrganizationForm>({
        name: organization.data.name,
        slug: organization.data.slug,
        domain: organization.data.domain || '',
    });

    console.log(organization.data.name);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('organization.update'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Organization updated successfully!');
            },
            onError: () => {
                toast.error('Failed to update organization. Please try again.');
            },
        });
    };

    const breadcrumbs = [
        { title: 'Settings', href: route('profile.edit') },
        { title: 'Organization', href: route('organization.edit') },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organization Settings" />
            <SettingsLayout>
                <div className="space-y-6">
                    <div>
                        <h1 className="text-2xl font-bold">Organization Settings</h1>
                        <p className="text-muted-foreground">
                            Manage your organization's information and settings.
                        </p>
                    </div>

                    {/* Organization Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building className="h-5 w-5" />
                                Organization Information
                            </CardTitle>
                            <CardDescription>
                                Update your organization's basic information and settings.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Organization Name</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            disabled={!canUpdate || processing}
                                            placeholder="Your organization name"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="slug">URL Slug</Label>
                                        <div className="flex">
                                            <span className="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                                /status/
                                            </span>
                                            <Input
                                                id="slug"
                                                type="text"
                                                value={data.slug}
                                                onChange={(e) => setData('slug', e.target.value.toLowerCase().replace(/[^a-z0-9\-]/g, ''))}
                                                disabled={!canUpdate || processing}
                                                placeholder="organization-slug"
                                                className="rounded-l-none"
                                            />
                                        </div>
                                        <InputError message={errors.slug} />
                                        <p className="text-xs text-muted-foreground">
                                            Only lowercase letters, numbers, and hyphens are allowed.
                                        </p>
                                    </div>

                                    <div className="space-y-2 md:col-span-2">
                                        <Label htmlFor="domain">Custom Domain (Optional)</Label>
                                        <Input
                                            id="domain"
                                            type="text"
                                            value={data.domain || ''}
                                            onChange={(e) => setData('domain', e.target.value || null)}
                                            disabled={!canUpdate || processing}
                                            placeholder="status.yourcompany.com"
                                        />
                                        <InputError message={errors.domain} />
                                        <p className="text-xs text-muted-foreground">
                                            Set up a custom domain for your status page. Contact support for DNS configuration help.
                                        </p>
                                    </div>
                                </div>

                                {canUpdate && (
                                    <div className="flex justify-end">
                                        <Button type="submit" disabled={processing}>
                                            {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                                            Update Organization
                                        </Button>
                                    </div>
                                )}
                            </form>
                        </CardContent>
                    </Card>

                    {/* Status Page Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <ExternalLink className="h-5 w-5" />
                                Public Status Page
                            </CardTitle>
                            <CardDescription>
                                Your public status page where customers can view service status.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div>
                                    <Label>Status Page URL</Label>
                                    <div className="mt-1 flex items-center space-x-2">
                                        <Input
                                            value={statusPageUrl}
                                            readOnly
                                            className="flex-1"
                                        />
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => window.open(statusPageUrl, '_blank')}
                                        >
                                            <ExternalLink className="h-4 w-4" />
                                            View
                                        </Button>
                                    </div>
                                </div>

                                {organization.data.domain && (
                                    <div>
                                        <Label>Custom Domain URL</Label>
                                        <div className="mt-1 flex items-center space-x-2">
                                            <Input
                                                value={`https://${organization.data.domain}`}
                                                readOnly
                                                className="flex-1"
                                            />
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => window.open(`https://${organization.data.domain}`, '_blank')}
                                            >
                                                <ExternalLink className="h-4 w-4" />
                                                View
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Organization Stats */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Organization Overview</CardTitle>
                            <CardDescription>
                                Quick overview of your organization's data.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div className="text-center">
                                    <div className="text-2xl font-bold">{organization.data.users_count || 0}</div>
                                    <div className="text-sm text-muted-foreground">Team Members</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold">{organization.data.services_count || 0}</div>
                                    <div className="text-sm text-muted-foreground">Services</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold">{organization.data.incidents_count || 0}</div>
                                    <div className="text-sm text-muted-foreground">Incidents</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold">{organization.data.maintenances_count || 0}</div>
                                    <div className="text-sm text-muted-foreground">Maintenances</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Team Management Link */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                Team Management
                            </CardTitle>
                            <CardDescription>
                                Manage team members, roles, and permissions.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex space-x-2">
                                <Button asChild variant="outline">
                                    <Link href={route('organization.team')}>
                                        <Users className="h-4 w-4 mr-2" />
                                        Manage Team
                                    </Link>
                                </Button>
                                <Button asChild>
                                    <Link href={route('organization.invite')}>
                                        <UserPlus className="h-4 w-4 mr-2" />
                                        Invite Member
                                    </Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href="/teams">
                                        <Settings className="h-4 w-4 mr-2" />
                                        Manage Teams
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
} 