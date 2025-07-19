# Plivo Status - Status Page Application

A modern, real-time status page application built with Laravel 12, Inertia.js, React 19, and Tailwind CSS. This application allows organizations to manage their services, incidents, and maintenance windows while providing a public-facing status page for customers.

## 🎯 Assignment Overview

This project was built as a technical assignment to demonstrate:
- **Code Quality**: Clean, well-organized, and commented code
- **Architecture**: Proper separation of concerns and scalable design
- **Frontend Skills**: Responsive design, state management, and component structure
- **Backend Skills**: API design, database integration, and authentication implementation
- **Problem-Solving**: Approach and solve challenges during development
- **AI-First Development**: Leveraging AI tools for fast-track development

## ✨ Key Features

### 🔐 Authentication & User Management
- **User Registration & Login**: Secure authentication system
- **Email Verification**: Built-in email verification workflow
- **Password Reset**: Secure password reset functionality
- **Invitation System**: Team member invitations with role-based access

### 🏢 Multi-Tenant Organization System
- **Organization Management**: Create and manage multiple organizations
- **Team Management**: Organize users into teams with specific roles
- **Role-Based Permissions**: Granular permission system with custom overrides
- **System Admin**: Super admin with access to all organizations

### 🔧 Service Management
- **CRUD Operations**: Create, read, update, and delete services
- **Status Management**: Real-time status updates (Operational, Degraded, Partial Outage, Major Outage)
- **Service Categories**: Organize services by categories
- **Uptime Tracking**: Automatic uptime calculation and metrics

### 🚨 Incident & Maintenance Management
- **Incident Creation**: Create and manage incidents with severity levels
- **Incident Updates**: Add real-time updates to ongoing incidents
- **Maintenance Windows**: Schedule and manage planned maintenance
- **Service Association**: Link incidents and maintenance to specific services

### 📡 Real-Time Features
- **WebSocket Integration**: Real-time status updates using Pusher/Laravel Reverb
- **Live Notifications**: Instant updates across all connected clients
- **Status Broadcasting**: Automatic broadcasting of status changes

### 🌐 Public Status Page
- **Public Access**: No authentication required for status viewing
- **Real-Time Updates**: Live status updates without page refresh
- **Incident Timeline**: Historical view of incidents and maintenance
- **Service Status Overview**: Clear visual representation of all services

### 🎨 Modern UI/UX
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile
- **Dark/Light Mode**: Automatic theme switching with system preference
- **Linear-Inspired Design**: Clean, minimalistic interface using ShadcnUI
- **Accessibility**: WCAG compliant design patterns

## 🛠 Tech Stack

### Backend
- **Laravel 12**: Latest PHP framework with modern features
- **Inertia.js 2**: Seamless SPA experience without API complexity
- **MySQL/PostgreSQL**: Robust database support
- **Pusher/Laravel Reverb**: Real-time broadcasting
- **Laravel Sanctum**: API authentication

### Frontend
- **React 19**: Latest React with concurrent features
- **TypeScript**: Type-safe development
- **Tailwind CSS 4**: Utility-first CSS framework
- **ShadcnUI**: Beautiful, accessible components
- **Framer Motion**: Smooth animations and transitions

### Development Tools
- **Vite**: Fast build tool and dev server
- **ESLint & Prettier**: Code quality and formatting
- **PHPUnit**: Comprehensive testing suite
- **Laravel Pint**: PHP code style fixer

## 🚀 Quick Start

### Prerequisites
- PHP 8.2 or higher
- Node.js 18 or higher
- Composer
- MySQL/PostgreSQL database

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd plivo-status
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   ```bash
   # Update .env with your database credentials
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=plivo_status
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

7. **Configure broadcasting (optional)**
   ```bash
   # For local development with Laravel Reverb
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=your-app-id
   REVERB_APP_KEY=your-app-key
   REVERB_APP_SECRET=your-app-secret
   REVERB_HOST=localhost
   REVERB_PORT=8080
   REVERB_SCHEME=http
   ```

8. **Start the development server**
   ```bash
   # Start all services (Laravel, Vite, Queue, Reverb)
   composer run dev
   ```

9. **Access the application**
   - **Main Application**: http://localhost:8000
   - **Default Admin**: admin@plivo-status.com / password

## 📚 Documentation

Comprehensive documentation is available in the `docs/` folder:

- **[Getting Started](docs/getting-started.md)** - Complete setup guide
- **[Architecture Overview](docs/architecture.md)** - System design and patterns
- **[Authentication & Permissions](docs/authentication.md)** - User management and roles
- **[Real-time Features](docs/realtime.md)** - WebSocket and broadcasting setup
- **[API Documentation](docs/api.md)** - Available endpoints and usage
- **[Deployment Guide](docs/deployment.md)** - Production deployment instructions
- **[Testing Guide](docs/testing.md)** - Running tests and test coverage

## 🧪 Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --filter=PermissionTest
php artisan test --filter=SystemAdminTest
php artisan test --filter=CustomPermissionTest

# Run with coverage
php artisan test --coverage
```

## 🚀 Deployment

### Environment Variables
```env
APP_NAME="Plivo Status"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-pusher-app-id
PUSHER_APP_KEY=your-pusher-app-key
PUSHER_APP_SECRET=your-pusher-app-secret
PUSHER_APP_CLUSTER=your-pusher-cluster

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
```

### Production Commands
```bash
# Build assets
npm run build

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Start queue workers
php artisan queue:work --daemon
```

## 🎥 Demo

A demo video showcasing the application features is available at: [Demo Video Link]

## 🔧 Development Commands

```bash
# Development with all services
composer run dev

# Development with SSR
composer run dev:ssr

# Code formatting
composer run format
npm run format

# Code linting
composer run lint
npm run lint

# Type checking
npm run types

# Database seeding
php artisan db:seed --class=DemoDataSeeder
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and linting
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🙏 Acknowledgments

- **Laravel Team** for the amazing framework
- **Inertia.js** for seamless SPA experience
- **ShadcnUI** for beautiful components
- **Tailwind CSS** for utility-first styling
- **AI Tools** (Cursor, GitHub Copilot, Claude) for accelerated development

---

**Built with ❤️ using modern web technologies and AI-assisted development** 