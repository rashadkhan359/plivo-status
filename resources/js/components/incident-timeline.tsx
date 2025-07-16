import { StatusBadge } from './status-badge';
import { Skeleton } from './ui/skeleton';

export function IncidentTimeline({ updates, loading, error }: {
  updates: { id: string; message: string; status: string; created_at: string }[];
  loading?: boolean;
  error?: string | null;
}) {
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
    return <div className="text-muted-foreground text-sm p-4">No updates yet.</div>;
  }
  return (
    <ol className="relative border-l border-muted-foreground/20 space-y-6">
      {updates.map((update, i) => (
        <li key={update.id} className="ml-4">
          <div className="absolute -left-2.5 w-5 h-5 bg-background border-2 border-muted-foreground/20 rounded-full flex items-center justify-center">
            <StatusBadge status={update.status} className="text-xs px-1 py-0.5" />
          </div>
          <div className="pl-8">
            <div className="font-medium text-sm mb-1">{update.message}</div>
            <div className="text-xs text-muted-foreground">{new Date(update.created_at).toLocaleString()}</div>
          </div>
        </li>
      ))}
    </ol>
  );
} 