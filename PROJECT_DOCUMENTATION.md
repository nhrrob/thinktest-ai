# ThinkTest AI - Complete Project Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [Architecture & Design](#architecture--design)
3. [Features & Functionality](#features--functionality)
4. [Technology Stack](#technology-stack)
5. [Installation Guide](#installation-guide)
6. [API Documentation](#api-documentation)
7. [Security Implementation](#security-implementation)
8. [Testing Strategy](#testing-strategy)
9. [Deployment Guide](#deployment-guide)
10. [Troubleshooting](#troubleshooting)

## Project Overview

ThinkTest AI is an intelligent WordPress plugin testing platform that leverages advanced AI models to automatically generate comprehensive test suites. The platform bridges the gap between WordPress development and modern testing practices by providing AI-powered test generation for both PHPUnit and Pest frameworks.

### Core Value Proposition
- **Automated Test Generation**: Reduces test creation time from hours to minutes
- **AI-Powered Intelligence**: Uses GPT-5 and Claude 3.5 Sonnet for superior test quality
- **WordPress-Specific**: Understands WordPress hooks, filters, and plugin patterns
- **Developer-Friendly**: Seamless integration with existing development workflows

## Architecture & Design

### System Architecture
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend       │    │   External      │
│   (React)       │◄──►│   (Laravel)     │◄──►│   Services      │
│                 │    │                 │    │                 │
│ • React 19      │    │ • Laravel 12    │    │ • OpenAI API    │
│ • TypeScript    │    │ • PHP 8.2+      │    │ • Anthropic API │
│ • Tailwind CSS  │    │ • MySQL/Postgres│    │ • GitHub API    │
│ • Inertia.js    │    │ • Redis Cache   │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Database Schema
- **Users & Authentication**: User management with role-based permissions
- **AI Conversations**: Conversation state and message history
- **Plugin Analysis**: Analysis results and metadata
- **Test Generation**: Generated tests and configuration
- **GitHub Integration**: Repository data and processing state
- **API Tokens**: User API keys and usage tracking

### Design Patterns
- **Service Layer Pattern**: Business logic separation
- **Repository Pattern**: Data access abstraction
- **Factory Pattern**: AI provider instantiation
- **Observer Pattern**: Event-driven notifications
- **Strategy Pattern**: Multiple AI provider support

## Features & Functionality

### AI-Powered Test Generation
- **Multi-Provider Support**: OpenAI GPT-5 and Anthropic Claude 3.5 Sonnet
- **Framework Flexibility**: PHPUnit and Pest test generation
- **WordPress Intelligence**: Hooks, filters, and action testing
- **Comprehensive Coverage**: Unit, integration, and functional tests

### GitHub Integration
- **Repository Processing**: Full repository analysis and file extraction
- **Branch Selection**: Dynamic branch listing with commit information
- **File-Level Testing**: Selective file testing for targeted test generation
- **Security Validation**: Comprehensive URL and content validation
- **Rate Limiting**: Per-user request limiting and abuse prevention

### User Experience
- **Modern Interface**: React 19 with Tailwind CSS 4.0
- **Dark Mode Support**: Professional theme switching with system detection
- **Real-Time Updates**: Live processing feedback and progress indicators
- **Demo Mode**: Free credits for platform evaluation
- **Responsive Design**: Mobile-first responsive layout

### Security Features
- **Authentication**: Laravel Sanctum with secure session management
- **Authorization**: Spatie Permission for role-based access control
- **Input Validation**: Comprehensive request validation and sanitization
- **Rate Limiting**: API and GitHub request rate limiting
- **Content Security**: Malicious code detection and sanitization

## Technology Stack

### Backend Technologies
- **Framework**: Laravel 12.x with PHP 8.2+
- **Database**: MySQL 8.0+ / PostgreSQL 13+
- **Cache**: Redis for session and application caching
- **Queue**: Laravel Queue for background processing
- **Authentication**: Laravel Sanctum with API tokens
- **Permissions**: Spatie Laravel Permission package

### Frontend Technologies
- **Framework**: React 19 with TypeScript 5.7+
- **Styling**: Tailwind CSS 4.0 with custom design system
- **UI Components**: Radix UI primitives for accessibility
- **State Management**: Inertia.js for SPA experience
- **Build Tool**: Vite 6.0 for fast development builds
- **Icons**: Lucide React for consistent iconography

### Development Tools
- **Code Quality**: ESLint 9.x, Prettier 3.x, Laravel Pint
- **Testing**: Pest PHP for backend, React Testing Library for frontend
- **Type Safety**: TypeScript with strict configuration
- **Package Management**: Composer (PHP), npm (Node.js)
- **Version Control**: Git with conventional commit messages

### External Services
- **AI Providers**: OpenAI API (GPT-5), Anthropic API (Claude 3.5 Sonnet)
- **GitHub Integration**: GitHub REST API v4
- **Monitoring**: Laravel Telescope for debugging
- **Logging**: Laravel Log with structured logging

## Installation Guide

### System Requirements
- PHP 8.2 or higher with extensions: mbstring, xml, curl, zip, gd
- Node.js 18+ with npm 9+
- MySQL 8.0+ or PostgreSQL 13+
- Redis 6.0+ (optional but recommended)
- Git for version control

### Development Setup
```bash
# 1. Clone repository
git clone https://github.com/nhrrob/thinktest-ai.git
cd thinktest-ai

# 2. Install PHP dependencies
composer install

# 3. Install Node.js dependencies
npm install

# 4. Environment configuration
cp .env.example .env
php artisan key:generate

# 5. Database setup
php artisan migrate --seed

# 6. Build frontend assets
npm run build

# 7. Start development servers
php artisan serve
npm run dev
```

### Production Deployment
```bash
# 1. Optimize for production
composer install --optimize-autoloader --no-dev
npm run build

# 2. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Set up queue worker
php artisan queue:work --daemon

# 4. Configure web server (Nginx/Apache)
# 5. Set up SSL certificate
# 6. Configure monitoring and logging
```

### Environment Configuration
```env
# Application
APP_NAME="ThinkTest AI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thinktest-ai
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password

# AI Providers
OPENAI_API_KEY=your_openai_api_key
ANTHROPIC_API_KEY=your_anthropic_api_key

# GitHub Integration
GITHUB_API_TOKEN=your_github_token
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
```

## API Documentation

### Authentication Endpoints
- `POST /api/auth/login` - User authentication
- `POST /api/auth/logout` - User logout
- `POST /api/auth/register` - User registration
- `GET /api/auth/user` - Get authenticated user

### ThinkTest Endpoints
- `GET /api/thinktest` - Get dashboard data
- `POST /api/thinktest/upload` - Upload plugin file
- `POST /api/thinktest/analyze` - Analyze plugin code
- `POST /api/thinktest/generate` - Generate tests
- `GET /api/thinktest/download/{id}` - Download generated tests

### GitHub Integration Endpoints
- `POST /api/github/validate` - Validate repository URL
- `GET /api/github/branches` - Get repository branches
- `POST /api/github/process` - Process repository
- `GET /api/github/files` - Browse repository files
- `POST /api/github/generate-file` - Generate tests for specific file

### API Response Format
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Response data
  },
  "meta": {
    "timestamp": "2024-01-01T00:00:00Z",
    "version": "2.0.0"
  }
}
```

## Security Implementation

### Authentication & Authorization
- **Session Management**: Secure session handling with CSRF protection
- **API Authentication**: Token-based authentication with rate limiting
- **Role-Based Access**: Granular permissions using Spatie Permission
- **Password Security**: Bcrypt hashing with configurable rounds

### Input Validation & Sanitization
- **Request Validation**: Laravel Form Requests with custom rules
- **File Upload Security**: MIME type validation and virus scanning
- **GitHub URL Validation**: Comprehensive URL pattern validation
- **Content Sanitization**: HTML purification and malicious code detection

### Rate Limiting & Abuse Prevention
- **API Rate Limits**: Per-user and per-endpoint rate limiting
- **GitHub API Limits**: Intelligent rate limit handling with backoff
- **File Size Limits**: Repository (50MB) and file count (1000) limits
- **Request Throttling**: Configurable throttling for resource-intensive operations

### Data Protection
- **Encryption**: Database encryption for sensitive data
- **Secure Headers**: Security headers for XSS and CSRF protection
- **Audit Logging**: Comprehensive activity logging for security monitoring
- **Data Retention**: Configurable data retention policies

## Testing Strategy

### Backend Testing (Pest PHP)
- **Unit Tests**: Service layer and utility function testing
- **Feature Tests**: HTTP endpoint and integration testing
- **Database Tests**: Model relationships and query testing
- **Security Tests**: Authentication and authorization testing

### Frontend Testing
- **Component Tests**: React component unit testing
- **Integration Tests**: User interaction flow testing
- **Accessibility Tests**: WCAG compliance testing
- **Visual Regression**: Screenshot comparison testing

### Test Coverage
- **Backend Coverage**: 85%+ with comprehensive test suite
- **Frontend Coverage**: 80%+ with component and integration tests
- **E2E Testing**: Critical user journey testing
- **Performance Testing**: Load testing and optimization validation

### Continuous Integration
```yaml
# GitHub Actions workflow
name: CI/CD Pipeline
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php artisan test
      - name: Frontend tests
        run: npm test
```

## Deployment Guide

### Production Environment Setup
1. **Server Configuration**: Ubuntu 22.04 LTS with Nginx
2. **PHP Configuration**: PHP 8.2 with OPcache and required extensions
3. **Database Setup**: MySQL 8.0 with optimized configuration
4. **Redis Setup**: Redis 6.0 for caching and sessions
5. **SSL Certificate**: Let's Encrypt or commercial SSL
6. **Monitoring**: Server monitoring and application performance monitoring

### Deployment Process
1. **Code Deployment**: Git-based deployment with automated builds
2. **Database Migration**: Zero-downtime migrations with rollback capability
3. **Asset Compilation**: Optimized asset builds with CDN integration
4. **Cache Warming**: Application and route cache warming
5. **Health Checks**: Automated health checks and monitoring
6. **Rollback Strategy**: Quick rollback procedures for failed deployments

### Performance Optimization
- **Database Optimization**: Query optimization and indexing
- **Caching Strategy**: Multi-layer caching with Redis
- **Asset Optimization**: Minification, compression, and CDN
- **Queue Processing**: Background job processing for heavy operations
- **Monitoring**: Performance monitoring and alerting

## Troubleshooting

### Common Issues
1. **AI Provider Errors**: API key validation and rate limit handling
2. **GitHub Integration**: Authentication and repository access issues
3. **File Upload Problems**: Size limits and format validation
4. **Performance Issues**: Database queries and caching optimization
5. **Authentication Errors**: Session and token management

### Debug Tools
- **Laravel Telescope**: Request and query debugging
- **Laravel Debugbar**: Development debugging toolbar
- **Log Analysis**: Structured logging with search capabilities
- **Performance Profiling**: Query and request performance analysis

### Support Resources
- **Documentation**: Comprehensive setup and usage guides
- **GitHub Issues**: Community support and bug reporting
- **Error Tracking**: Automated error reporting and tracking
- **Monitoring**: Real-time application monitoring and alerting

---

This documentation provides a comprehensive overview of the ThinkTest AI platform, covering all aspects from architecture to deployment. For specific implementation details, refer to the codebase and inline documentation.
