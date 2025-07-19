# Real-time Features

This document covers the real-time features implementation in the Plivo Status application, including WebSocket setup, broadcasting, and live updates.

## Overview

The application uses Laravel's broadcasting system to provide real-time updates for:
- Service status changes
- Incident creation and updates
- Maintenance scheduling and status changes
- Public status page updates

## Broadcasting Configuration

### Supported Drivers

The application supports multiple broadcasting drivers:

1. **Laravel Reverb** (Recommended for local development)
2. **Pusher** (Recommended for production)
3. **Redis** (Alternative for self-hosted solutions)

### Environment Configuration

#### Local Development (Laravel Reverb)

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

#### Production (Pusher)

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-pusher-app-id
PUSHER_APP_KEY=your-pusher-app-key
PUSHER_APP_SECRET=your-pusher-app-secret
PUSHER_APP_CLUSTER=your-pusher-cluster
PUSHER_HOST=api-{cluster}.pusher.com
PUSHER_PORT=443
PUSHER_SCHEME=https
```

#### Frontend Environment Variables

```env
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
```

## Event System

### Event Classes

The application defines several events that implement broadcasting:

```php
// Service status change event
class ServiceStatusChanged implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public Service $service,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("organization.{$this->service->organization_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'service.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'service' => ServiceResource::make($this->service),
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

### Available Events

| Event | Description | Channel | Payload |
|-------|-------------|---------|---------|
| `ServiceStatusChanged` | Service status updated | `organization.{id}` | Service data, old/new status |
| `IncidentCreated` | New incident created | `organization.{id}` | Incident data |
| `IncidentUpdated` | Incident updated | `organization.{id}` | Incident data, updates |
| `IncidentResolved` | Incident resolved | `organization.{id}` | Incident data |
| `MaintenanceScheduled` | Maintenance scheduled | `organization.{id}` | Maintenance data |
| `MaintenanceStarted` | Maintenance started | `organization.{id}` | Maintenance data |
| `MaintenanceCompleted` | Maintenance completed | `organization.{id}` | Maintenance data |

### Event Broadcasting

Events are automatically broadcast when triggered:

```php
// In ServiceController
public function updateStatus(UpdateServiceStatusRequest $request, Service $service)
{
    $oldStatus = $service->status;
    
    $service->update([
        'status' => $request->status,
        'status_message' => $request->message,
    ]);

    // Log status change
    $service->statusLogs()->create([
        'status' => $request->status,
        'message' => $request->message,
    ]);

    // Broadcast event
    event(new ServiceStatusChanged($service, $oldStatus, $request->status));

    return back()->with('success', 'Service status updated successfully.');
}
```

## Frontend Integration

### Laravel Echo Setup

The frontend uses Laravel Echo to listen for real-time events:

```typescript
// resources/js/app.tsx
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure Pusher
window.Pusher = Pusher;

// Configure Echo
const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    host: import.meta.env.VITE_PUSHER_HOST,
    port: import.meta.env.VITE_PUSHER_PORT,
    scheme: import.meta.env.VITE_PUSHER_SCHEME,
    forceTLS: import.meta.env.VITE_PUSHER_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Make Echo available globally
window.Echo = echo;
```

### Real-time Hook

A custom hook provides real-time functionality:

```typescript
// resources/js/hooks/use-realtime.ts
import { useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface RealtimeConfig {
    organizationId: number;
    onServiceUpdate?: (data: any) => void;
    onIncidentUpdate?: (data: any) => void;
    onMaintenanceUpdate?: (data: any) => void;
}

export function useRealtime(config: RealtimeConfig) {
    const channelRef = useRef<any>(null);

    useEffect(() => {
        if (!config.organizationId) return;

        // Join organization channel
        channelRef.current = window.Echo.channel(`organization.${config.organizationId}`);

        // Listen for service status changes
        channelRef.current.listen('.service.status.changed', (e: any) => {
            console.log('Service status changed:', e);
            
            if (config.onServiceUpdate) {
                config.onServiceUpdate(e);
            }

            // Show notification
            toast.success(`Service "${e.service.name}" status changed to ${e.new_status}`);
            
            // Refresh page data
            router.reload({ only: ['services', 'incidents'] });
        });

        // Listen for incident updates
        channelRef.current.listen('.incident.created', (e: any) => {
            console.log('Incident created:', e);
            
            if (config.onIncidentUpdate) {
                config.onIncidentUpdate(e);
            }

            toast.error(`New incident: ${e.incident.title}`);
            router.reload({ only: ['incidents'] });
        });

        channelRef.current.listen('.incident.updated', (e: any) => {
            console.log('Incident updated:', e);
            
            if (config.onIncidentUpdate) {
                config.onIncidentUpdate(e);
            }

            toast.info(`Incident updated: ${e.incident.title}`);
            router.reload({ only: ['incidents'] });
        });

        // Listen for maintenance updates
        channelRef.current.listen('.maintenance.scheduled', (e: any) => {
            console.log('Maintenance scheduled:', e);
            
            if (config.onMaintenanceUpdate) {
                config.onMaintenanceUpdate(e);
            }

            toast.warning(`Maintenance scheduled: ${e.maintenance.title}`);
            router.reload({ only: ['maintenances'] });
        });

        return () => {
            if (channelRef.current) {
                channelRef.current.unsubscribe();
            }
        };
    }, [config.organizationId]);

    return {
        channel: channelRef.current,
    };
}
```

### Component Usage

```tsx
// Example: Dashboard with real-time updates
function Dashboard() {
    const { organization } = usePage<SharedData>().props;
    
    useRealtime({
        organizationId: organization.id,
        onServiceUpdate: (data) => {
            // Custom service update handling
            console.log('Service updated:', data);
        },
        onIncidentUpdate: (data) => {
            // Custom incident update handling
            console.log('Incident updated:', data);
        },
    });

    return (
        <div>
            <h1>Dashboard</h1>
            {/* Dashboard content */}
        </div>
    );
}
```

## Public Status Page

### Real-time Updates

The public status page also receives real-time updates:

```tsx
// resources/js/pages/public-status-page.tsx
function PublicStatusPage() {
    const { organization, services, incidents, maintenances } = usePage<SharedData>().props;
    
    useRealtime({
        organizationId: organization.id,
        onServiceUpdate: (data) => {
            // Update service status in real-time
            // This could use a state management solution like Zustand
        },
        onIncidentUpdate: (data) => {
            // Update incidents in real-time
        },
    });

    return (
        <div>
            <h1>{organization.name} Status</h1>
            {/* Status page content */}
        </div>
    );
}
```

## Queue Configuration

### Background Processing

Real-time events are processed through Laravel's queue system:

```env
QUEUE_CONNECTION=redis  # or database, sync for development
```

### Queue Workers

Start queue workers to process broadcasting events:

```bash
# Development
php artisan queue:work

# Production
php artisan queue:work --daemon

# With supervisor (recommended for production)
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

## SSL/HTTPS Configuration

### Automatic SSL Detection

The system automatically detects HTTPS and configures WebSocket connections:

```typescript
// Automatic SSL configuration
const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    host: import.meta.env.VITE_PUSHER_HOST,
    port: import.meta.env.VITE_PUSHER_PORT,
    scheme: import.meta.env.VITE_PUSHER_SCHEME,
    forceTLS: import.meta.env.VITE_PUSHER_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

### SSL Certificate Verification

For production environments with custom SSL certificates:

```typescript
const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    host: import.meta.env.VITE_PUSHER_HOST,
    port: import.meta.env.VITE_PUSHER_PORT,
    scheme: import.meta.env.VITE_PUSHER_SCHEME,
    forceTLS: true,
    enabledTransports: ['wss'],
    // Additional SSL options if needed
    wsHost: import.meta.env.VITE_PUSHER_HOST,
    wsPort: import.meta.env.VITE_PUSHER_PORT,
    wssPort: import.meta.env.VITE_PUSHER_PORT,
});
```

## Testing Real-time Features

### Manual Testing

1. **Start Development Environment**:
   ```bash
   composer run dev
   ```

2. **Open Multiple Tabs**:
   - Dashboard: http://localhost:8000/dashboard
   - Public Status: http://localhost:8000/status/{organization-slug}

3. **Make Changes**:
   - Update service status
   - Create incidents
   - Schedule maintenance

4. **Observe Real-time Updates**:
   - Changes should appear instantly across all tabs
   - Toast notifications should show
   - No page refresh required

### Automated Testing

```php
// tests/Feature/EventBroadcastingTest.php
class EventBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_status_change_broadcasts_event()
    {
        Event::fake();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $service = Service::factory()->for($organization)->create();

        $organization->users()->attach($user->id, ['role' => 'admin']);

        $this->actingAs($user)
            ->patch("/services/{$service->id}/status", [
                'status' => 'degraded',
                'message' => 'Performance issues detected',
            ]);

        Event::assertDispatched(ServiceStatusChanged::class);
    }

    public function test_incident_creation_broadcasts_event()
    {
        Event::fake();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        
        $organization->users()->attach($user->id, ['role' => 'admin']);

        $this->actingAs($user)
            ->post('/incidents', [
                'title' => 'Test Incident',
                'description' => 'Test description',
                'severity' => 'minor',
            ]);

        Event::assertDispatched(IncidentCreated::class);
    }
}
```

## Performance Considerations

### Connection Management

- **Connection Pooling**: Laravel Echo manages WebSocket connections efficiently
- **Automatic Reconnection**: Built-in reconnection logic for network issues
- **Connection Limits**: Monitor connection limits for your broadcasting service

### Event Optimization

```php
// Optimize event payload size
class ServiceStatusChanged implements ShouldBroadcast
{
    public function broadcastWith(): array
    {
        return [
            'id' => $this->service->id,
            'name' => $this->service->name,
            'status' => $this->service->status,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'updated_at' => $this->service->updated_at->toISOString(),
        ];
    }
}
```

### Caching

```php
// Cache organization data to reduce database queries
class Organization extends Model
{
    public function getCachedServicesAttribute()
    {
        return Cache::remember(
            "organization_services_{$this->id}",
            now()->addMinutes(5),
            fn() => $this->services()->with('statusLogs')->get()
        );
    }
}
```

## Troubleshooting

### Common Issues

#### 1. Events Not Broadcasting

**Symptoms**: Changes not appearing in real-time
**Solutions**:
```bash
# Check queue worker
php artisan queue:work

# Check broadcasting configuration
php artisan config:cache

# Verify environment variables
php artisan tinker
config('broadcasting.default')
```

#### 2. WebSocket Connection Issues

**Symptoms**: Console errors about WebSocket connections
**Solutions**:
```bash
# Check Pusher/Reverb credentials
# Verify SSL certificates
# Check firewall settings
# Ensure proper port configuration
```

#### 3. SSL/HTTPS Issues

**Symptoms**: "400 Error: The plain HTTP request was sent to HTTPS port"
**Solutions**:
- Ensure `forceTLS: true` for HTTPS
- Use `wss://` scheme for secure connections
- Verify SSL certificate configuration

#### 4. Performance Issues

**Symptoms**: Slow real-time updates or high resource usage
**Solutions**:
- Monitor queue worker performance
- Optimize event payload size
- Use connection pooling
- Implement proper caching

### Debug Tools

```php
// Enable broadcasting debug mode
BROADCAST_DEBUG=true

// Log broadcasting events
Log::info('Broadcasting event', [
    'event' => get_class($event),
    'channel' => $event->broadcastOn(),
    'data' => $event->broadcastWith(),
]);
```

```typescript
// Frontend debugging
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('Connected to WebSocket');
});

window.Echo.connector.pusher.connection.bind('error', (error: any) => {
    console.error('WebSocket error:', error);
});
```

## Best Practices

### 1. Event Design

- Keep event payloads small and focused
- Include only necessary data
- Use consistent naming conventions
- Version events for backward compatibility

### 2. Channel Management

- Use organization-scoped channels for security
- Implement proper channel authorization
- Clean up unused connections
- Monitor channel usage

### 3. Error Handling

- Implement proper error handling for WebSocket connections
- Provide fallback mechanisms
- Log connection issues
- Graceful degradation when real-time is unavailable

### 4. Security

- Validate event data on both client and server
- Use private channels for sensitive data
- Implement proper authentication for channels
- Monitor for suspicious activity

This real-time system provides a robust foundation for live updates while maintaining performance and security standards. 