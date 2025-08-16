# ThinkTest AI Project Guidelines

## Project Overview
ThinkTest AI is a Laravel-based application for generating WordPress/Elementor plugin tests using AI providers like OpenAI GPT-5 and Anthropic Claude.

## Key Features
- AI-powered test generation for WordPress plugins
- GitHub repository integration for code analysis
- Credit-based payment system with Stripe integration
- Multi-provider AI support (OpenAI, Anthropic, Mock)
- Role-based access control with Spatie Laravel Permission
- Inertia.js with React frontend
- Comprehensive test infrastructure detection

## Architecture Patterns
- **Controllers**: Use constructor-based permission checking following nhrrob/laravel-get-started-project patterns
- **Authorization**: Always use Spatie Laravel Permission's native methods (Auth::user()->can())
- **Permissions**: Implement granular CRUD permissions (view/create/edit/delete)
- **Admin Panel**: Organize controllers in Admin folder with consistent sidebar navigation
- **Forms**: Use consolidated form request classes (single class for create/update operations)

## AI Provider Integration
- **Service Pattern**: Use AIProviderService for all AI interactions
- **Mock Provider**: Always check for mock provider before API calls
- **Credit System**: Integrate with CreditService for usage tracking
- **Error Handling**: Implement comprehensive error handling for API failures

## GitHub Integration
- **Rate Limiting**: Use GitHubRateLimitMiddleware for all GitHub routes
- **Validation**: Use GitHubValidationService for repository URL and branch validation
- **Authentication**: Require authentication for all GitHub operations
- **Caching**: Cache repository information to reduce API calls

## Frontend Guidelines
- **Framework**: Use Inertia.js with React and TypeScript
- **Styling**: Use Tailwind CSS with dark mode support
- **Components**: Create reusable components for common UI patterns
- **State Management**: Use React hooks for local state, Inertia for server state

## Testing Standards
- **Framework**: Use Pest for feature tests
- **Coverage**: Implement comprehensive tests for each feature integration
- **Mocking**: Mock external APIs (GitHub, Stripe, AI providers) in tests
- **Database**: Use RefreshDatabase trait for database tests

## Database Conventions
- **Naming**: Use MySQL with 'thinktest-ai' database name
- **Migrations**: Follow Laravel migration conventions
- **Models**: Use Eloquent relationships and proper model organization

## Security Guidelines
- **Authentication**: Use Laravel's built-in authentication with GitHub OAuth
- **Authorization**: Implement role-based access control
- **API Security**: Validate all inputs and sanitize outputs
- **Rate Limiting**: Implement rate limiting for external API calls

## Code Quality
- **Standards**: Follow PSR-12 coding standards
- **Documentation**: Document all public methods and complex logic
- **Error Handling**: Use proper exception handling and logging
- **Performance**: Optimize database queries and cache frequently accessed data
