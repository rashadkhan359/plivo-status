# Checking Environment Variables in Render (Free Tier)

Since Render's free tier doesn't provide shell access, here are safe alternatives to check your environment variables:

## Method 1: Temporary Debug Route (Recommended)

I've added a temporary debug route to your application. After deployment:

1. Visit: `https://your-app.onrender.com/debug-env`
2. This will show you all the environment variables and their status
3. **IMPORTANT**: Remove this route after debugging for security

## Method 2: Check Application Logs

The application now logs environment variable status when accessing the public status page:

1. Visit your public status page: `https://your-app.onrender.com/status/your-org-slug`
2. Check the Render logs in your dashboard
3. Look for log entries with "Public status page accessed"

## Method 3: Browser Developer Tools

Check the browser console for Echo/Pusher connection issues:

1. Open browser developer tools (F12)
2. Go to Console tab
3. Visit your public status page
4. Look for Echo connection errors and Pusher configuration

## Method 4: Test Email Functionality

Test if email environment variables are working:

1. Try the "Forgot Password" feature
2. Check if you receive the email
3. If not, check the application logs for email errors

## Method 5: Check Render Dashboard

In your Render dashboard:

1. Go to your service
2. Click on "Environment" tab
3. Verify all required variables are set:
   - `MAIL_FROM_ADDRESS`
   - `MAIL_USERNAME`
   - `MAIL_PASSWORD`
   - `PUSHER_APP_ID`
   - `PUSHER_APP_KEY`
   - `PUSHER_APP_SECRET`
   - `PUSHER_APP_CLUSTER`
   - `VITE_PUSHER_APP_KEY`
   - `VITE_PUSHER_APP_CLUSTER`

## Expected Values

### Email Configuration
```
MAIL_FROM_ADDRESS=rashadkhan359@gmail.com
MAIL_USERNAME=rashadkhan359@gmail.com
MAIL_PASSWORD=your_16_character_app_password
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

### Pusher Configuration
```
PUSHER_APP_ID=your_pusher_app_id (numeric)
PUSHER_APP_KEY=your_pusher_app_key (alphanumeric)
PUSHER_APP_SECRET=your_pusher_app_secret (alphanumeric)
PUSHER_APP_CLUSTER=your_cluster (e.g., us2, eu, ap1)
VITE_PUSHER_APP_KEY=same_as_PUSHER_APP_KEY
VITE_PUSHER_APP_CLUSTER=same_as_PUSHER_APP_CLUSTER
```

## Common Issues

### Echo Not Available
- Check if `VITE_PUSHER_APP_KEY` is set
- Verify `PUSHER_APP_KEY` matches `VITE_PUSHER_APP_KEY`
- Ensure `BROADCAST_CONNECTION=pusher`

### Email Not Working
- Verify Gmail app password is correct
- Check `MAIL_PORT=587` (not 465)
- Ensure `MAIL_ENCRYPTION=tls`

### Mixed Content Errors
- Verify `APP_URL=https://your-app.onrender.com`
- Check `SESSION_SECURE_COOKIE=true`

## After Debugging

1. Remove the debug route from `routes/web.php`
2. Remove debug logging from controllers
3. Redeploy the application

## Security Note

Never expose sensitive environment variables in production. The debug route is automatically disabled in production, but always remove it after debugging. 