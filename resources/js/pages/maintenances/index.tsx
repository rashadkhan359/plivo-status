import { DeleteDialog } from '@/components/delete-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Clock, Edit, Plus, Trash2 } from 'lucide-react';
import React from 'react';
import { useToast } from '@/hooks/use-toast';

interface Maintenance {
    id: number;
    title: string;
    status: string;
    scheduled_start: string;
    scheduled_end: string;
}

interface Props {
    maintenances: {
        data: Maintenance[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}

const statusColors = {
    scheduled: 'text-blue-600 bg-blue-50 border-blue-200',
    in_progress: 'text-orange-600 bg-orange-50 border-orange-200',
    completed: 'text-green-600 bg-green-50 border-green-200',
};

export default function MaintenanceIndex({ maintenances }: PageProps<Props>) {
    const toast = useToast();
    
    const [deleteDialogOpen, setDeleteDialogOpen] = React.useState(false);
    const [maintenanceToDelete, setMaintenanceToDelete] = React.useState<Maintenance | null>(null);
    const [deleting, setDeleting] = React.useState(false);

    const handleDeleteClick = (maintenance: Maintenance) => {
        setMaintenanceToDelete(maintenance);
        setDeleteDialogOpen(true);
    };

    const handleConfirmDelete = async () => {
        if (!maintenanceToDelete) return;
        setDeleting(true);
        router.delete(`/maintenances/${maintenanceToDelete.id}`, {
            onSuccess: () => {
                toast.success('Maintenance deleted successfully!');
            },
            onError: () => {
                toast.error('Failed to delete maintenance. Please try again.');
            },
            onFinish: () => {
                setDeleting(false);
                setDeleteDialogOpen(false);
                setMaintenanceToDelete(null);
            },
        });
    };

    const breadcrumbs = [
        { title: 'Maintenance', href: '/maintenances' },
    ];

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
                    <Head title="Maintenance" />
                    
                    {/* Page Header */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold">Maintenance</h1>
                            <p className="text-muted-foreground mt-2">Schedule and manage planned maintenance windows</p>
                        </div>
                        <Link href="/maintenances/create">
                            <Button className="flex items-center gap-2">
                                <Plus className="h-4 w-4" />
                                Schedule Maintenance
                            </Button>
                        </Link>
                    </div>

                    {/* Content */}
                    {maintenances.data.length === 0 ? (
                        <div className="text-center py-12">
                            <Calendar className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <h3 className="text-lg font-semibold mb-2">No maintenance scheduled</h3>
                            <p className="text-muted-foreground mb-6">Keep your users informed about planned service interruptions</p>
                            <Link href="/maintenances/create">
                                <Button>Schedule maintenance</Button>
                            </Link>
                        </div>
                    ) : (
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {maintenances.data.map((maintenance) => (
                                <Card key={maintenance.id} className="flex flex-col p-6 hover:shadow-md transition-shadow duration-200">
                                    <CardHeader className="p-0 mb-4">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1 min-w-0">
                                                <CardTitle className="text-lg truncate">{maintenance.title}</CardTitle>
                                            </div>
                                            <span
                                                className={`inline-flex items-center px-2 py-1 rounded-md text-xs font-medium capitalize border ${
                                                    statusColors[maintenance.status as keyof typeof statusColors] || 'text-gray-600 bg-gray-50 border-gray-200'
                                                }`}
                                            >
                                                {maintenance.status.replace('_', ' ')}
                                            </span>
                                        </div>
                                    </CardHeader>

                                    <CardContent className="p-0 flex-1">
                                        <div className="space-y-3">
                                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <Clock className="h-4 w-4" />
                                                <div>
                                                    <div>Start: {new Date(maintenance.scheduled_start).toLocaleString()}</div>
                                                    <div>End: {new Date(maintenance.scheduled_end).toLocaleString()}</div>
                                                </div>
                                            </div>
                                            
                                            <div className="text-xs text-muted-foreground">
                                                Duration: {Math.round((new Date(maintenance.scheduled_end).getTime() - new Date(maintenance.scheduled_start).getTime()) / (1000 * 60))} minutes
                                            </div>
                                        </div>
                                    </CardContent>

                                    <div className="flex gap-2 mt-4 pt-4 border-t">
                                        <Link href={`/maintenances/${maintenance.id}/edit`} className="flex-1">
                                            <Button size="sm" variant="outline" className="w-full flex items-center gap-2">
                                                <Edit className="h-4 w-4" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button 
                                            size="sm" 
                                            variant="outline" 
                                            onClick={() => handleDeleteClick(maintenance)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </Card>
                            ))}
                        </div>
                    )}

                    {/* Pagination */}
                    {maintenances.data.length > 0 && maintenances.last_page > 1 && (
                        <div className="flex items-center justify-between mt-6">
                            <div className="text-sm text-muted-foreground">
                                Showing {((maintenances.current_page - 1) * maintenances.per_page) + 1} to{' '}
                                {Math.min(maintenances.current_page * maintenances.per_page, maintenances.total)} of{' '}
                                {maintenances.total} results
                            </div>
                            <div className="flex items-center space-x-2">
                                {maintenances.links.map((link, index) => (
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

                    {/* Delete Dialog */}
                    <DeleteDialog
                        open={deleteDialogOpen}
                        onOpenChange={setDeleteDialogOpen}
                        onConfirm={handleConfirmDelete}
                        loading={deleting}
                        itemType="maintenance"
                        itemName={maintenanceToDelete?.title}
                    />
                </div>
            </AppLayout>
        </>
    );
}
