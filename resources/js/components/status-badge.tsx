import { Badge } from './ui/badge';
import clsx from 'clsx';

const STATUS_CONFIG: Record<string, {
  variant: 'success' | 'warning' | 'destructive' | 'info' | 'secondary' | 'muted';
  icon?: string;
  priority: 'high' | 'medium' | 'low';
}> = {
  operational: { variant: 'success', icon: '●', priority: 'low' },
  degraded: { variant: 'warning', icon: '⚠', priority: 'medium' },
  partial_outage: { variant: 'destructive', icon: '⚡', priority: 'high' },
  major_outage: { variant: 'destructive', icon: '🚨', priority: 'high' },
  investigating: { variant: 'info', icon: '🔍', priority: 'medium' },
  identified: { variant: 'warning', icon: '🎯', priority: 'medium' },
  monitoring: { variant: 'info', icon: '👁', priority: 'low' },
  resolved: { variant: 'success', icon: '✅', priority: 'low' },
  scheduled: { variant: 'secondary', icon: '📅', priority: 'low' },
  in_progress: { variant: 'info', icon: '⚙', priority: 'medium' },
  completed: { variant: 'success', icon: '🎉', priority: 'low' },
};

export function StatusBadge({ status, className }: { status: string; className?: string }) {
  const config = STATUS_CONFIG[status] || { variant: 'muted', priority: 'low' };
  const displayText = status.replace(/_/g, ' ');

  return (
    <Badge
      variant={config.variant}
      className={clsx(
        'capitalize font-medium tracking-wide',
        'flex items-center gap-1.5',
        config.priority === 'high' && 'animate-pulse',
        config.priority === 'medium' && 'ring-1 ring-current/20',
        className
      )}
      aria-label={`Status: ${displayText}`}
    >
      {config.icon && (
        <span className="text-[10px] leading-none">
          {config.icon}
        </span>
      )}
      <span className="font-semibold">
        {displayText}
      </span>
    </Badge>
  );
} 