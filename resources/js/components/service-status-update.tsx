import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Check, Clock, AlertTriangle, XCircle, Loader2, Info } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Service } from '@/types/service';

interface ServiceStatusUpdateProps {
  service: Service;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

const statusOptions = [
  {
    value: 'operational',
    label: 'Operational',
    color: 'text-green-600',
    bgColor: 'bg-green-50',
    icon: Check,
    description: 'All systems functioning normally'
  },
  {
    value: 'degraded',
    label: 'Degraded Performance',
    color: 'text-yellow-600',
    bgColor: 'bg-yellow-50',
    icon: Clock,
    description: 'Minor issues affecting performance'
  },
  {
    value: 'partial_outage',
    label: 'Partial Outage',
    color: 'text-orange-600',
    bgColor: 'bg-orange-50',
    icon: AlertTriangle,
    description: 'Some functionality is unavailable'
  },
  {
    value: 'major_outage',
    label: 'Major Outage',
    color: 'text-red-600',
    bgColor: 'bg-red-50',
    icon: XCircle,
    description: 'Service is significantly impacted'
  },
];

export function ServiceStatusUpdate({ service, open, onOpenChange }: ServiceStatusUpdateProps) {
  const [status, setStatus] = useState(service.status);
  const [message, setMessage] = useState('');
  const [createIncident, setCreateIncident] = useState(false);
  const [updating, setUpdating] = useState(false);
  const toast = useToast();

  const currentStatus = statusOptions.find(option => option.value === service.status);
  const selectedStatus = statusOptions.find(option => option.value === status);

  const handleSubmit = async () => {
    setUpdating(true);

    const updateData: any = { status };

    if (createIncident && message && status !== 'operational') {
      updateData.create_incident = true;
      updateData.incident_message = message;
    }

    router.patch(`/services/${service.id}/status`, updateData, {
      onSuccess: () => {
        toast.success('Service status updated successfully!');
        onOpenChange(false);
        setMessage('');
        setCreateIncident(false);
      },
      onError: (errors) => {
        toast.error('Failed to update service status');
      },
      onFinish: () => {
        setUpdating(false);
      },
    });
  };

  const isStatusChanged = status !== service.status;
  const isDowngrade = status !== 'operational' && service.status === 'operational';

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Update Service Status</DialogTitle>
          <DialogDescription>
            Update the status for <strong>{service.name}</strong>
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          {/* Current Status */}
          <div className="flex items-center justify-between p-3 rounded-lg bg-muted/50">
            <div className="flex items-center gap-2">
              {currentStatus && <currentStatus.icon className={`h-4 w-4 ${currentStatus.color}`} />}
              <span className="text-sm font-medium">Current: {currentStatus?.label}</span>
            </div>
          </div>

          {/* New Status Selection */}
          <div className="space-y-2">
            <Label htmlFor="status">New Status</Label>
            <Select value={status} onValueChange={(value) => setStatus(value as Service['status'])}>
              <SelectTrigger>
                {/* Custom rendering for selected value: only icon + label, single line, centered */}
                {(() => {
                  const selected = statusOptions.find(option => option.value === status);
                  const Icon = selected ? selected.icon || Info : Info;
                  return (
                    <div className={`flex items-center gap-2 truncate w-full ${selected?.color} ${selected?.bgColor}/10 p-2 rounded-md`}>
                      <Icon className={`h-4 w-4 ${selected?.color}`} />
                      <span className="font-medium truncate">{selected ? selected.label : "Select status"}</span>
                    </div>
                  );
                })()}
              </SelectTrigger>
              <SelectContent>
                {statusOptions.map(option => {
                  const Icon = option.icon || Info;
                  return (
                    <SelectItem key={option.value} value={option.value}>
                      <div className="flex items-center gap-2">
                        <Icon className={`h-4 w-4 ${option.color}`} />
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
          </div>

          {/* Show incident creation option for downgrades */}
          {isDowngrade && (
            <div className="space-y-3 p-3 rounded-lg border border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-950/50">
              <div className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  id="create-incident"
                  checked={createIncident}
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => setCreateIncident(e.target.checked)}
                  className="rounded border-gray-300"
                />
                <Label htmlFor="create-incident" className="text-sm">
                  Create incident for this status change
                </Label>
              </div>

              {createIncident && (
                <div className="space-y-2">
                  <Label htmlFor="incident-message">Incident Description</Label>
                  <textarea
                    id="incident-message"
                    value={message}
                    onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setMessage(e.target.value)}
                    placeholder="Describe what's happening..."
                    rows={3}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              )}
            </div>
          )}

          {/* Status change preview */}
          {isStatusChanged && (
            <div className="flex items-center justify-between p-3 rounded-lg bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800">
              <div className="flex items-center gap-2">
                {selectedStatus && <selectedStatus.icon className={`h-4 w-4 ${selectedStatus.color}`} />}
                <span className="text-sm font-medium">Changing to: {selectedStatus?.label}</span>
              </div>
            </div>
          )}
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={updating}>
            Cancel
          </Button>
          <Button
            onClick={handleSubmit}
            disabled={!isStatusChanged || updating || (createIncident && !message)}
          >
            {updating && <Loader2 className="h-4 w-4 animate-spin mr-2" />}
            Update Status
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
} 