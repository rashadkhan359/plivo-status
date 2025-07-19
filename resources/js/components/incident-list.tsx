import { useEffect, useState } from 'react';
import { StatusBadge } from '@/components/status-badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Incident } from '@/types/incident';

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

  // Sync with prop changes
  useEffect(() => {
    setIncidents(initialIncidents);
  }, [initialIncidents]);

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