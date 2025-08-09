---
alwaysApply: true
---

# ThinkTest AI Laravel Project - Augment Rules

## Technology Stack Requirements

### Core Technologies
- **Laravel 12.0+** - Latest Laravel framework version
- **PHP 8.2+** - Minimum PHP version requirement
- **MySQL** - Primary database (not SQLite)
- **Pest PHP 3.0+** - Testing framework (preferred over PHPUnit)
- **Tailwind CSS 4.1.11+** - Utility-first CSS framework
- **React 19.1.0+** - Frontend JavaScript framework
- **Vite 6.2.4+** - Build tool and development server
- **Redis** - Caching and queue management
- **Laravel Herd** - Preferred local development environment

### Package Management
- Always use **Composer** for PHP dependencies
- Always use **npm** for Node.js dependencies
- Never manually edit package.json, composer.json, or lock files
- Use package manager commands for installing/removing dependencies

## Laravel Development Standards

### File Generation
- **ALWAYS use Laravel Artisan commands** for file generation instead of manual creation:
  - `php artisan make:model ModelName` for models
  - `php artisan make:migration create_table_name` for migrations
  - `php artisan make:controller ControllerName` for controllers
  - `php artisan make:seeder SeederName` for database seeders
  - `php artisan make:factory FactoryName` for model factories
  - `php artisan make:request RequestName` for form requests
  - `php artisan make:middleware MiddlewareName` for middleware
  - `php artisan make:job JobName` for queue jobs
  - `php artisan make:command CommandName` for console commands

### Code Style and Formatting
- Follow PSR-12 coding standards
- Use Laravel naming conventions:
  - Models: PascalCase, singular (e.g., `User`, `TestResult`)
  - Controllers: PascalCase with "Controller" suffix (e.g., `UserController`)
  - Migrations: snake_case with descriptive action (e.g., `create_users_table`)
  - Database tables: snake_case, plural (e.g., `users`, `test_results`)
  - Routes: kebab-case (e.g., `/test-results`)
- Always use resource routes if possible
- Use meaningful variable and method names
- Add proper docblocks for classes and methods

### Database Conventions
- **Use MySQL** as the primary database (not SQLite)
- Use descriptive table names in snake_case plural form
- Primary keys should be `id` (auto-incrementing)
- Foreign keys should follow `{table}_id` pattern (e.g., `user_id`)
- Use timestamps (`created_at`, `updated_at`) on all tables
- Use soft deletes where appropriate (`deleted_at`)
- Use Redis for caching and queue management
- Configure proper database indexing for performance

### Database Development & Refresh Commands
- **Always use Laravel's database refresh commands** for clean development database resets:
  - `herd php artisan migrate:fresh --seed` - Drop all tables, re-run migrations, execute all seeders
  - `herd php artisan migrate:refresh --seed` - Rollback and re-run migrations, then run seeders
  - `herd php artisan db:seed` - Run all seeders without touching migrations
  - `herd php artisan db:seed --class=SpecificSeederName` - Run individual seeders
- **Ensure proper seeder order**: RolePermissionSeeder must run before UserSeeder for Spatie Permission setup
- **Use Herd's PHP version** (`herd php artisan`) instead of system PHP for Laravel 11 compatibility (PHP 8.2+ required)
- **Spatie Permission Integration**: Always seed roles and permissions before creating users with role assignments

### File Organization
- Use Laravel Artisan commands for automation tasks (in `app/Console/Commands/`)
- Organize services in `app/Services/` directory
- Place facades in `app/Facades/` directory
- Store configuration files in `config/` with descriptive names
- Keep tests organized by feature/unit in respective directories
- Place test fixtures in `tests/Fixtures/` directory

### Frontend Development Standards
- **Use React 19.1.0+** for all JavaScript components
- **Use Tailwind CSS 4.1.11+** for styling (no custom CSS unless necessary)
- **Use Vite 6.2.4+** for build tooling and hot module replacement
- Write components in JSX format (`.jsx` extension)
- Use functional components with hooks
- Implement proper component prop validation
- Follow React best practices for state management
- Use Tailwind utility classes for responsive design
- Organize React components in `resources/js/components/`
- Use Laravel Vite plugin for asset compilation

### WordPress Plugin Testing
- Create dedicated test classes for WordPress plugin functionality
- Use Laravel's testing features to simulate WordPress environments
- Mock WordPress functions when testing plugin integrations
- Test both successful and error scenarios for plugin interactions

### AI Integration Best Practices
- Implement proper error handling for AI service calls
- Use queues for long-running AI operations
- Cache AI responses when appropriate
- Implement rate limiting for AI API calls
- Log AI interactions for debugging and monitoring

### Documentation Requirements
- Maintain clear README.md with setup instructions
- Document API endpoints with proper examples
- Include inline comments for complex business logic
- Keep PRD.md updated with feature specifications
- Document environment variables and configuration options

### Testing Standards
- **Use Pest PHP 3.0+** as the primary testing framework
- Write tests for all new features and bug fixes
- Maintain minimum 80% code coverage
- Use Pest's expressive syntax: `it('should do something', function() { ... })`
- Use `expect()` assertions instead of PHPUnit assertions
- Group related tests using `describe()` blocks
- Use factories for test data generation
- Leverage Pest's Laravel plugin for database testing
- Use `beforeEach()` and `afterEach()` for test setup/teardown

### Security Practices
- Validate all user inputs
- Use Laravel's built-in security features (CSRF, XSS protection)
- Implement proper authentication and authorization
- Sanitize data before database operations
- Use environment variables for sensitive configuration

### Performance Guidelines
- Use eager loading to prevent N+1 queries
- Implement database indexing for frequently queried columns
- Cache expensive operations
- Optimize images and assets
- Use Laravel's built-in caching mechanisms

### Git and Version Control
- Use descriptive commit messages
- Create feature branches for new development
- Keep commits atomic and focused
- Use pull requests for code review
- Tag releases with semantic versioning