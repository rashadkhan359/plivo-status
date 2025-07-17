import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Head } from '@inertiajs/react';
import type { Organization, User } from '@/types/models';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface OrganizationShowPageProps extends PageProps { // eslint-disable-line
    organization: {
        data: Organization;
    };
}

export default function OrganizationShowPage({ organization }: OrganizationShowPageProps) {
    console.log(organization.data);
    return (
        <AppSidebarLayout>
            <Head title={`Admin: ${organization.data.name}`} />

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{organization.data.name}</CardTitle>
                        <CardDescription>Created on {organization.data.created_at}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">Slug: {organization.data.slug}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Users</CardTitle>
                        <CardDescription>A list of users belonging to this organization.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Joined</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {organization.data.users?.map((user: User) => (
                                    <TableRow key={user.id}>
                                        <TableCell>{user.name}</TableCell>
                                        <TableCell>{user.email}</TableCell>
                                        <TableCell>{user.role}</TableCell>
                                        <TableCell>{user.created_at}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppSidebarLayout>
    );
}
