# Laravel Testing Guidelines with Pest PHP

## Testing Framework Standards

### Pest PHP Configuration
- Use Pest PHP as the primary testing framework
- Configure Pest in `tests/Pest.php`
- Use Laravel's testing utilities and assertions

### Pest.php Configuration
```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(
    Tests\TestCase::class,
    RefreshDatabase::class,
)->in('Feature');

uses(Tests\TestCase::class)->in('Unit');

// Custom expectations
expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
```

## Test Structure Standards

### Feature Tests
Feature tests should test complete user workflows and HTTP endpoints:

```php
<?php

// tests/Feature/PostTest.php

test('authenticated user can view posts index', function () {
    $user = User::factory()->create();
    $posts = Post::factory(3)->create();

    $this->actingAs($user)
         ->get('/posts')
         ->assertOk()
         ->assertViewIs('posts.index')
         ->assertViewHas('posts');
});

test('authenticated user can create a post', function () {
    $user = User::factory()->create();
    
    $postData = [
        'title' => 'Test Post',
        'content' => 'This is test content.',
    ];

    $this->actingAs($user)
         ->post('/posts', $postData)
         ->assertRedirect('/posts')
         ->assertSessionHas('success');

    $this->assertDatabaseHas('posts', [
        'title' => 'Test Post',
        'user_id' => $user->id,
    ]);
});

test('guest cannot access posts creation page', function () {
    $this->get('/posts/create')
         ->assertRedirect('/login');
});

test('user can only edit their own posts', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
         ->get("/posts/{$post->id}/edit")
         ->assertForbidden();
});
```

### Unit Tests
Unit tests should test individual methods and business logic:

```php
<?php

// tests/Unit/PostTest.php

test('post can generate excerpt', function () {
    $post = new Post([
        'content' => str_repeat('Lorem ipsum dolor sit amet. ', 20)
    ]);

    expect($post->excerpt)->toHaveLength(100);
    expect($post->excerpt)->toEndWith('...');
});

test('post scope published returns only published posts', function () {
    Post::factory()->create(['is_published' => true]);
    Post::factory()->create(['is_published' => false]);

    $publishedPosts = Post::published()->get();

    expect($publishedPosts)->toHaveCount(1);
    expect($publishedPosts->first()->is_published)->toBeTrue();
});

test('post belongs to user', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);

    expect($post->user)->toBeInstanceOf(User::class);
    expect($post->user->id)->toBe($user->id);
});
```

## Authentication Testing

### User Authentication Tests
```php
<?php

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

test('user cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email']);

    $this->assertGuest();
});

test('user can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
         ->post('/logout')
         ->assertRedirect('/');

    $this->assertGuest();
});
```

## Authorization Testing

### Role and Permission Tests
```php
<?php

test('admin can access admin dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
         ->get('/admin')
         ->assertOk();
});

test('regular user cannot access admin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
         ->get('/admin')
         ->assertForbidden();
});

test('user with permission can perform action', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('create posts');

    $this->actingAs($user)
         ->get('/posts/create')
         ->assertOk();
});

test('user without permission cannot perform action', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
         ->get('/posts/create')
         ->assertForbidden();
});
```

## API Testing

### JSON API Tests
```php
<?php

test('api returns posts in json format', function () {
    $posts = Post::factory(3)->create();

    $this->getJson('/api/posts')
         ->assertOk()
         ->assertJsonCount(3, 'data')
         ->assertJsonStructure([
             'data' => [
                 '*' => [
                     'id',
                     'title',
                     'content',
                     'created_at',
                     'updated_at',
                 ]
             ]
         ]);
});

test('api creates post with valid data', function () {
    $user = User::factory()->create();
    
    $postData = [
        'title' => 'API Test Post',
        'content' => 'This is API test content.',
    ];

    $this->actingAs($user)
         ->postJson('/api/posts', $postData)
         ->assertCreated()
         ->assertJsonFragment([
             'title' => 'API Test Post',
         ]);

    $this->assertDatabaseHas('posts', $postData);
});

test('api validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
         ->postJson('/api/posts', [])
         ->assertUnprocessable()
         ->assertJsonValidationErrors(['title', 'content']);
});
```

## Database Testing

### Database State Tests
```php
<?php

test('seeder creates default users', function () {
    $this->seed(UserSeeder::class);

    $this->assertDatabaseHas('users', [
        'email' => 'admin@example.com',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'developer@example.com',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'user@example.com',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'demo@example.com',
    ]);
});

test('user factory creates valid user', function () {
    $user = User::factory()->create();

    expect($user->name)->toBeString();
    expect($user->email)->toContain('@');
    expect($user->password)->toBeString();
    expect($user->email_verified_at)->toBeInstanceOf(Carbon::class);
});

test('post factory creates valid post', function () {
    $post = Post::factory()->create();

    expect($post->title)->toBeString();
    expect($post->content)->toBeString();
    expect($post->user_id)->toBeInt();
    expect($post->is_published)->toBeBool();
});
```

## Test Helpers and Utilities

### Custom Test Helpers
```php
<?php

// tests/TestCase.php

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    protected function createAdminUser(): User
    {
        return $this->createUserWithRole('admin');
    }

    protected function createRegularUser(): User
    {
        return $this->createUserWithRole('user');
    }

    protected function assertUserHasRole(User $user, string $role): void
    {
        $this->assertTrue($user->hasRole($role));
    }

    protected function assertUserHasPermission(User $user, string $permission): void
    {
        $this->assertTrue($user->hasPermissionTo($permission));
    }
}
```

### Using Test Helpers
```php
<?php

test('admin can manage users', function () {
    $admin = $this->createAdminUser();

    $this->actingAs($admin)
         ->get('/admin/users')
         ->assertOk();

    $this->assertUserHasRole($admin, 'admin');
    $this->assertUserHasPermission($admin, 'view users');
});
```

## Test Organization

### Test File Structure
```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   ├── RegisterTest.php
│   │   └── PasswordResetTest.php
│   ├── Admin/
│   │   ├── UserManagementTest.php
│   │   └── DashboardTest.php
│   ├── PostTest.php
│   └── CommentTest.php
├── Unit/
│   ├── Models/
│   │   ├── UserTest.php
│   │   └── PostTest.php
│   └── Services/
│       └── PostServiceTest.php
└── TestCase.php
```

### Test Naming Conventions
- Use descriptive test names that explain what is being tested
- Start with the subject being tested
- Use "can" or "cannot" for permission tests
- Use "should" for behavior tests

## Running Tests

### Pest Commands
```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/PostTest.php

# Run tests with coverage
./vendor/bin/pest --coverage

# Run tests in parallel
./vendor/bin/pest --parallel

# Run tests with specific filter
./vendor/bin/pest --filter="user can login"
```

### Continuous Integration
- Run tests on every commit
- Use GitHub Actions or similar CI/CD tools
- Ensure tests pass before merging

---

*These testing guidelines ensure comprehensive test coverage and maintainable test suites across all Laravel projects.*
