# Getting Started Guide

This guide will walk you through setting up the Plivo Status application from scratch.

## Prerequisites

Before you begin, ensure you have the following installed on your system:

### Required Software
- **PHP 8.2 or higher** - [Download PHP](https://www.php.net/downloads)
- **Node.js 18 or higher** - [Download Node.js](https://nodejs.org/)
- **Composer** - [Download Composer](https://getcomposer.org/download/)
- **Git** - [Download Git](https://git-scm.com/downloads)

### Database
- **MySQL 8.0+** or **PostgreSQL 13+** or **SQLite** (for development)

### Optional (for production)
- **Redis** - For caching and session storage
- **Supervisor** - For managing queue workers
- **Nginx/Apache** - Web server

## Installation Steps

### 1. Clone the Repository

```bash
git clone <repository-url>
cd plivo-status
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Environment Configuration

```bash
# Copy the environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 5. Database Setup

#### Option A: MySQL/PostgreSQL
```bash
# Update .env with your database credentials
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=plivo_status
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### Option B: SQLite (Development)
```bash
# Create SQLite database
touch database/database.sqlite

# Update .env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

### 6. Run Database Migrations

```bash
php artisan migrate
```

### 7. Seed the Database

```bash
# Run basic seeders
php artisan db:seed

# Run demo data (optional)
php artisan db:seed --class=DemoDataSeeder
```

### 8. Configure Broadcasting (Optional)

For real-time features, configure broadcasting:

#### Local Development (Laravel Reverb)
```bash
# Add to .env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

#### Production (Pusher)
```bash
# Add to .env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-pusher-app-id
PUSHER_APP_KEY=your-pusher-app-key
PUSHER_APP_SECRET=your-pusher-app-secret
PUSHER_APP_CLUSTER=your-pusher-cluster
```

### 9. Configure Email (Optional)

For email notifications and invitations:

```bash
# Add to .env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 10. Start Development Server

```bash
# Start all services (Laravel, Vite, Queue, Reverb)
composer run dev
```

This command starts:
- Laravel development server (http://localhost:8000)
- Vite development server (http://localhost:5173)
- Queue worker
- Laravel Reverb (if configured)

## Initial Setup

### 1. Access the Application

Open your browser and navigate to:
- **Main Application**: http://localhost:8000

### 2. Default Credentials

After running the seeders, you'll have these default accounts:

#### System Admin
- **Email**: admin@plivo-status.com
- **Password**: password

#### Demo Organization
- **Organization**: Demo Corp
- **Admin**: demo@example.com
- **Password**: password

### 3. Create Your First Organization

1. Register a new account
2. Create your first organization
3. Invite team members
4. Add services and start monitoring

## Development Workflow

### Available Commands

```bash
# Development
composer run dev          # Start all development services
composer run dev:ssr      # Start with Server-Side Rendering
npm run dev              # Start Vite only
php artisan serve        # Start Laravel only

# Code Quality
composer run format      # Format PHP code
npm run format          # Format JavaScript/TypeScript
composer run lint        # Lint PHP code
npm run lint            # Lint JavaScript/TypeScript
npm run types           # TypeScript type checking

# Testing
php artisan test        # Run all tests
php artisan test --coverage  # Run with coverage
npm run test            # Run frontend tests

# Database
php artisan migrate     # Run migrations
php artisan migrate:rollback  # Rollback migrations
php artisan db:seed     # Run seeders
php artisan db:wipe     # Clear database
```

### File Structure

```
plivo-status/
├── app/                    # Laravel application logic
│   ├── Console/           # Artisan commands
│   ├── Enums/             # PHP enums
│   ├── Events/            # Event classes
│   ├── Http/              # Controllers, middleware, requests
│   ├── Listeners/         # Event listeners
│   ├── Models/            # Eloquent models
│   ├── Notifications/     # Notification classes
│   ├── Policies/          # Authorization policies
│   ├── Providers/         # Service providers
│   └── Services/          # Business logic services
├── database/              # Migrations, seeders, factories
├── resources/             # Frontend resources
│   ├── js/               # React components and pages
│   ├── css/              # Stylesheets
│   └── views/            # Blade templates
├── routes/               # Route definitions
├── storage/              # File storage
├── tests/                # Test files
└── docs/                 # Documentation
```

## Troubleshooting

### Common Issues

#### 1. Permission Errors
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

#### 2. Composer Memory Issues
```bash
# Increase memory limit
COMPOSER_MEMORY_LIMIT=-1 composer install
```

#### 3. Node.js Version Issues
```bash
# Use Node Version Manager
nvm use 18
```

#### 4. Database Connection Issues
```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();
```

#### 5. Broadcasting Not Working
```bash
# Check queue worker
php artisan queue:work

# Check Reverb server
php artisan reverb:start
```

### Environment Variables Reference

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Application name | "Laravel" |
| `APP_ENV` | Environment | "local" |
| `APP_DEBUG` | Debug mode | true |
| `APP_URL` | Application URL | "http://localhost" |
| `DB_CONNECTION` | Database driver | "sqlite" |
| `BROADCAST_CONNECTION` | Broadcasting driver | "log" |
| `QUEUE_CONNECTION` | Queue driver | "sync" |
| `CACHE_DRIVER` | Cache driver | "file" |
| `SESSION_DRIVER` | Session driver | "file" |

## Next Steps

After completing the setup:

1. **Read the Documentation**:
   - [Architecture Overview](architecture.md)
   - [Authentication & Permissions](authentication.md)
   - [Real-time Features](realtime.md)

2. **Explore the Features**:
   - Create an organization
   - Add services
   - Create incidents
   - Test real-time updates

3. **Customize the Application**:
   - Modify the UI theme
   - Add custom services
   - Configure notifications

4. **Deploy to Production**:
   - [Deployment Guide](deployment.md)

## Support

If you encounter any issues:

1. Check the [troubleshooting section](#troubleshooting)
2. Review the [documentation](index.md)
3. Check the [GitHub issues](https://github.com/your-repo/issues)
4. Create a new issue with detailed information 