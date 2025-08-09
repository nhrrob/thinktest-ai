# Laravel Development Guidelines

## Overview
These guidelines establish comprehensive standards for Laravel development across all projects, ensuring consistency, best practices, and maintainability. These rules apply to all Laravel projects developed through Augment.

## Technology Stack Standards

### Core Framework
- **Laravel Version**: Laravel 12 (latest version)
- **PHP Version**: PHP 8.3+ (minimum required for Laravel 12)
- **Testing Framework**: Pest PHP (preferred over PHPUnit)
- **Frontend Stack**: React with Inertia.js
- **Styling**: Tailwind CSS
- **Package Manager**: Composer for PHP, npm/yarn for Node.js

### Required Packages
- **Authentication**: Laravel Breeze or Jetstream
- **Authorization**: Spatie Laravel Permission package
- **Testing**: Pest PHP with Laravel plugin
- **Frontend**: Inertia.js with React adapter

## Project Setup Protocol

### New Project Creation
1. **Always use the official Laravel installer**:
   ```bash
   composer create-project laravel/laravel project-name
   # OR
   laravel new project-name
   ```

2. **Follow all command-line prompts during installation**
   - Choose appropriate starter kit (Breeze/Jetstream)
   - Select testing framework (Pest PHP)
   - Configure frontend stack (React + Inertia)

3. **Never manually edit database files**
   - Use Laravel Artisan commands exclusively
   - Create migrations for all database changes
   - Use seeders for sample data

### Essential Artisan Commands
```bash
# Database operations
php artisan migrate
php artisan db:seed
php artisan make:migration
php artisan make:seeder

# Code generation
php artisan make:controller
php artisan make:model
php artisan make:request
php artisan make:resource
```

## Architecture Patterns

### Controller Standards
- **RESTful Resource Pattern**: All controllers must implement RESTful resource patterns
- **Required Methods**: Each resource controller must include all 7 standard methods:
  ```php
  public function index()     // Display listing
  public function create()    // Show creation form
  public function store()     // Store new resource
  public function show()      // Display specific resource
  public function edit()      // Show edit form
  public function update()    // Update specific resource
  public function destroy()   // Delete specific resource
  ```

### Naming Conventions
- **Controllers**: PascalCase with "Controller" suffix (e.g., `UserController`)
- **Models**: PascalCase singular (e.g., `User`, `BlogPost`)
- **Migrations**: Snake_case with descriptive action (e.g., `create_users_table`)
- **Routes**: Kebab-case for URLs (e.g., `/blog-posts`)
- **Variables**: camelCase (e.g., `$userName`)

### Directory Structure
Follow Laravel's standard directory structure:
```
app/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Providers/
└── Services/
```

### MVC Separation of Concerns
- **Models**: Handle data logic, relationships, and business rules
- **Views**: Handle presentation logic only
- **Controllers**: Handle HTTP requests, coordinate between models and views
- **Services**: Handle complex business logic (when needed)

## Authentication & Authorization

### Spatie Laravel Permission Integration
1. **Always install Spatie Laravel Permission**:
   ```bash
   composer require spatie/laravel-permission
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   php artisan migrate
   ```

2. **Model Configuration**:
   ```php
   use Spatie\Permission\Traits\HasRoles;

   class User extends Authenticatable
   {
       use HasRoles;
   }
   ```

### Authorization Implementation (Spatie-Based)
**IMPORTANT**: Use Spatie Laravel Permission's native authorization methods instead of Laravel policies.

1. **Permission Structure**:
   - Use granular CRUD permissions: `view {resource}`, `create {resource}`, `edit {resource}`, `delete {resource}`
   - Example: `view users`, `create users`, `edit users`, `delete users`
   - Avoid broad permissions like `manage users`

2. **Controller Authorization**:
   ```php
   use Illuminate\Support\Facades\Auth;

   public function index()
   {
       // Check permission using Spatie Laravel Permission
       if (!Auth::user()->can('view users')) {
           abort(403, 'Unauthorized action.');
       }

       // Controller logic...
   }
   ```

