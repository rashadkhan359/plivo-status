# System Admin Setup

This document describes the automatic system admin setup for the Plivo Status application.

## Overview

The application automatically ensures that a system admin exists when it starts. This system admin has full access to all organizations and administrative features.

## Automatic Setup

### 1. Application Boot
When the application starts, the `AppServiceProvider` automatically checks if a system admin exists and creates one if needed.

### 2. Configuration
System admin settings are configured in `config/admin.php`:

```php
'system_admin' => [
    'name' => env('SYSTEM_ADMIN_NAME', 'System Admin'),
    'email' => env('SYSTEM_ADMIN_EMAIL', 'admin@plivo-status.com'),
    'password' => env('SYSTEM_ADMIN_PASSWORD', 'password'),
],
```

### 3. Environment Variables
You can customize the system admin by setting these environment variables:

```env
SYSTEM_ADMIN_NAME="Your Admin Name"
SYSTEM_ADMIN_EMAIL="admin@yourdomain.com"
SYSTEM_ADMIN_PASSWORD="secure-password"
```

## Manual Commands

### Create System Admin
```bash
php artisan admin:create --name="Admin Name" --email="admin@example.com" --password="password"
```

### Ensure System Admin Exists
```bash
php artisan admin:ensure --name="Admin Name" --email="admin@example.com" --password="password"
```

## System Admin Service

The `SystemAdminService` provides methods to manage system admins:

```php
use App\Services\SystemAdminService;

$service = app(SystemAdminService::class);

// Check if system admin exists
if ($service->exists()) {
    $admin = $service->get();
}

// Create system admin
$admin = $service->create('Name', 'email@example.com', 'password');

// Ensure system admin exists
$admin = $service->ensureExists();

// Grant system admin status to user
$service->grantSystemAdmin($user);

// Remove system admin status from user
$service->removeSystemAdmin($user);

// Get all system admins
$admins = $service->getAll();

// Count system admins
$count = $service->count();
```

## User Model Methods

The `User` model includes methods for system admin functionality:

```php
$user = User::find(1);

// Check if user is system admin
if ($user->isSystemAdmin()) {
    // User has system admin privileges
}
```

## Factory Support

The `UserFactory` supports creating system admin users:

```php
// Create system admin using factory
$admin = User::factory()->systemAdmin()->create();

// Create system admin with custom attributes
$admin = User::factory()->systemAdmin()->create([
    'name' => 'Custom Admin',
    'email' => 'custom@example.com',
]);
```

## Database Migration

The system admin functionality requires the `is_system_admin` field in the users table:

```php
// Migration: 2025_07_18_000001_add_system_admin_to_users.php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('is_system_admin')->default(false)->after('role');
});
```

## Testing

Run the system admin tests:

```bash
php artisan test --filter=SystemAdminTest
```

## Security Considerations

1. **Default Credentials**: Change the default system admin password in production
2. **Environment Variables**: Use environment variables to configure system admin credentials
3. **Access Control**: System admins have full access to all organizations
4. **Audit Logging**: All system admin operations are logged

## Default System Admin

When the application starts for the first time, a system admin is automatically created with:

- **Name**: System Admin
- **Email**: admin@plivo-status.com
- **Password**: password

**Important**: Change these credentials in production!

## Troubleshooting

### System Admin Not Created
1. Check if the migration has been run: `php artisan migrate:status`
2. Check application logs for errors
3. Manually create system admin: `php artisan admin:ensure`

### Permission Issues
1. Ensure the user has `is_system_admin = true` in the database
2. Check if the middleware is properly configured
3. Verify the policy is correctly registered

### Multiple System Admins
By default, only one system admin can exist. To create additional system admins, use the `--force` flag:

```bash
php artisan admin:create --force --name="Second Admin" --email="admin2@example.com" --password="password"
``` 