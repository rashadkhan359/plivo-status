import { Badge } from './ui/badge';
import clsx from 'clsx';

export function RealtimeIndicator({ state }: { state: 'connecting' | 'connected' | 'disconnected' }) {
  const getStateConfig = () => {
    switch (state) {
      case 'connected':
        return {
          bgColor: 'bg-emerald-500/10',
          borderColor: 'border-emerald-500/20',
          textColor: 'text-emerald-700 dark:text-emerald-400',
          dotColor: 'bg-emerald-500',
          text: 'Live',
          icon: '●'
        };
      case 'connecting':
        return {
          bgColor: 'bg-amber-500/10',
          borderColor: 'border-amber-500/20',
          textColor: 'text-amber-700 dark:text-amber-400',
          dotColor: 'bg-amber-500',
          text: 'Connecting...',
          icon: '⟳'
        };
      case 'disconnected':
        return {
          bgColor: 'bg-red-500/10',
          borderColor: 'border-red-500/20',
          textColor: 'text-red-700 dark:text-red-400',
          dotColor: 'bg-red-500',
          text: 'Offline',
          icon: '○'
        };
    }
  };

  const config = getStateConfig();

  return (
    <Badge
      className={clsx(
        'inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium',
        'border rounded-full transition-all duration-300 ease-in-out',
        'shadow-sm backdrop-blur-sm',
        config.bgColor,
        config.borderColor,
        config.textColor,
        state === 'connected' && 'shadow-emerald-500/20',
        state === 'connecting' && 'shadow-amber-500/20',
        state === 'disconnected' && 'shadow-red-500/20'
      )}
      aria-live="polite"
    >
      <div className="relative">
        <span 
          className={clsx(
            'inline-block w-2 h-2 rounded-full',
            config.dotColor,
            'transition-all duration-300 ease-in-out',
            state === 'connected' && 'animate-pulse shadow-lg shadow-emerald-500/50',
            state === 'connecting' && 'animate-spin',
            state === 'disconnected' && 'opacity-60'
          )} 
        />
        {state === 'connected' && (
          <span className="absolute inset-0 w-2 h-2 top-1 rounded-full bg-emerald-400 animate-ping opacity-75" />
        )}
      </div>
      <span className="font-semibold tracking-wide">
        {config.text}
      </span>
    </Badge>
  );
} 