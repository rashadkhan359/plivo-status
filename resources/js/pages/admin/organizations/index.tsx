import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Head, router, Link } from '@inertiajs/react';
import type { Organization } from '@/types/models';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Search, Users, Building2, AlertTriangle, Wrench, TrendingUp } from 'lucide-react';
import { useState } from 'react';
import { useDebounce } from '@/hooks/use-debounce';

interface Stats {
    total_organizations: number;
    total_users: number;
    total_services: number;
    total_incidents: number;
    total_maintenances: number;
    active_incidents: number;
    scheduled_maintenances: number;
}

interface ChartData {
    organizations: Record<string, number>;
    users: Record<string, number>;
    incidents: Record<string, number>;
}

interface OrganizationIndexPageProps extends PageProps {
    organizations: {
        data: Organization[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    stats: Stats;
    chartData: ChartData;
    filters: {
        search?: string;
    };
}

export default function OrganizationIndexPage({ 
    organizations, 
    stats, 
    chartData, 
    filters 
}: OrganizationIndexPageProps) {
    const [search, setSearch] = useState(filters.search || '');
    const debouncedSearch = useDebounce(search, 300);

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get(route('admin.organizations.index'), { search: value }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleRowClick = (organizationId: number) => {
        router.get(route('admin.organizations.show', organizationId));
    };

    const formatNumber = (num: number) => {
        return new Intl.NumberFormat().format(num);
    };

    const breadcrumbs = [
        { title: 'Admin', href: route('dashboard') },
        { title: 'Organizations', href: route('admin.organizations.index') },
    ];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin: Organizations" />

            <div className="space-y-6 p-6">
                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Organizations</CardTitle>
                            <Building2 className="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatNumber(stats.total_organizations)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
                            <Users className="h-4 w-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatNumber(stats.total_users)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Incidents</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatNumber(stats.active_incidents)}</div>
                            <p className="text-xs text-muted-foreground">
                                of {formatNumber(stats.total_incidents)} total
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Scheduled Maintenance</CardTitle>
                            <Wrench className="h-4 w-4 text-yellow-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatNumber(stats.scheduled_maintenances)}</div>
                            <p className="text-xs text-muted-foreground">
                                of {formatNumber(stats.total_maintenances)} total
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Search and Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Organizations</CardTitle>
                        <CardDescription>Manage and monitor all organizations in the system.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center space-x-2 mb-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search organizations..."
                                    value={search}
                                    onChange={(e) => handleSearch(e.target.value)}
                                    className="pl-8"
                                />
                            </div>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Slug</TableHead>
                                    <TableHead>Users</TableHead>
                                    <TableHead>Services</TableHead>
                                    <TableHead>Incidents</TableHead>
                                    <TableHead>Created</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {organizations.data.map((organization) => (
                                    <TableRow
                                        key={organization.id}
                                        onClick={() => handleRowClick(organization.id)}
                                        className="cursor-pointer hover:bg-muted/50"
                                    >
                                        <TableCell className="font-medium">{organization.name}</TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">{organization.slug}</Badge>
                                        </TableCell>
                                        <TableCell>{organization.users_count}</TableCell>
                                        <TableCell>{organization.services_count}</TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-1">
                                                <span>{organization.incidents_count}</span>
                                                {organization.incidents_count > 0 && (
                                                    <Badge variant="destructive" className="text-xs">
                                                        {organization.incidents_count}
                                                    </Badge>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>{new Date(organization.created_at).toLocaleDateString()}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {/* Pagination */}
                        {organizations.last_page > 1 && (
                            <div className="flex items-center justify-between mt-4">
                                <div className="text-sm text-muted-foreground">
                                    Showing {((organizations.current_page - 1) * organizations.per_page) + 1} to{' '}
                                    {Math.min(organizations.current_page * organizations.per_page, organizations.total)} of{' '}
                                    {organizations.total} results
                                </div>
                                <div className="flex items-center space-x-2">
                                    {organizations.links.map((link, index) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? "default" : "outline"}
                                            size="sm"
                                            onClick={() => {
                                                if (link.url) {
                                                    router.get(link.url);
                                                }
                                            }}
                                            disabled={!link.url}
                                        >
                                            {link.label.replace('&laquo;', '«').replace('&raquo;', '»')}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppSidebarLayout>
    );
}

