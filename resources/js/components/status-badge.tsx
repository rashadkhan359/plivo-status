import { Badge } from './ui/badge';
import clsx from 'clsx';

const STATUS_COLORS: Record<string, string> = {
  operational: 'bg-green-500 text-white',
  degraded: 'bg-yellow-500 text-white',
  partial_outage: 'bg-orange-500 text-white',
  major_outage: 'bg-red-600 text-white',
  investigating: 'bg-blue-500 text-white',
  identified: 'bg-amber-500 text-white',
  monitoring: 'bg-cyan-600 text-white',
  resolved: 'bg-green-600 text-white',
  scheduled: 'bg-sky-500 text-white',
  in_progress: 'bg-indigo-500 text-white',
  completed: 'bg-green-700 text-white',
};

export function StatusBadge({ status, className }: { status: string; className?: string }) {
  return (
    <Badge
      className={clsx(
        'capitalize px-2 py-1 text-xs font-medium transition-colors duration-200',
        STATUS_COLORS[status] || 'bg-gray-400 text-white',
        className
      )}
      aria-label={status}
    >
      {status.replace(/_/g, ' ')}
    </Badge>
  );
} 