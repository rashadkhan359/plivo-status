import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ServiceSelector } from '@/components/service-selector';
import React from 'react';
import AppLayout from '@/layouts/app-layout';
import { useToast } from '@/hooks/use-toast';
import { Service } from '@/types/service';

const colorOptions = [
    { value: '#ef4444', label: 'Red' },
    { value: '#f97316', label: 'Orange' },
    { value: '#eab308', label: 'Yellow' },
    { value: '#22c55e', label: 'Green' },
    { value: '#06b6d4', label: 'Cyan' },
    { value: '#3b82f6', label: 'Blue' },
    { value: '#8b5cf6', label: 'Violet' },
    { value: '#ec4899', label: 'Pink' },
    { value: '#64748b', label: 'Gray' },
];

interface TeamCreateProps {
    availableServices: Service[];
}

export default function TeamCreate({ availableServices }: TeamCreateProps) {
    const toast = useToast();
    
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        color: '#3b82f6',
        service_ids: [] as number[],
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('teams.store'), {
            onSuccess: () => {
                toast.success('Team created successfully!');
            },
            onError: () => {
                toast.error('Failed to create team. Please try again.');
            },
        });
    }

    const handleServiceSelectionChange = (services: Service[]) => {
        setData('service_ids', services.map(service => service.id));
    };

    const selectedServices = availableServices.filter(service => 
        data.service_ids.includes(service.id)
    );

    const breadcrumbs = [
        {
            title: 'Teams',
            href: '/teams',
        },
        {
            title: 'Create',
            href: '/teams/create',
        },
    ];

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Create Team" />
                <div className="flex flex-col gap-6 p-6 max-w-2xl mx-auto w-full">
                    <div>
                        <h1 className="text-3xl font-bold">Create Team</h1>
                        <p className="text-muted-foreground mt-2">Create a new team to organize users and manage services</p>
                    </div>

                    <Card className="p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-4">
                                <h2 className="text-lg font-semibold">Team Details</h2>
                                
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Team Name *</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                                        placeholder="e.g., Frontend Team, Backend Engineers"
                                    />
                                    {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('description', e.target.value)}
                                        placeholder="Brief description of the team's responsibilities"
                                        rows={3}
                                    />
                                    {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="color">Team Color</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {colorOptions.map((color) => (
                                            <button
                                                key={color.value}
                                                type="button"
                                                onClick={() => setData('color', color.value)}
                                                className={`w-8 h-8 rounded-full border-2 ${
                                                    data.color === color.value 
                                                        ? 'border-gray-900 ring-2 ring-gray-300' 
                                                        : 'border-gray-300 hover:border-gray-400'
                                                }`}
                                                style={{ backgroundColor: color.value }}
                                                title={color.label}
                                            />
                                        ))}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Choose a color to help identify this team throughout the system
                                    </p>
                                    {errors.color && <p className="text-sm text-red-500">{errors.color}</p>}
                                </div>
                            </div>

                            <div className="space-y-4">
                                <h2 className="text-lg font-semibold">Team Services</h2>
                                <div className="grid gap-2">
                                    <Label>Services to Manage (Optional)</Label>
                                    <ServiceSelector
                                        services={availableServices}
                                        selectedServices={selectedServices}
                                        onSelectionChange={handleServiceSelectionChange}
                                        placeholder="Select services this team will manage..."
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        Select services that this team will be responsible for managing. You can add or remove services later.
                                    </p>
                                    {errors.service_ids && <p className="text-sm text-red-500">{errors.service_ids}</p>}
                                </div>
                            </div>

                            <div className="flex gap-3 pt-4">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating...' : 'Create Team'}
                                </Button>
                                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </Card>
                </div>
            </AppLayout>
        </>
    );
} 