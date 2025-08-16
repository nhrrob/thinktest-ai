# Testing Standards and Guidelines

## Overview

This document establishes comprehensive testing standards for the ThinkTest AI project to ensure code quality, reliability, and maintainability.

## Testing Philosophy

### Core Principles
1. **Test-Driven Development (TDD)**: Write tests before implementation when possible
2. **Comprehensive Coverage**: Aim for 80%+ overall coverage, 90%+ for critical components
3. **Fast Feedback**: Tests should run quickly and provide immediate feedback
4. **Reliable Tests**: Tests should be deterministic and not flaky
5. **Clear Intent**: Tests should clearly express what they're testing and why

### Testing Pyramid
- **Unit Tests (70%)**: Test individual methods and classes in isolation
- **Integration Tests (20%)**: Test component interactions and API endpoints
- **End-to-End Tests (10%)**: Test complete user workflows

## Coverage Requirements

### Minimum Coverage Targets
- **Overall Project**: 80%
- **Critical Components**: 90%
  - Authentication & Authorization
  - Payment Processing
  - AI Provider Services
  - File Processing & Analysis
  - Credit System
- **Controllers**: 85%
- **Services**: 90%
- **Models**: 80%
- **Utilities**: 95%

### Coverage Exclusions
- Configuration files
- Database migrations
- Blade templates (covered by browser tests)
- Third-party integrations (mocked in tests)

## Test Organization

### Directory Structure
```
tests/
├── Feature/           # Integration and feature tests
├── Unit/             # Unit tests
├── Support/          # Test utilities and helpers
│   ├── ApiMockService.php
│   ├── MocksExternalApis.php
│   └── TestDataFactory.php
└── Browser/          # End-to-end browser tests (if needed)
```

### Naming Conventions
- **Test Files**: `{ClassName}Test.php` for unit tests, `{FeatureName}Test.php` for feature tests
- **Test Methods**: `test_method_name_scenario()` using snake_case
- **Test Classes**: Match the class being tested with `Test` suffix

## Test Categories

### Unit Tests
**Purpose**: Test individual methods and classes in isolation

**Guidelines**:
- Mock all external dependencies
- Test one method per test case
- Cover happy path, edge cases, and error conditions
- Use descriptive test names that explain the scenario

**Example**:
```php
test('credit service calculates balance correctly', function () {
    $user = User::factory()->create();
    $creditService = new CreditService();
    
    $creditService->addCredits($user->id, 10.0, 'Test');
    $creditService->deductCredits($user->id, 3.0, 'Usage');
    
    expect($creditService->getUserBalance($user->id))->toBe(7.0);
});
```

### Integration Tests
**Purpose**: Test component interactions and API endpoints

**Guidelines**:
- Use database transactions for isolation
- Mock external APIs but test internal service interactions
- Test complete request/response cycles
- Verify database state changes

**Example**:
```php
test('payment controller creates payment intent successfully', function () {
    $user = TestDataFactory::createUserWithCredits();
    $package = CreditPackage::where('slug', 'starter-pack')->first();
    
    $response = $this->actingAs($user)->postJson('/credits/payment-intent', [
        'package_id' => $package->id,
    ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'client_secret', 'payment_intent_id']);
});
```

### Feature Tests
**Purpose**: Test complete user workflows and business logic

**Guidelines**:
- Test from user perspective
- Include authentication and authorization
- Test error handling and edge cases
- Use realistic test data

## Test Data Management

### Using TestDataFactory
Always use the `TestDataFactory` class for creating test data:

```php
// Create user with permissions
$user = TestDataFactory::createUserWithPermissions(['generate tests']);

// Create user with credits
$user = TestDataFactory::createUserWithCredits(25.0);

// Create admin user
$admin = TestDataFactory::createAdminUser();

// Create test repositories
$repo = TestDataFactory::createGitHubRepository($user);
```

### Database Seeding
- Use seeders for consistent test data setup
- Seed credit packages before payment tests
- Create required permissions before authorization tests

