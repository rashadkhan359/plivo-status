import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Head, router } from '@inertiajs/react';
import type { Organization } from '@/types/models';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface OrganizationIndexPageProps extends PageProps { // eslint-disable-line
    organizations: {
        data: Organization[];
    };
}

export default function OrganizationIndexPage({ organizations: { data: organizationsList } }: OrganizationIndexPageProps) {
    const handleRowClick = (organizationId: number) => {
        router.get(route('admin.organizations.show', organizationId));
    };

    return (
        <AppSidebarLayout>
            <Head title="Admin: Organizations" />

            <Card>
                <CardHeader>
                    <CardTitle>Organizations</CardTitle>
                    <CardDescription>A list of all organizations in the system.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Users</TableHead>
                                <TableHead>Created At</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {organizationsList.map((organization) => (
                                <TableRow
                                    key={organization.id}
                                    onClick={() => handleRowClick(organization.id)}
                                    className="cursor-pointer"
                                >
                                    <TableCell className="font-medium">{organization.name}</TableCell>
                                    <TableCell>{organization.slug}</TableCell>
                                    <TableCell>{organization.users_count}</TableCell>
                                    <TableCell>{organization.created_at}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AppSidebarLayout>
    );
}

