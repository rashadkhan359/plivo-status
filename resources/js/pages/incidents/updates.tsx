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

interface IncidentUpdate {
    id: number;
    message: string;
    status: IncidentStatus;
    created_at: string;
}

interface Props {
    incident: { data: Incident };
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
    const { data, setData, post, processing, errors, reset } = useForm<UpdateForm>({
        message: '',
        status: incident.data.status,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('incidents.updates.store', incident.data.id), {
            onSuccess: () => {
                reset('message');
            },
        });
    };

    const breadcrumbs = [
        { title: 'Incidents', href: '/incidents' },
        { title: incident.data.title, href: `/incidents/${incident.data.id}/updates` },
    ];

    const StatusIcon = statusIcons[incident.data.status]?.icon || AlertTriangle;
    const statusColor = statusIcons[incident.data.status]?.color || 'text-gray-500';

    const severityColors = {
        low: 'text-blue-600 bg-blue-50 border-blue-200',
        medium: 'text-yellow-600 bg-yellow-50 border-yellow-200',
        high: 'text-orange-600 bg-orange-50 border-orange-200',
        critical: 'text-red-600 bg-red-50 border-red-200',
    };
    const severityColor = severityColors[incident.data.severity as keyof typeof severityColors] || 'text-gray-600 bg-gray-50 border-gray-200';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="p-4 max-w-4xl mx-auto">
                <Head title={`Updates - ${incident.data.title}`} />
                
                {/* Incident Header */}
                <Card className="mb-6">
                    <CardHeader>
                        <div className="flex items-start justify-between">
                            <div className="flex-1">
                                <div className="flex items-center gap-2 mb-2">
                                    <CardTitle className="text-xl">{incident.data.title}</CardTitle>
                                    <div className={cn(
                                        'flex items-center gap-1 rounded-full border px-2 py-1 text-xs font-semibold',
                                        statusColor,
                                    )}>
                                        <StatusIcon className="h-3 w-3" />
                                        <span className="capitalize">{incident.data.status.replace('_', ' ')}</span>
                                    </div>
                                </div>
                                
                                {incident.data.service && (
                                    <p className="text-sm text-muted-foreground mb-2">
                                        Affecting: <span className="font-medium">{incident.data.service.name}</span>
                                    </p>
                                )}
                                
                                <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                    <div className={cn(
                                        'inline-flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-medium',
                                        severityColor
                                    )}>
                                        <ShieldAlert className="h-3 w-3" />
                                        <span className="capitalize">{incident.data.severity} severity</span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Clock className="h-3 w-3" />
                                        <span>Started {new Date(incident.data.created_at).toLocaleString()}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    {incident.data.description && (
                        <CardContent>
                            <p className="text-muted-foreground">{incident.data.description}</p>
                        </CardContent>
                    )}
                </Card>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Timeline */}
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <MessageSquare className="h-5 w-5" />
                                    Incident Timeline
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {updates.data.length === 0 ? (
                                    <div className="text-center py-8 text-muted-foreground">
                                        <MessageSquare className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p>No updates yet</p>
                                    </div>
                                ) : (
                                    <div className="space-y-6">
                                        {updates.data.map((update, index) => {
                                            const UpdateStatusIcon = statusIcons[update.status]?.icon || Info;
                                            const updateStatusColor = statusIcons[update.status]?.color || 'text-gray-500';
                                            
                                            return (
                                                <div key={update.id} className={cn(
                                                    'relative pl-6 pb-6',
                                                    index !== updates.data.length - 1 && 'border-l border-muted-foreground/20 ml-2'
                                                )}>
                                                    <div className="absolute -left-2.5 w-5 h-5 bg-background border-2 border-muted-foreground/20 rounded-full flex items-center justify-center">
                                                        <UpdateStatusIcon className={cn('h-3 w-3', updateStatusColor)} />
                                                    </div>
                                                    <div className="bg-muted/30 rounded-lg p-4">
                                                        <div className="flex items-center justify-between mb-2">
                                                            <div className={cn(
                                                                'text-xs font-medium px-2 py-1 rounded-full',
                                                                updateStatusColor,
                                                                'bg-current/10'
                                                            )}>
                                                                {statusIcons[update.status]?.label || update.status}
                                                            </div>
                                                            <span className="text-xs text-muted-foreground">
                                                                {new Date(update.created_at).toLocaleString()}
                                                            </span>
                                                        </div>
                                                        <p className="text-sm">{update.message}</p>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
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
                                                <SelectValue placeholder="Select status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {statusOptions.map(option => {
                                                    const Icon = statusIcons[option.value as IncidentStatus]?.icon || Info;
                                                    return (
                                                        <SelectItem key={option.value} value={option.value}>
                                                            <div className="flex items-center gap-2">
                                                                <Icon className="h-4 w-4" />
                                                                <div>
                                                                    <div className="font-medium">{option.label}</div>
                                                                    <div className="text-xs text-muted-foreground">{option.description}</div>
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