import { Badge } from './ui/badge';
import clsx from 'clsx';

const STATUS_COLORS: Record<string, string> = {
  operational: 'bg-green-100 text-green-800',
  degraded: 'bg-yellow-100 text-yellow-800',
  partial_outage: 'bg-orange-100 text-orange-800',
  major_outage: 'bg-red-100 text-red-800',
  investigating: 'bg-blue-100 text-blue-800',
  identified: 'bg-amber-100 text-amber-800',
  monitoring: 'bg-cyan-100 text-cyan-800',
  resolved: 'bg-green-100 text-green-800',
  scheduled: 'bg-sky-100 text-sky-800',
  in_progress: 'bg-indigo-100 text-indigo-800',
  completed: 'bg-green-100 text-green-800',
};

export function StatusBadge({ status, className }: { status: string; className?: string }) {
  return (
    <Badge
      className={clsx(
        'capitalize px-2 py-1 text-xs font-light transition-colors duration-200 rounded-full',
        STATUS_COLORS[status] || 'bg-gray-400 text-white',
        className
      )}
      aria-label={status}
    >
      {status.replace(/_/g, ' ')}
    </Badge>
  );
} 