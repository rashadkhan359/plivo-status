import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Head, Link } from '@inertiajs/react';
import type { Organization } from '@/types/models';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, Users, Settings, AlertTriangle, Wrench, TrendingUp, Calendar } from 'lucide-react';

interface Stats {
    services_by_status: Record<string, number>;
    incidents_by_status: Record<string, number>;
    incidents_by_severity: Record<string, number>;
    users_by_role: Record<string, number>;
}

interface OrganizationShowPageProps extends PageProps {
    organization: { data: Organization };
    stats: Stats;
}

export default function OrganizationShowPage({ organization, stats }: OrganizationShowPageProps) {
    const breadcrumbs = [
        { title: 'Admin', href: route('admin.organizations.index') },
        { title: 'Organizations', href: route('admin.organizations.index') },
    ];

    const org = organization.data;
    const formatNumber = (num: number) => {
        return new Intl.NumberFormat().format(num);
    };

    const getStatusColor = (status: string) => {
        const colors: Record<string, string> = {
            operational: 'bg-green-100 text-green-800',
            degraded: 'bg-yellow-100 text-yellow-800',
            partial_outage: 'bg-orange-100 text-orange-800',
            major_outage: 'bg-red-100 text-red-800',
            investigating: 'bg-blue-100 text-blue-800',
            identified: 'bg-purple-100 text-purple-800',
            monitoring: 'bg-indigo-100 text-indigo-800',
            resolved: 'bg-gray-100 text-gray-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getSeverityColor = (severity: string) => {
        const colors: Record<string, string> = {
            low: 'bg-green-100 text-green-800',
            medium: 'bg-yellow-100 text-yellow-800',
            high: 'bg-orange-100 text-orange-800',
            critical: 'bg-red-100 text-red-800',
        };
        return colors[severity] || 'bg-gray-100 text-gray-800';
    };

    console.log(org.users);

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title={`Admin: ${org.name}`} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div className="flex items-center gap-2">
                        <Link href={route('admin.organizations.index')}>
                            <Button variant="ghost" size="icon" className="mr-2">
                                <ArrowLeft className="h-5 w-5" />
                            </Button>
                        </Link>
                        <h1 className="text-2xl font-semibold">{org.name}</h1>
                    </div>
                    <p className="text-sm text-muted-foreground md:ml-4">Organization Details &amp; Analytics</p>
                </div>

                {/* Organization Info */}
                <Card>
                    <CardHeader>
                        <CardTitle>Organization Information</CardTitle>
                        <CardDescription>Basic details about the organization</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm font-medium text-muted-foreground">Name</label>
                                <p className="text-sm font-medium">{org.name}</p>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-muted-foreground">Slug</label>
                                <Badge variant="secondary">{org.slug}</Badge>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-muted-foreground">Domain</label>
                                <p className="text-sm">{org.domain || 'Not set'}</p>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-muted-foreground">Created</label>
                                <p className="text-sm">{new Date(org.created_at).toLocaleDateString()}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Overview Statistics */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center space-x-2">
                                <Users className="h-5 w-5 text-blue-500" />
                                <div>
                                    <p className="text-2xl font-bold">{formatNumber(org.users_count || 0)}</p>
                                    <p className="text-sm text-muted-foreground">Users</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center space-x-2">
                                <Settings className="h-5 w-5 text-green-500" />
                                <div>
                                    <p className="text-2xl font-bold">{formatNumber(org.services_count || 0)}</p>
                                    <p className="text-sm text-muted-foreground">Services</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center space-x-2">
                                <AlertTriangle className="h-5 w-5 text-red-500" />
                                <div>
                                    <p className="text-2xl font-bold">{formatNumber(org.incidents_count || 0)}</p>
                                    <p className="text-sm text-muted-foreground">Incidents</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center space-x-2">
                                <Wrench className="h-5 w-5 text-orange-500" />
                                <div>
                                    <p className="text-2xl font-bold">{formatNumber(org.maintenances_count || 0)}</p>
                                    <p className="text-sm text-muted-foreground">Maintenance</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Detailed Statistics */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Services by Status */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Services by Status</CardTitle>
                            <CardDescription>Distribution of services across different statuses</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {Object.entries(stats.services_by_status || {}).map(([status, count]) => (
                                    <div key={status} className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            <Badge className={getStatusColor(status)}>
                                                {status.replace('_', ' ')}
                                            </Badge>
                                        </div>
                                        <span className="font-medium">{count}</span>
                                    </div>
                                ))}
                                {Object.keys(stats.services_by_status || {}).length === 0 && (
                                    <p className="text-sm text-muted-foreground">No services found</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Incidents by Status */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Incidents by Status</CardTitle>
                            <CardDescription>Distribution of incidents across different statuses</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {Object.entries(stats.incidents_by_status || {}).map(([status, count]) => (
                                    <div key={status} className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            <Badge className={getStatusColor(status)}>
                                                {status}
                                            </Badge>
                                        </div>
                                        <span className="font-medium">{count}</span>
                                    </div>
                                ))}
                                {Object.keys(stats.incidents_by_status || {}).length === 0 && (
                                    <p className="text-sm text-muted-foreground">No incidents found</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Incidents by Severity */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Incidents by Severity</CardTitle>
                            <CardDescription>Distribution of incidents by severity level</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {Object.entries(stats.incidents_by_severity || {}).map(([severity, count]) => (
                                    <div key={severity} className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            <Badge className={getSeverityColor(severity)}>
                                                {severity}
                                            </Badge>
                                        </div>
                                        <span className="font-medium">{count}</span>
                                    </div>
                                ))}
                                {Object.keys(stats.incidents_by_severity || {}).length === 0 && (
                                    <p className="text-sm text-muted-foreground">No incidents found</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Users by Role */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Users by Role</CardTitle>
                            <CardDescription>Distribution of users across different roles</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {Object.entries(stats.users_by_role || {}).map(([role, count]) => (
                                    <div key={role} className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            <Badge variant="outline">
                                                {role.replace('_', ' ')}
                                            </Badge>
                                        </div>
                                        <span className="font-medium">{count}</span>
                                    </div>
                                ))}
                                {Object.keys(stats.users_by_role || {}).length === 0 && (
                                    <p className="text-sm text-muted-foreground">No users found</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Activity */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Recent Incidents */}
                    {org.incidents && org.incidents.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Recent Incidents</CardTitle>
                                <CardDescription>Latest incidents in this organization</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {org.incidents.slice(0, 5).map((incident: any) => (
                                        <div key={incident.id} className="flex items-center justify-between p-3 border rounded-lg">
                                            <div>
                                                <p className="font-medium text-sm">{incident.title}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {new Date(incident.created_at).toLocaleDateString()}
                                                </p>
                                            </div>
                                            <Badge className={getStatusColor(incident.status)}>
                                                {incident.status}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Recent Maintenance */}
                    {org.maintenances && org.maintenances.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Recent Maintenance</CardTitle>
                                <CardDescription>Latest maintenance windows</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {org.maintenances.slice(0, 5).map((maintenance: any) => (
                                        <div key={maintenance.id} className="flex items-center justify-between p-3 border rounded-lg">
                                            <div>
                                                <p className="font-medium text-sm">{maintenance.title}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {new Date(maintenance.scheduled_start).toLocaleDateString()}
                                                </p>
                                            </div>
                                            <Badge variant="outline">
                                                {maintenance.status}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Users Table */}
                {org.users && Array.isArray(org.users) && org.users.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Users ({org.users.length})</CardTitle>
                            <CardDescription>Members of this organization</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>User</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Joined</TableHead>
                                        <TableHead>Email</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {org.users.map((user: any) => (
                                        <TableRow key={user.id}>
                                            <TableCell>
                                                <div className="flex items-center space-x-3">
                                                    <div className="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                                        <span className="text-sm font-medium">
                                                            {user.name.split(' ').map((n: string) => n[0]).join('').toUpperCase()}
                                                        </span>
                                                    </div>
                                                    <span className="font-medium">{user.name}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{user.role}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                {user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">{user.email}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppSidebarLayout>
    );
}
