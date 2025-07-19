# Broadcasting Setup

To enable real-time features, add these environment variables to your `.env` file:

## For Local Development (Laravel Reverb - Recommended)
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

## For Production (Pusher)
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

## Frontend Environment Variables
Add these to your `.env` file for the frontend:

```env
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
```

## Running Laravel Reverb (Local Development)
```bash
php artisan reverb:start
```

## Queue Configuration
Make sure your queue is running for broadcasting:
```bash
php artisan queue:work
```

## SSL/HTTPS Configuration
If you're using HTTPS in production, the system automatically:
- Uses WSS (WebSocket Secure) connections
- Forces TLS encryption
- Handles SSL certificate verification

## Testing Real-time Features
1. Register/login to create an organization
2. Create services, incidents, or maintenance 
3. Open the public status page in another tab: `/status/{organization-slug}`
4. Make changes in the dashboard - they should appear in real-time on the status page

## Troubleshooting

### 400 Error: "The plain HTTP request was sent to HTTPS port"
This error occurs when WebSocket connections try to use HTTP on an HTTPS site. The system now automatically:
- Detects HTTPS protocol and uses WSS
- Forces TLS encryption for secure connections
- Uses proper SSL configuration

### Events Not Broadcasting
1. Check that your queue worker is running: `php artisan queue:work`
2. Verify broadcasting connection in `.env`: `BROADCAST_CONNECTION=pusher` or `reverb`
3. Check browser console for WebSocket connection errors
4. Ensure events implement `ShouldBroadcast` interface

### WebSocket Connection Issues
1. Verify Pusher/Reverb credentials in `.env`
2. Check firewall settings for WebSocket ports
3. Ensure proper SSL certificates for HTTPS sites
4. Check browser console for connection errors 