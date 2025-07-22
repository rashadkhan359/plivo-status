# Render Deployment Fix Guide

This guide addresses the email configuration and Echo/Pusher websocket issues in your Render deployment.

## Issues Fixed

1. **Email Configuration**: Missing Gmail app password and port
2. **Queue Processing**: Using sync queue for simplicity (emails sent synchronously)
3. **Echo/Pusher**: Using placeholder values instead of real credentials
4. **HTTPS**: Mixed content errors due to HTTP URLs

## Required Environment Variables for Render

Add these environment variables in your Render dashboard under the "Environment" tab:

### Email Configuration (Gmail SMTP)
```
MAIL_FROM_ADDRESS=rashadkhan359@gmail.com
MAIL_ENCRYPTION=tls
MAIL_USERNAME=rashadkhan359@gmail.com
MAIL_PASSWORD=your_gmail_app_password_here
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
```

### Queue Configuration (Simple)
```
QUEUE_CONNECTION=sync
```

### Broadcasting Configuration (Pusher)
```
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_CLUSTER=your_pusher_cluster
PUSHER_HOST=api-your_cluster.pusherapp.com
PUSHER_PORT=443
PUSHER_SCHEME=https
```

### Frontend Environment Variables
```
VITE_PUSHER_APP_KEY=your_pusher_app_key
VITE_PUSHER_APP_CLUSTER=your_pusher_cluster
```

## Gmail App Password Setup

1. Go to your Google Account settings
2. Navigate to Security > 2-Step Verification
3. Scroll down to "App passwords"
4. Generate a new app password for "Mail"
5. Use this password as `MAIL_PASSWORD`

## Pusher Setup

1. Create a free account at [Pusher](https://pusher.com/)
2. Create a new Channels app
3. Get your app credentials from the dashboard
4. Update the environment variables with your real credentials

## Changes Made

### 1. HTTPS Middleware (`app/Http/Middleware/ForceHttps.php`)
Added middleware to force HTTPS in production:
```php
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && !$request->isSecure()) {
            return redirect()->secure($request->getRequestUri());
        }
        return $next($request);
    }
}
```

### 2. Session Configuration (`render.yaml`)
Added secure cookie configuration:
```yaml
- key: SESSION_SECURE_COOKIE
  value: true
```

### 3. Debug Route (`routes/web.php`)
Added temporary debug route for environment variable checking:
```php
Route::get('/debug-env', function () {
    // Returns environment variable status
});
```

## Testing Email

After deployment, test email functionality:

1. Try the "Forgot Password" feature
2. Check if you receive the email (may take a few seconds with sync queue)
3. Verify emails are being sent to your Gmail account

## Testing WebSocket

After updating Pusher credentials:

1. Visit your public status page
2. Open browser developer tools
3. Check for Echo connection errors
4. Verify real-time updates are working

## Troubleshooting

### Email Issues
- Check Gmail app password is correct
- Verify `MAIL_PORT=587` (not 465)
- Ensure `MAIL_ENCRYPTION=tls`
- With sync queue, emails are sent immediately (may take a few seconds)

### WebSocket Issues
- Verify Pusher credentials are correct
- Check `BROADCAST_CONNECTION=pusher`
- Ensure `VITE_PUSHER_APP_KEY` matches `PUSHER_APP_KEY`
- Check browser console for connection errors

### HTTPS Issues
- Verify `APP_URL=https://your-app.onrender.com`
- Check `SESSION_SECURE_COOKIE=true`
- ForceHttps middleware should redirect HTTP to HTTPS

## Current render.yaml Issues

The current `render.yaml` uses placeholder values:
```yaml
- key: PUSHER_APP_ID
  value: default-app-id
- key: PUSHER_APP_KEY
  value: default-app-key
- key: PUSHER_APP_SECRET
  value: default-app-secret
```

These need to be replaced with real Pusher credentials or removed to use environment variables instead.

## Recommended Action

1. Update all environment variables in Render dashboard
2. Redeploy the application
3. Test email and websocket functionality
4. Monitor logs for any remaining issues
5. Remove debug route after confirming everything works

## Why Sync Queue?

For this assignment/demo, we're using `QUEUE_CONNECTION=sync` because:
- **Simplicity**: No need for queue workers or database tables
- **Immediate feedback**: Emails are sent synchronously
- **Easier debugging**: No background job processing to troubleshoot
- **Sufficient for demo**: Handles the email requirements without complexity

In production, you'd typically use `database` or `redis` queues for better performance and reliability. 