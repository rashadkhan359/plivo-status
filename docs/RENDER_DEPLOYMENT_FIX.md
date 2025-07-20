# Render Deployment Fix Guide

This guide addresses the email configuration and Echo/Pusher websocket issues in your Render deployment.

## Issues Fixed

1. **Email Configuration**: Missing Gmail app password and port
2. **Queue Processing**: Added queue workers for asynchronous email processing
3. **Echo/Pusher**: Using placeholder values instead of real credentials
4. **Queue Tables**: Missing database tables for queue processing

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

### Queue Configuration
```
QUEUE_CONNECTION=database
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

### 1. Supervisor Configuration (`docker/supervisor/supervisord.conf`)
Added queue worker to process background jobs:
```ini
[program:queue-worker]
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true
startretries=0
priority=300
user=www-data
```

### 2. Entrypoint Script (`docker/entrypoint.sh`)
Added queue table creation:
```bash
# Create queue tables if using database queue
php artisan queue:table --force
php artisan migrate --force
```

## Testing Email

After deployment, test email functionality:

1. Try the "Forgot Password" feature
2. Check the logs for any email errors
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
- Check queue workers are running

### WebSocket Issues
- Verify Pusher credentials are correct
- Check `BROADCAST_CONNECTION=pusher`
- Ensure `VITE_PUSHER_APP_KEY` matches `PUSHER_APP_KEY`
- Check browser console for connection errors

### Queue Issues
- Verify `QUEUE_CONNECTION=database`
- Check queue workers are running in supervisor
- Monitor logs for queue processing errors

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