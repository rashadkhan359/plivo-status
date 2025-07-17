import { DeleteDialog } from '@/components/delete-dialog';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import React from 'react';

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
    };
}

export default function MaintenanceIndex({ maintenances }: PageProps<Props>) {
    console.log(maintenances);
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
            onFinish: () => {
                setDeleting(false);
                setDeleteDialogOpen(false);
                setMaintenanceToDelete(null);
            },
        });
    };

    return (
        <>
            <AppLayout>
                <div className="p-5">
                    <Head title="Maintenances" />
                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Maintenances</h1>
                        <Link href="/maintenances/create" target="_blank">
                            <Button size="sm" variant="outline" className="flex items-center">
                                Add Maintenance
                            </Button>
                        </Link>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {maintenances.data.map((maintenance) => (
                            <Card key={maintenance.id} className="flex flex-col gap-2 p-4">
                                <div className="flex items-center justify-between">
                                    <span className="font-semibold">{maintenance.title}</span>
                                    <span className={`badge badge-${maintenance.status}`}>{maintenance.status.replace('_', ' ')}</span>
                                </div>
                                <div className="text-sm text-gray-500">
                                    {maintenance.scheduled_start} - {maintenance.scheduled_end}
                                </div>
                                <div className="mt-2 flex gap-2">
                                    <Link href={`/maintenances/${maintenance.id}/edit`} target="_blank">
                                        <Button size="sm" variant="outline" className="flex items-center">
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        className="flex items-center"
                                        onClick={() => handleDeleteClick(maintenance)}
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </Card>
                        ))}
                    </div>
                </div>
                <DeleteDialog
                    open={deleteDialogOpen}
                    onOpenChange={(open) => {
                        setDeleteDialogOpen(open);
                        if (!open) setMaintenanceToDelete(null);
                    }}
                    onConfirm={handleConfirmDelete}
                    itemType="maintenance"
                    itemName={maintenanceToDelete?.title}
                    loading={deleting}
                />
            </AppLayout>
        </>
    );
}
