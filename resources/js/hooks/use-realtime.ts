import { useEffect, useRef, useState } from 'react';

export type ConnectionState = 'connecting' | 'connected' | 'disconnected';

export function useRealtime() {
  const [state, setState] = useState<ConnectionState>('connecting');
  const echoRef = useRef<any>(null);

  useEffect(() => {
    // Wait for Echo to be available
    const checkEcho = () => {
      // @ts-ignore
      if (!window.Echo) {
        setState('disconnected');
        return;
      }
      
      const echo = window.Echo;
      echoRef.current = echo;
      
      try {
        // Check if we have a valid connection
        if (echo.connector && echo.connector.pusher) {
          setState('connected');
          
          echo.connector.pusher.connection.bind('connecting', () => setState('connecting'));
          echo.connector.pusher.connection.bind('connected', () => setState('connected'));
          echo.connector.pusher.connection.bind('disconnected', () => setState('disconnected'));
          echo.connector.pusher.connection.bind('unavailable', () => setState('disconnected'));
          echo.connector.pusher.connection.bind('failed', () => setState('disconnected'));
        } else {
          setState('disconnected');
        }
      } catch (error) {
        console.warn('Echo connection error:', error);
        setState('disconnected');
      }
    };

    // Check immediately
    checkEcho();

    // Also check after a short delay in case Echo is still initializing
    const timeout = setTimeout(checkEcho, 1000);

    return () => {
      clearTimeout(timeout);
      if (echoRef.current && echoRef.current.connector && echoRef.current.connector.pusher) {
        const connection = echoRef.current.connector.pusher.connection;
        connection.unbind('connecting');
        connection.unbind('connected');
        connection.unbind('disconnected');
        connection.unbind('unavailable');
        connection.unbind('failed');
      }
    };
  }, []);

  function subscribe(channel: string, event: string, callback: (data: any) => void) {
    if (!echoRef.current) {
      console.warn('Echo not available for subscription:', channel, event);
      return;
    }
    try {
      echoRef.current.channel(channel).listen(event, callback);
    } catch (error) {
      console.warn('Echo subscription error:', error);
    }
  }

  function unsubscribe(channel: string, event: string) {
    if (!echoRef.current) return;
    try {
      echoRef.current.channel(channel).stopListening(event);
    } catch (error) {
      console.warn('Echo unsubscription error:', error);
    }
  }

  return { state, subscribe, unsubscribe };
} 