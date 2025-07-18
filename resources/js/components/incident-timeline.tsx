import { useEffect, useState } from 'react';
import { StatusBadge } from '@/components/status-badge';
import { Skeleton } from '@/components/ui/skeleton';
import { IncidentUpdate } from '@/types/incident-update';
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';
import { cn } from '@/lib/utils';
import { AlertTriangle, CheckCircle, Info, ShieldAlert, Zap, MessageSquare } from 'lucide-react';

type IncidentStatus = 'investigating' | 'identified' | 'monitoring' | 'resolved';

const statusIcons: Record<IncidentStatus, { icon: React.ElementType; color: string; label: string }> = {
  investigating: { icon: Info, color: 'text-blue-500', label: 'Investigating' },
  identified: { icon: ShieldAlert, color: 'text-yellow-500', label: 'Identified' },
  monitoring: { icon: Zap, color: 'text-purple-500', label: 'Monitoring' },
  resolved: { icon: CheckCircle, color: 'text-green-500', label: 'Resolved' },
};

interface IncidentTimelineProps {
  updates: IncidentUpdate[];
  loading?: boolean;
  error?: string | null;
  enableRealtime?: boolean;
  orgId?: string;
  orgSlug?: string;
  showIcons?: boolean;
  compact?: boolean;
}

export function IncidentTimeline({
  updates: initialUpdates,
  loading,
  error,
  enableRealtime = false,
  orgId,
  orgSlug,
  showIcons = true,
  compact = false
}: IncidentTimelineProps) {
  const [updates, setUpdates] = useState<IncidentUpdate[]>(initialUpdates);
  console.log('IncidentTimeline: initialUpdates:', initialUpdates);
  console.log('IncidentTimeline: updates:', updates.length);

  // Update local state when props change
  useEffect(() => {
    setUpdates(initialUpdates);
  }, [initialUpdates]);

  if (loading) {
    return (
      <div className="space-y-4">
        {[...Array(3)].map((_, i) => (
          <Skeleton key={i} className="h-16 w-full rounded-lg" />
        ))}
      </div>
    );
  }

  if (error) {
    return <div className="text-red-500 text-sm p-4">{error}</div>;
  }

  if (!updates.length) {
    return (
      <div className="text-center py-8 text-muted-foreground">
        <MessageSquare className="h-8 w-8 mx-auto mb-2 opacity-50" />
        <p>No updates yet</p>
      </div>
    );
  }

  // Full version with icons and rich styling
  return (
    <div className="space-y-6">
      {updates.map((update, index) => {
        const UpdateStatusIcon = statusIcons[update.status as IncidentStatus]?.icon || Info;
        const updateStatusColor = statusIcons[update.status as IncidentStatus]?.color || 'text-gray-500';

        return (
          <div key={update.id} className={cn(
            'relative pl-6 pb-6',
            index !== updates.length - 1 && 'border-l border-muted-foreground/20 ml-2'
          )}>
            <div className="absolute -left-2.5 w-5 h-5 bg-background border-2 border-muted-foreground/20 rounded-full flex items-center justify-center">
              {showIcons ? (
                <UpdateStatusIcon className={cn('h-3 w-3', updateStatusColor)} />
              ) : (
                <StatusBadge status={update.status} className="text-xs px-1 py-0.5" />
              )}
            </div>
            <div className="bg-muted/30 rounded-lg p-4">
              <div className="flex items-center justify-between mb-2">
                <div className={cn(
                  'text-xs font-medium px-2 py-1 rounded-full',
                  updateStatusColor,
                  'bg-current/10'
                )}>
                  {statusIcons[update.status as IncidentStatus]?.label || update.status}
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
  );
} 