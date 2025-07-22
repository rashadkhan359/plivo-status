# BEACON

A modern, real-time status page application built with Laravel, Inertia.js, and React. This project demonstrates a comprehensive status monitoring system with live updates, incident management, and team collaboration features.

## 🚀 Key Features

### Core Functionality
- **Real-time Status Updates**: Live status monitoring with WebSocket integration
- **Incident Management**: Create, update, and resolve incidents with timeline tracking
- **Maintenance Scheduling**: Plan and manage scheduled maintenance windows
- **Team Collaboration**: Role-based access control with team management
- **Public Status Pages**: Shareable status pages for external stakeholders

### Technical Highlights
- **Modern Stack**: Laravel 11 + Inertia.js + React + TypeScript
- **Real-time Updates**: WebSocket broadcasting for live status changes
- **Responsive Design**: Mobile-first design with dark/light mode support
- **Permission System**: Granular role-based access control
- **API Integration**: RESTful API for external integrations

## 🛠 Tech Stack

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: React 18, TypeScript, Tailwind CSS
- **Database**: MySQL/PostgreSQL
- **Real-time**: Laravel WebSockets
- **Deployment**: Docker-ready with Render deployment

## 📋 Assignment Requirements Met

### ✅ Core Features
- [x] Service status monitoring (Operational, Degraded, Outage)
- [x] Incident creation and management
- [x] Real-time status updates
- [x] Public status page
- [x] Team management and permissions
- [x] Maintenance scheduling

### ✅ Technical Requirements
- [x] Laravel backend with API
- [x] Modern frontend with React
- [x] Real-time updates
- [x] Responsive design
- [x] Database migrations and seeders
- [x] Docker configuration

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL/PostgreSQL

### Installation

1. **Clone and Setup**
```bash
git clone <repository-url>
cd plivo-status
composer install
npm install
```

2. **Environment Configuration**
```bash
cp .env.example .env
# Configure database and other settings
```

3. **Database Setup**
```bash
php artisan migrate
php artisan db:seed
```

4. **Start Development**
```bash
npm run dev
php artisan serve
```

### Docker Deployment
```bash
docker-compose up -d
```

## 📁 Project Structure

```
plivo-status/
├── app/
│   ├── Http/Controllers/    # API and web controllers
│   ├── Models/             # Eloquent models
│   ├── Events/             # Real-time events
│   └── Services/           # Business logic
├── resources/js/
│   ├── components/         # React components
│   ├── pages/             # Inertia pages
│   └── hooks/             # Custom React hooks
└── database/
    ├── migrations/         # Database schema
    └── seeders/           # Sample data
```

## �� Configuration

### Environment Variables
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=plivo_status
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
```

### Real-time Setup
1. Configure Pusher credentials in `.env`
2. Run `php artisan websockets:serve` for local development
3. For production, use Pusher or configure Laravel WebSockets

## 📱 Features Overview

### Dashboard
- Real-time service status overview
- Recent incidents and maintenance
- Team activity feed
- Quick status updates

### Incident Management
- Create and update incidents
- Timeline tracking
- Service impact mapping
- Resolution workflow

### Public Status Page
- Shareable status page
- Service uptime metrics
- Incident history
- RSS feed support

### Team Management
- Role-based permissions
- Team invitations
- Activity logging
- Custom permission system

## �� Testing

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --filter=ServiceStatusTest
php artisan test --filter=IncidentTest
```

## 📊 API Documentation

### Core Endpoints
- `GET /api/services` - List all services
- `POST /api/incidents` - Create new incident
- `GET /api/status` - Public status page data
- `PUT /api/services/{id}/status` - Update service status

### Authentication
- Bearer token authentication
- Role-based access control
- Team-scoped permissions

## 🚀 Deployment

### Render Deployment
1. Connect your repository to Render
2. Configure environment variables
3. Set build command: `composer install && npm install && npm run build`
4. Set start command: `php artisan serve --host=0.0.0.0 --port=$PORT`

### Docker Deployment
```bash
docker build -t plivo-status .
docker run -p 8000:8000 plivo-status
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 📄 License

This project is created for the Plivo assignment. All rights reserved.

---

**Note**: This is a demonstration project showcasing modern web development practices with Laravel and React. The application includes comprehensive features for status page management with real-time updates and team collaboration capabilities.