3. **Route Middleware**:
   ```php
   // Use granular permissions with OR operator for resource routes
   Route::middleware(['permission:view users|create users|edit users|delete users'])->group(function () {
       Route::resource('users', UserController::class);
   });
   ```

4. **Frontend Permission Checks**:
   ```tsx
   // Check for any of the granular permissions
   hasPermission = auth.user.permissions?.some(permission =>
       ['view users', 'create users', 'edit users', 'delete users'].includes(permission.name)
   ) || auth.user.roles?.some(role =>
       role.name === 'super-admin' || role.name === 'admin'
   ) || false;
   ```

5. **DO NOT USE**:
   - Laravel authorization policies
   - Broad "manage" permissions
   - Laravel's Gate system for Spatie permissions

### Default User Seeding
Create comprehensive user seeding with these specific accounts:

```php
// DatabaseSeeder.php
User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
])->assignRole('admin');

User::create([
    'name' => 'Developer User',
    'email' => 'developer@example.com',
    'password' => Hash::make('password'),
])->assignRole('developer');

User::create([
    'name' => 'Regular User',
    'email' => 'user@example.com',
    'password' => Hash::make('password'),
])->assignRole('user');

User::create([
    'name' => 'Demo User',
    'email' => 'demo@example.com',
    'password' => Hash::make('password'),
])->assignRole('demo');
```

### Password Standards
- **Default Password**: "password" for all development accounts
- **Hashing**: Always use Laravel's `Hash::make()` method
- **Production**: Enforce strong password policies in production

## UI/UX Standards

### Styling Framework
- **Primary**: Tailwind CSS utility classes
- **Consistency**: Follow Laravel Breeze/Jetstream design patterns
- **Responsive**: Mobile-first responsive design approach

### Blade Templating (when applicable)
- Use Laravel's blade templating best practices
- Implement component-based architecture
- Follow Laravel's naming conventions for blade files

### React + Inertia.js Standards
- Use functional components with hooks
- Implement proper TypeScript types (when using TypeScript)
- Follow React best practices for state management
- Use Inertia.js for seamless SPA experience

## Testing Standards

### Pest PHP Configuration
- Use Pest PHP as the primary testing framework
- Write feature tests for all major functionality
- Implement unit tests for complex business logic
- Use Laravel's testing utilities (factories, assertions)

### Test Structure
```php
// Feature Test Example
test('user can view dashboard', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user)
         ->get('/dashboard')
         ->assertOk()
         ->assertSee('Dashboard');
});
```

## Development Workflow

### Version Control
- Use semantic commit messages
- Create feature branches for new functionality
- Implement proper code review process

### Environment Management
- Use `.env` files for environment-specific configuration
- Never commit sensitive data to version control
- Use Laravel's configuration caching in production

### Performance Considerations
- Implement proper database indexing
- Use Laravel's query optimization techniques
- Implement caching strategies where appropriate

## Package Management

### Composer Dependencies
- Always use Composer for PHP package management
- Keep dependencies up to date
- Use semantic versioning constraints

### Node.js Dependencies
- Use npm or yarn for frontend dependencies
- Keep package.json updated
- Use lock files for consistent installations

## Security Best Practices

### Laravel Security Features
- Use Laravel's built-in CSRF protection
- Implement proper input validation
- Use Laravel's authentication guards
- Follow Laravel's authorization patterns

### Data Protection
- Use Laravel's encryption for sensitive data
- Implement proper database sanitization
- Follow GDPR compliance when applicable

## Deployment Standards

### Production Checklist
- Enable Laravel's configuration caching
- Use proper environment variables
- Implement proper logging
- Use Laravel's maintenance mode for updates

### Server Requirements
- PHP 8.3+
- Composer
- Node.js (for asset compilation)
- Database (MySQL/PostgreSQL)

---

*These guidelines ensure consistent, maintainable, and scalable Laravel applications across all projects developed through Augment.*
