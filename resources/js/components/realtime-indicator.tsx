import { Badge } from './ui/badge';
import clsx from 'clsx';

export function RealtimeIndicator({ state }: { state: 'connecting' | 'connected' | 'disconnected' }) {
  let color = 'bg-gray-400';
  let text = 'Connecting...';
  if (state === 'connected') {
    color = 'bg-green-500';
    text = 'Live';
  } else if (state === 'disconnected') {
    color = 'bg-red-500';
    text = 'Offline';
  }
  return (
    <Badge
      className={clsx(
        'flex items-center gap-1 px-2 py-1 text-xs font-medium transition-colors duration-200',
        color
      )}
      aria-live="polite"
    >
      <span className={clsx('inline-block w-2 h-2 rounded-full mr-1', color, state === 'connected' && 'animate-pulse')} />
      {text}
    </Badge>
  );
} 