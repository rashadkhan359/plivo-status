import { useEffect, useRef, useState } from 'react';

export type ConnectionState = 'connecting' | 'connected' | 'disconnected';

export function useRealtime() {
  const [state, setState] = useState<ConnectionState>('connecting');
  const echoRef = useRef<any>(null);
  const retryTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const connectionAttempts = useRef(0);
  const maxRetries = 10; // Increased retries
  const isInitialized = useRef(false);

  useEffect(() => {
    // Wait for Echo to be available and properly initialized
    const checkEcho = () => {
      // @ts-ignore
      if (!window.Echo) {
        setState('connecting');
        
        // Retry after 1 second, but limit attempts
        if (connectionAttempts.current < maxRetries) {
          connectionAttempts.current++;
          retryTimeoutRef.current = setTimeout(checkEcho, 1000);
        } else {
          console.error('Max retry attempts reached for Echo connection');
          setState('disconnected');
        }
        return;
      }
      
      const echo = window.Echo;
      
      // Check if Echo has a connector (meaning it's properly initialized)
      if (!echo.connector) {
        if (connectionAttempts.current < maxRetries) {
          connectionAttempts.current++;
          retryTimeoutRef.current = setTimeout(checkEcho, 500);
        }
        return;
      }
      
      // Echo is properly initialized
      if (!isInitialized.current) {
        isInitialized.current = true;
      }
      
      echoRef.current = echo;
      
      try {
        // Check if we have a valid Pusher connection
        if (echo.connector.pusher) {
          const pusher = echo.connector.pusher;
          
          // Set initial state based on current connection
          setState(pusher.connection.state);
          
          // Bind to connection state changes
          pusher.connection.bind('connecting', () => {
            setState('connecting');
          });
          
          pusher.connection.bind('connected', () => {
            setState('connected');
            connectionAttempts.current = 0; // Reset retry counter on success
          });
          
          pusher.connection.bind('disconnected', () => {
            setState('disconnected');
          });
          
          pusher.connection.bind('unavailable', () => {
            setState('disconnected');
          });
          
          pusher.connection.bind('failed', (error: any) => {
            setState('disconnected');
          });
          
          // If already connected, set state immediately
          if (pusher.connection.state === 'connected') {
            setState('connected');
            connectionAttempts.current = 0;
          }
        } else {
          console.warn('Echo connector available but Pusher not initialized');
          setState('connecting');
          
          // Retry after a short delay to wait for Pusher initialization
          if (connectionAttempts.current < maxRetries) {
            connectionAttempts.current++;
            retryTimeoutRef.current = setTimeout(checkEcho, 500);
          }
        }
      } catch (error) {
        setState('disconnected');
      }
    };

    // Check immediately
    checkEcho();

    // Also check after a short delay in case Echo is still initializing
    const timeout = setTimeout(checkEcho, 500);

    return () => {
      clearTimeout(timeout);
      if (retryTimeoutRef.current) {
        clearTimeout(retryTimeoutRef.current);
      }
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
    
    // Check if Echo is properly connected
    if (echoRef.current.connector && echoRef.current.connector.pusher) {
      const pusherState = echoRef.current.connector.pusher.connection.state;
      if (pusherState !== 'connected') {
        console.warn(`Cannot subscribe to ${channel} ${event} - Pusher not connected (state: ${pusherState})`);
        // Retry subscription after a short delay
        setTimeout(() => {
          subscribe(channel, event, callback);
        }, 1000);
        return;
      }
    }
    
    try {
      const channelInstance = echoRef.current.channel(channel);
      
      // Add error handling for the channel subscription
      if (!channelInstance) {
        console.error('Failed to get channel instance for:', channel);
        return;
      }

      
      // Add dot prefix to event name for proper Laravel Echo handling
      const eventName = event.startsWith('.') ? event : `.${event}`;
      
      channelInstance.listen(eventName, (data: any) => {
        callback(data);
      });
      
      // Add error handling for the channel
      if (channelInstance.error) {
        console.error('Channel error:', channelInstance.error);
      }
    } catch (error) {
      console.warn('Echo subscription error:', error);
    }
  }

  function unsubscribe(channel: string, event: string) {
    if (!echoRef.current) return;
    try {
      // Add dot prefix to event name for proper Laravel Echo handling
      const eventName = event.startsWith('.') ? event : `.${event}`;
      echoRef.current.channel(channel).stopListening(eventName);
    } catch (error) {
      console.warn('Echo unsubscription error:', error);
    }
  }

  return { state, subscribe, unsubscribe };
} 