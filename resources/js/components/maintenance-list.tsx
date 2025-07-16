import { useEffect, useState } from 'react';
import { StatusBadge } from './status-badge';
import { Skeleton } from './ui/skeleton';

export function MaintenanceList({ initialMaintenances }: { initialMaintenances: any[] }) {
  const [maintenances, setMaintenances] = useState(initialMaintenances);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Example: Listen for real-time updates (replace with your WebSocket logic)
  useEffect(() => {
    // TODO: Replace with Laravel Echo or your WebSocket client
    // Example: window.Echo.channel('maintenances').listen('maintenance.status.updated', ...)
    // setMaintenances(...)
  }, []);

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
  if (!maintenances.length) {
    return <div className="text-muted-foreground text-sm p-4">No maintenances found.</div>;
  }
  return (
    <div className="space-y-2">
      {maintenances.map((m) => (
        <div key={m.id} className="flex items-center gap-4 p-3 rounded-lg bg-card hover:bg-muted transition-colors cursor-pointer">
          <StatusBadge status={m.status} />
          <div className="flex-1 min-w-0">
            <div className="font-medium truncate">{m.title}</div>
            <div className="text-xs text-muted-foreground flex gap-2">
              <span>{new Date(m.scheduled_start).toLocaleString()} - {new Date(m.scheduled_end).toLocaleString()}</span>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
} 