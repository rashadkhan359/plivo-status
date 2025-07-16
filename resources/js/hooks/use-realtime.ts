import { useEffect, useRef, useState } from 'react';

export type ConnectionState = 'connecting' | 'connected' | 'disconnected';

export function useRealtime() {
  const [state, setState] = useState<ConnectionState>('connecting');
  const echoRef = useRef<any>(null);

  useEffect(() => {
    // @ts-ignore
    if (!window.Echo) {
      setState('disconnected');
      return;
    }
    const echo = window.Echo;
    echoRef.current = echo;
    setState('connected');
    echo.connector.pusher.connection.bind('connecting', () => setState('connecting'));
    echo.connector.pusher.connection.bind('connected', () => setState('connected'));
    echo.connector.pusher.connection.bind('disconnected', () => setState('disconnected'));
    echo.connector.pusher.connection.bind('unavailable', () => setState('disconnected'));
    return () => {
      echo.connector.pusher.connection.unbind('connecting');
      echo.connector.pusher.connection.unbind('connected');
      echo.connector.pusher.connection.unbind('disconnected');
      echo.connector.pusher.connection.unbind('unavailable');
    };
  }, []);

  function subscribe(channel: string, event: string, callback: (data: any) => void) {
    if (!echoRef.current) return;
    echoRef.current.channel(channel).listen(event, callback);
  }
  function unsubscribe(channel: string, event: string) {
    if (!echoRef.current) return;
    echoRef.current.channel(channel).stopListening(event);
  }

  return { state, subscribe, unsubscribe };
} 