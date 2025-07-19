import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Service } from '@/types/service';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import React from 'react';
import { useToast } from '@/hooks/use-toast';

const statusOptions = [
    { value: 'investigating', label: 'Investigating' },
    { value: 'identified', label: 'Identified' },
    { value: 'monitoring', label: 'Monitoring' },
    { value: 'resolved', label: 'Resolved' },
];

const severityOptions = [
    { value: 'low', label: 'Low', description: 'Minor issues with minimal impact' },
    { value: 'medium', label: 'Medium', description: 'Moderate issues affecting some users' },
    { value: 'high', label: 'High', description: 'Major issues affecting many users' },
    { value: 'critical', label: 'Critical', description: 'System-wide outage or critical failure' },
];

interface Props {
    services: {
        data: Service[];
    };
}

export default function IncidentCreate({ services }: PageProps<Props>) {
    const toast = useToast();
    
    const { data, setData, post, processing, errors } = useForm({
        service_ids: [] as number[],
        title: '',
        description: '',
        status: 'investigating',
        severity: 'medium',
    });

    const handleServiceToggle = (serviceId: number, checked: boolean) => {
        if (checked) {
            setData('service_ids', [...data.service_ids, serviceId]);
        } else {
            setData('service_ids', data.service_ids.filter(id => id !== serviceId));
        }
    };

    const selectedServices = services.data.filter(service => 
        data.service_ids.includes(service.id)
    );

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/incidents', {
            onSuccess: () => {
                toast.success('Incident created successfully!');
            },
            onError: () => {
                toast.error('Failed to create incident. Please try again.');
            },
        });
    }

    const breadcrumbs = [
        {
            title: 'Incidents',
            href: '/incidents',
        },
        {
            title: 'Create',
            href: '/incidents/create',
        },
    ];

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Report Incident" />
                <div className="flex flex-col gap-6 p-6 max-w-2xl mx-auto w-full">
                    <div>
                        <h1 className="text-3xl font-bold">Report Incident</h1>
                        <p className="text-muted-foreground mt-2">Create a new incident report for service issues</p>
                    </div>

                    <Card className="p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Basic Information */}
                            <div className="space-y-4">
                                <h2 className="text-lg font-semibold">Incident Details</h2>
                                
                                <div className="grid gap-2">
                                    <Label htmlFor="title">Incident Title *</Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        placeholder="e.g., API Service Experiencing Slow Response Times"
                                    />
                                    {errors.title && <p className="text-sm text-red-500">{errors.title}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Input
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Provide details about the incident, impact, and any known causes"
                                    />
                                    {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                                </div>
                            </div>

                            {/* Affected Services */}
                            <div className="space-y-4">
                                <div>
                                    <h2 className="text-lg font-semibold">Affected Services *</h2>
                                    <p className="text-sm text-muted-foreground">Select all services impacted by this incident</p>
                                </div>
                                
                                {services.data.length === 0 ? (
                                    <p className="text-sm text-muted-foreground py-4">No services available. Please create services first.</p>
                                ) : (
                                    <div className="space-y-3">
                                        {services.data.map((service) => (
                                            <div key={service.id} className="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50">
                                                <Checkbox
                                                    id={`service-${service.id}`}
                                                    checked={data.service_ids.includes(service.id)}
                                                    onCheckedChange={(checked: boolean) => handleServiceToggle(service.id, checked)}
                                                />
                                                <div className="flex-1">
                                                    <Label htmlFor={`service-${service.id}`} className="cursor-pointer">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-medium">{service.name}</span>
                                                            {service.team && (
                                                                <Badge variant="secondary" className="flex items-center gap-1">
                                                                    <div 
                                                                        className="w-2 h-2 rounded-full" 
                                                                        style={{ backgroundColor: service.team.color || '#64748b' }}
                                                                    />
                                                                    {service.team.name}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        {service.description && (
                                                            <p className="text-sm text-muted-foreground mt-1">{service.description}</p>
                                                        )}
                                                    </Label>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                
                                {selectedServices.length > 0 && (
                                    <div className="mt-3">
                                        <p className="text-sm font-medium mb-2">Selected services ({selectedServices.length}):</p>
                                        <div className="flex flex-wrap gap-2">
                                            {selectedServices.map((service) => (
                                                <Badge key={service.id} variant="outline" className="flex items-center gap-1">
                                                    {service.team && (
                                                        <div 
                                                            className="w-2 h-2 rounded-full" 
                                                            style={{ backgroundColor: service.team.color || '#64748b' }}
                                                        />
                                                    )}
                                                    {service.name}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                )}
                                
                                {errors.service_ids && <p className="text-sm text-red-500">{errors.service_ids}</p>}
                            </div>

                            {/* Status and Severity */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status *</Label>
                                    <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                        <SelectTrigger id="status">
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {statusOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.status && <p className="text-sm text-red-500">{errors.status}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="severity">Severity *</Label>
                                    <Select value={data.severity} onValueChange={(value) => setData('severity', value)}>
                                        <SelectTrigger id="severity">
                                            <SelectValue placeholder="Select severity" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {severityOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    <div>
                                                        <div className="font-medium">{option.label}</div>
                                                        <div className="text-sm text-muted-foreground">{option.description}</div>
                                                    </div>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.severity && <p className="text-sm text-red-500">{errors.severity}</p>}
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex gap-3 pt-4">
                                <Button 
                                    type="submit" 
                                    disabled={processing || data.service_ids.length === 0}
                                >
                                    {processing ? 'Creating...' : 'Create Incident'}
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
