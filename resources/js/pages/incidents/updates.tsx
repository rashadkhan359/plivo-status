import { Head, useForm } from '@inertiajs/react';
import { AlertTriangle, CheckCircle, Info, ShieldAlert, Zap, Clock, MessageSquare, Send } from 'lucide-react';
import { FormEventHandler } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import InputError from '@/components/input-error';
import { IncidentTimeline } from '@/components/incident-timeline';
import { IncidentUpdate } from '@/types/incident-update';
import { useToast } from '@/hooks/use-toast';

type IncidentStatus = 'investigating' | 'identified' | 'monitoring' | 'resolved';

interface Incident {
    id: number;
    title: string;
    description: string;
    status: IncidentStatus;
    severity: string;
    created_at: string;
    service?: {
        id: number;
        name: string;
    };
}

interface Props {
    incident: Incident;
    updates: { data: IncidentUpdate[] };
}

const statusIcons: Record<IncidentStatus, { icon: React.ElementType; color: string; label: string }> = {
    investigating: { icon: Info, color: 'text-blue-500', label: 'Investigating' },
    identified: { icon: ShieldAlert, color: 'text-yellow-500', label: 'Identified' },
    monitoring: { icon: Zap, color: 'text-purple-500', label: 'Monitoring' },
    resolved: { icon: CheckCircle, color: 'text-green-500', label: 'Resolved' },
};

const statusOptions = [
    { value: 'investigating', label: 'Investigating', description: 'We are investigating the issue' },
    { value: 'identified', label: 'Identified', description: 'We have identified the problem' },
    { value: 'monitoring', label: 'Monitoring', description: 'We are monitoring the fix' },
    { value: 'resolved', label: 'Resolved', description: 'The issue has been resolved' },
];

type UpdateForm = {
    message: string;
    status: IncidentStatus;
};

export default function IncidentUpdates({ incident, updates }: PageProps<Props>) {
    const toast = useToast();
    
    const { data, setData, post, processing, errors, reset } = useForm<UpdateForm>({
        message: '',
        status: incident.status,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('incidents.updates.store', incident.id), {
            onSuccess: () => {
                toast.success('Update posted successfully!');
                reset('message');
            },
            onError: (errors) => {
                toast.error('Failed to post update. Please try again.');
            },
        });
    };

    const breadcrumbs = [
        { title: 'Incidents', href: '/incidents' },
        { title: incident.title, href: `/incidents/${incident.id}/updates` },
    ];

    const StatusIcon = statusIcons[incident.status]?.icon || AlertTriangle;
    const statusColor = statusIcons[incident.status]?.color || 'text-gray-500';

    const severityColors = {
        low: 'text-blue-600 bg-blue-50 border-blue-200',
        medium: 'text-yellow-600 bg-yellow-50 border-yellow-200',
        high: 'text-orange-600 bg-orange-50 border-orange-200',
        critical: 'text-red-600 bg-red-50 border-red-200',
    };
    const severityColor = severityColors[incident.severity as keyof typeof severityColors] || 'text-gray-600 bg-gray-50 border-gray-200';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="p-6 mx-auto">
                <Head title={`Updates - ${incident.title}`} />
                
                {/* Incident Header */}
                <Card className="mb-6">
                    <CardHeader>
                        <div className="flex items-start justify-between">
                            <div className="flex-1">
                                <div className="flex items-center gap-2 mb-2">
                                    <CardTitle className="text-xl">{incident.title}</CardTitle>
                                    <div className={cn(
                                        'flex items-center gap-1 rounded-full border px-2 py-1 text-xs font-semibold',
                                        statusColor,
                                    )}>
                                        <StatusIcon className="h-3 w-3" />
                                        <span className="capitalize">{incident.status.replace('_', ' ')}</span>
                                    </div>
                                </div>
                                
                                {incident.service && (
                                    <p className="text-sm text-muted-foreground mb-2">
                                        Affecting: <span className="font-medium">{incident.service.name}</span>
                                    </p>
                                )}
                                
                                <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                    <div className={cn(
                                        'inline-flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-medium',
                                        severityColor
                                    )}>
                                        <ShieldAlert className="h-3 w-3" />
                                        <span className="capitalize">{incident.severity} severity</span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Clock className="h-3 w-3" />
                                        <span>Started {new Date(incident.created_at).toLocaleString()}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    {incident.description && (
                        <CardContent>
                            <p className="text-muted-foreground">{incident.description}</p>
                        </CardContent>
                    )}
                </Card>

                <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    {/* Timeline */}
                    <div className="xl:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <MessageSquare className="h-5 w-5" />
                                    Incident Timeline
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <IncidentTimeline 
                                    updates={updates.data} 
                                    enableRealtime={true}
                                    showIcons={true}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    {/* Post Update Form */}
                    <div>
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">Post Update</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submit} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="status">Status</Label>
                                        <Select value={data.status} onValueChange={(value) => setData('status', value as IncidentStatus)}>
                                            <SelectTrigger>
                                                {/* Custom rendering for selected value: only icon + label, single line, centered */}
                                                {(() => {
                                                    const selected = statusOptions.find(option => option.value === data.status);
                                                    const Icon = selected ? (statusIcons[selected.value as IncidentStatus]?.icon || Info) : Info;
                                                    return (
                                                        <div className="flex items-center gap-2 truncate w-full">
                                                            <Icon className="h-4 w-4" />
                                                            <span className="font-medium truncate">{selected ? selected.label : "Select status"}</span>
                                                        </div>
                                                    );
                                                })()}
                                            </SelectTrigger>
                                            <SelectContent>
                                                {statusOptions.map(option => {
                                                    const Icon = statusIcons[option.value as IncidentStatus]?.icon || Info;
                                                    return (
                                                        <SelectItem key={option.value} value={option.value}>
                                                            <div className="flex items-center gap-2">
                                                                <Icon className="h-4 w-4" />
                                                                <div className="flex flex-col">
                                                                    <span className="font-medium">{option.label}</span>
                                                                    <span className="text-xs text-muted-foreground">{option.description}</span>
                                                                </div>
                                                            </div>
                                                        </SelectItem>
                                                    );
                                                })}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.status} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="message">Update Message</Label>
                                        <textarea
                                            id="message"
                                            value={data.message}
                                            onChange={(e) => setData('message', e.target.value)}
                                            rows={4}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Describe what's happening with this incident..."
                                            disabled={processing}
                                        />
                                        <InputError message={errors.message} />
                                    </div>

                                    <Button type="submit" disabled={processing} className="w-full">
                                        <Send className="h-4 w-4 mr-2" />
                                        {processing ? 'Posting...' : 'Post Update'}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
} 