### Data Cleanup
- Use database transactions for automatic cleanup
- Call `TestDataFactory::cleanup()` for manual cleanup when needed
- Avoid leaving test data in the database

## Mocking Guidelines

### External APIs
Always mock external APIs using the `MocksExternalApis` trait:

```php
use Tests\Support\MocksExternalApis;

uses(RefreshDatabase::class, MocksExternalApis::class);

beforeEach(function () {
    $this->setUpApiMocks(); // Mocks GitHub, OpenAI, Anthropic, Stripe
});
```

### Service Mocking
Mock services when testing controllers or higher-level components:

```php
$this->mock(AIProviderService::class, function ($mock) {
    $mock->shouldReceive('generateTests')
        ->andReturn(['success' => true, 'tests' => 'test content']);
});
```

### Partial Mocking
Use partial mocking sparingly and only when necessary:

```php
$service = $this->partialMock(TestGenerationService::class, function ($mock) {
    $mock->shouldReceive('callExternalApi')->andReturn('mocked response');
});
```

## Assertion Guidelines

### Preferred Assertions
- Use `expect()` syntax for better readability
- Use specific assertions over generic ones
- Test both positive and negative cases

```php
// Good
expect($result)->toBeTrue();
expect($user->credits)->toBe(10.0);
expect($response)->toHaveKey('success');

// Avoid
$this->assertTrue($result);
$this->assertEquals(10.0, $user->credits);
$this->assertArrayHasKey('success', $response);
```

### HTTP Response Testing
```php
$response->assertStatus(200)
    ->assertJson(['success' => true])
    ->assertJsonStructure([
        'success',
        'data' => ['id', 'name', 'email']
    ]);
```

### Database Testing
```php
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseCount('credit_transactions', 1);
```

## Performance Standards

### Test Execution Time
- **Unit Tests**: < 100ms per test
- **Integration Tests**: < 500ms per test
- **Feature Tests**: < 2s per test
- **Full Test Suite**: < 5 minutes

### Optimization Techniques
- Use database transactions instead of migrations
- Mock external services
- Use in-memory databases for faster tests
- Parallelize test execution when possible

## Error Handling Tests

### Exception Testing
```php
test('service throws exception for invalid input', function () {
    $service = new ValidationService();
    
    expect(fn() => $service->validate(''))
        ->toThrow(InvalidArgumentException::class, 'Input cannot be empty');
});
```

### Error Response Testing
```php
test('api returns validation errors', function () {
    $response = $this->postJson('/api/endpoint', []);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['required_field']);
});
```

## Continuous Integration

### Test Execution
- Run full test suite on every pull request
- Run critical tests on every commit
- Generate coverage reports automatically
- Fail builds if coverage drops below threshold

### Quality Gates
- Minimum 80% coverage required
- All tests must pass
- No critical security vulnerabilities
- Code style checks must pass

## Best Practices

### Do's
- Write tests for all new features
- Update tests when changing existing code
- Use descriptive test names
- Test edge cases and error conditions
- Keep tests simple and focused
- Use appropriate mocking
- Clean up test data

### Don'ts
- Don't test framework code
- Don't write overly complex tests
- Don't ignore failing tests
- Don't test implementation details
- Don't use real external APIs in tests
- Don't leave commented-out test code
- Don't skip tests without good reason

## Test Review Checklist

### Code Review Items
- [ ] Tests cover new functionality
- [ ] Tests are well-named and descriptive
- [ ] Appropriate mocking is used
- [ ] Edge cases are covered
- [ ] Error conditions are tested
- [ ] Tests are not overly complex
- [ ] Test data is properly managed
- [ ] Coverage requirements are met

### Common Issues to Avoid
- Flaky tests due to timing issues
- Tests that depend on external services
- Tests that modify global state
- Overly complex test setup
- Testing implementation instead of behavior
- Missing error case testing
- Inadequate test data cleanup
