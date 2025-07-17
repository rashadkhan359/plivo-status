import { Button, Card, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui';
import { Service } from '@/types/service';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

const statusOptions = [
    { value: 'investigating', label: 'Investigating' },
    { value: 'identified', label: 'Identified' },
    { value: 'monitoring', label: 'Monitoring' },
    { value: 'resolved', label: 'Resolved' },
];
const severityOptions = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
    { value: 'critical', label: 'Critical' },
];

interface Props {
    services: {
        data: Service[];
    };
}

export default function IncidentCreate({ services }: PageProps<Props>) {
    const { data, setData, post, processing, errors } = useForm({
        service_id: '',
        title: '',
        description: '',
        status: 'investigating',
        severity: 'low',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/incidents');
    }

    return (
        <>
            <Head title="Add Incident" />
            <Card className="mx-auto mt-8 max-w-lg p-6">
                <h1 className="mb-4 text-xl font-bold">Add Incident</h1>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <Label htmlFor="service_id">Service</Label>
                    <Select value={data.service_id} onValueChange={(value) => setData('service_id', value)}>
                        <SelectTrigger id="service_id">
                            <SelectValue placeholder="Select service" />
                        </SelectTrigger>
                        <SelectContent>
                            {services.data.map((service) => (
                                <SelectItem key={service.id} value={String(service.id)}>
                                    {service.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <div>
                        <Label htmlFor="title">Title</  Label>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} />
                        {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                    </div>
                    <div>
                        <Label htmlFor="description">Description</Label>
                        <Input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        {errors.description && <p className="text-xs text-red-500">{errors.description}</p>}
                    </div>
                    <div>
                        <Label htmlFor="status">Status</Label>
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
                    </div>
                    <div>
                        <Label htmlFor="severity">Severity</Label>
                        <Select value={data.severity} onValueChange={(value) => setData('severity', value)}>
                            <SelectTrigger id="severity">
                                <SelectValue placeholder="Select severity" />
                            </SelectTrigger>
                            <SelectContent>
                                {severityOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Adding...' : 'Add Incident'}
                    </Button>
                </form>
            </Card>
        </>
    );
}
