import { useEffect, useState } from 'react';
import { StatusBadge } from '@/components/status-badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Incident } from '@/types/incident';
import { useRealtime } from '@/hooks/use-realtime';

export function IncidentList({ 
  initialIncidents, 
  orgId, 
  orgSlug 
}: { 
  initialIncidents: Incident[];
  orgId?: string;
  orgSlug?: string;
}) {
  const [incidents, setIncidents] = useState<Incident[]>(initialIncidents);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const { state, subscribe, unsubscribe } = useRealtime();

  // Real-time subscriptions
  useEffect(() => {
    if (!orgId || !orgSlug) return;
    
    const handleIncidentCreated = (data: { incident: Incident }) => {
      console.log('IncidentCreated received:', data);
      setIncidents((prev) => [data.incident, ...prev]);
    };
    
    const handleIncidentUpdated = (data: { incident: Incident }) => {
      console.log('IncidentUpdated received:', data);
      setIncidents((prev) => prev.map((i) => (i.id === data.incident.id ? data.incident : i)));
    };
    
    const handleIncidentResolved = (data: { incident: Incident }) => {
      console.log('IncidentResolved received:', data);
      setIncidents((prev) => prev.map((i) => (i.id === data.incident.id ? data.incident : i)));
    };

    // Subscribe to both public and private channels
    subscribe(`organization.${orgId}`, 'IncidentCreated', handleIncidentCreated);
    subscribe(`organization.${orgId}`, 'IncidentUpdated', handleIncidentUpdated);
    subscribe(`organization.${orgId}`, 'IncidentResolved', handleIncidentResolved);
    
    // Also subscribe to public channels for redundancy
    subscribe(`status.${orgSlug}`, 'IncidentCreated', handleIncidentCreated);
    subscribe(`status.${orgSlug}`, 'IncidentUpdated', handleIncidentUpdated);
    subscribe(`status.${orgSlug}`, 'IncidentResolved', handleIncidentResolved);

    return () => {
      unsubscribe(`organization.${orgId}`, 'IncidentCreated');
      unsubscribe(`organization.${orgId}`, 'IncidentUpdated');
      unsubscribe(`organization.${orgId}`, 'IncidentResolved');
      unsubscribe(`status.${orgSlug}`, 'IncidentCreated');
      unsubscribe(`status.${orgSlug}`, 'IncidentUpdated');
      unsubscribe(`status.${orgSlug}`, 'IncidentResolved');
    };
  }, [orgId, orgSlug, subscribe, unsubscribe]);

  if (loading) {
    return (
      <div className="space-y-2">
        {[...Array(3)].map((_, i) => (
          <Skeleton key={i} className="h-16 w-full rounded-lg" />
        ))}
      </div>
    );
  }
  if (error) {
    return <div className="text-red-500 text-sm p-4">{error}</div>;
  }
  if (!incidents.length) {
    return <div className="text-muted-foreground text-sm p-4">No incidents found.</div>;
  }
  return (
    <div className="space-y-2">
      {incidents.map((incident) => (
        <div key={incident.id} className="flex items-center gap-4 p-3 rounded-lg bg-card hover:bg-muted transition-colors cursor-pointer">
          <StatusBadge status={incident.status} />
          <div className="flex-1 min-w-0">
            <div className="font-medium truncate">{incident.title}</div>
            <div className="text-xs text-muted-foreground flex gap-2">
              <span className="capitalize">{incident.severity}</span>
              <span>•</span>
              <span>{new Date(incident.created_at).toLocaleString()}</span>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
} 