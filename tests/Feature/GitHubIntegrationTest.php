<?php

use App\Models\User;
use App\Models\GitHubRepository;
use App\Services\GitHub\GitHubService;
use App\Services\GitHub\GitHubValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->githubService = app(GitHubService::class);
    $this->validationService = app(GitHubValidationService::class);
});

test('github validate endpoint requires authentication', function () {
    $this->post('/logout');

    $response = $this->postJson('/thinktest/github/validate', [
        'repository_url' => 'https://github.com/owner/repo'
    ]);

    $response->assertStatus(401);
});

test('github validate endpoint works with proper csrf token', function () {
    // Mock the GitHub service to avoid actual API calls
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('isRepositoryAccessible')
            ->with('owner', 'repo')
            ->andReturn(true);

        $mock->shouldReceive('getRepositoryInfo')
            ->with('owner', 'repo')
            ->andReturn([
                'id' => 123,
                'name' => 'repo',
                'full_name' => 'owner/repo',
                'description' => 'Test repository',
                'private' => false,
                'default_branch' => 'main',
                'size' => 1024,
                'language' => 'PHP',
                'clone_url' => 'https://github.com/owner/repo.git',
                'html_url' => 'https://github.com/owner/repo',
                'updated_at' => '2023-01-01T00:00:00Z',
            ]);
    });

    $response = $this->postJson('/thinktest/github/validate', [
        'repository_url' => 'https://github.com/owner/repo'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'repository' => [
                'owner',
                'repo',
                'full_name',
                'name',
                'description',
                'private',
                'default_branch',
                'size',
                'language',
            ]
        ]);
});

test('github validate endpoint rejects invalid urls', function () {
    $response = $this->postJson('/thinktest/github/validate', [
        'repository_url' => 'invalid-url'
    ]);

    $response->assertStatus(422);
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
    $response->assertJson([
        'success' => false
    ]);
});

test('github branches endpoint requires valid owner and repo', function () {
    $response = $this->postJson('/thinktest/github/branches', [
        'owner' => 'invalid-owner!',
        'repo' => 'invalid-repo!'
    ]);

    $response->assertStatus(422);
});

test('github process endpoint validates all required fields', function () {
    $response = $this->postJson('/thinktest/github/process', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['owner', 'repo', 'branch']);
});

test('github process endpoint rejects invalid branch names', function () {
    $response = $this->postJson('/thinktest/github/process', [
        'owner' => 'owner',
        'repo' => 'repo',
        'branch' => 'invalid..branch'
    ]);

    $response->assertStatus(422);
});

test('github process endpoint requires authentication', function () {
    // Clear rate limiter cache to avoid interference
    \Illuminate\Support\Facades\RateLimiter::clear('github_global_');
    \Illuminate\Support\Facades\RateLimiter::clear('github_minute_');

    $response = $this->postJson('/thinktest/github/process', [
        'owner' => 'octocat',
        'repo' => 'Hello-World',
        'branch' => 'master'
    ]);

    // Should get 401 from the rate limiting middleware since no user is authenticated
    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => 'Authentication required'
    ]);
});

test('authenticated user can access github process endpoint with proper validation', function () {
    $user = User::factory()->create();

    // Clear rate limiter cache to avoid interference
    \Illuminate\Support\Facades\RateLimiter::clear("github_global_{$user->id}");
    \Illuminate\Support\Facades\RateLimiter::clear("github_minute_{$user->id}");

    $response = $this->actingAs($user)->postJson('/thinktest/github/process', [
        'owner' => 'octocat',
        'repo' => 'Hello-World',
        'branch' => 'master'
    ]);

    // Should get validation error or other error, not authentication error
    $this->assertNotEquals(401, $response->getStatusCode());
    $this->assertNotEquals(403, $response->getStatusCode());
});

test('github process endpoint maintains session during request', function () {
    $user = User::factory()->create();

    // Clear rate limiter cache to avoid interference
    \Illuminate\Support\Facades\RateLimiter::clear("github_global_{$user->id}");
    \Illuminate\Support\Facades\RateLimiter::clear("github_minute_{$user->id}");

    // First, make a request to establish session
    $this->actingAs($user)->get('/thinktest');

    // Then make the GitHub process request
    $response = $this->actingAs($user)->postJson('/thinktest/github/process', [
        'owner' => 'octocat',
        'repo' => 'Hello-World',
        'branch' => 'master'
    ]);

    // Should not get authentication error
    $this->assertNotEquals(302, $response->getStatusCode());
    $this->assertNotEquals(401, $response->getStatusCode());
    $this->assertNotEquals(403, $response->getStatusCode());
});

test('session persistence with csrf token validation', function () {
    $user = User::factory()->create();

    // Create required permissions
    \Spatie\Permission\Models\Permission::create(['name' => 'generate tests', 'group_name' => 'ai-test-generation']);

    // Give user required permissions
    $user->givePermissionTo('generate tests');

    // Clear rate limiter cache
    \Illuminate\Support\Facades\RateLimiter::clear("github_global_{$user->id}");
    \Illuminate\Support\Facades\RateLimiter::clear("github_minute_{$user->id}");

    // Step 1: Login and get the ThinkTest page
    $response = $this->actingAs($user)->get('/thinktest');
    $response->assertStatus(200);

    // Step 2: Extract CSRF token from the response
    $content = $response->getContent();
    $this->assertStringContainsString('csrf-token', $content);

    // Step 3: Make GitHub process request with proper headers
    $response = $this->actingAs($user)
        ->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])
        ->postJson('/thinktest/github/process', [
            'owner' => 'octocat',
            'repo' => 'Hello-World',
            'branch' => 'master'
        ]);

    // Should not get CSRF or authentication errors
    $this->assertNotEquals(419, $response->getStatusCode()); // CSRF error
    $this->assertNotEquals(401, $response->getStatusCode()); // Auth error
    $this->assertNotEquals(302, $response->getStatusCode()); // Redirect to login
});

test('auth check endpoint works correctly', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/auth/check');

    $response->assertStatus(200);
    $response->assertJson([
        'authenticated' => true,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]
    ]);
    $response->assertJsonStructure([
        'authenticated',
        'user' => ['id', 'name', 'email'],
        'csrf_token'
    ]);
});

test('auth check endpoint requires authentication', function () {
    // Ensure we're not authenticated
    Auth::logout();
    $this->assertGuest();

    $response = $this->get('/auth/check');

    // Should redirect to login since the route is protected by auth middleware
    $response->assertRedirect('/login');
});

test('github repository model creates correctly', function () {
    $repo = GitHubRepository::create([
        'user_id' => $this->user->id,
        'owner' => 'test-owner',
        'repo' => 'test-repo',
        'full_name' => 'test-owner/test-repo',
        'branch' => 'main',
        'github_id' => 12345,
        'description' => 'Test repository',
        'is_private' => false,
        'default_branch' => 'main',
        'size_bytes' => 1024,
        'language' => 'PHP',
        'clone_url' => 'https://github.com/test-owner/test-repo.git',
        'html_url' => 'https://github.com/test-owner/test-repo',
        'processing_status' => 'pending',
    ]);

    expect($repo->user_id)->toBe($this->user->id);
    expect($repo->full_name)->toBe('test-owner/test-repo');
    expect($repo->isPending())->toBeTrue();
    expect($repo->isProcessed())->toBeFalse();
    expect($repo->repository_url)->toBe('https://github.com/test-owner/test-repo');
});

test('github repository model status methods work correctly', function () {
    $repo = GitHubRepository::create([
        'user_id' => $this->user->id,
        'owner' => 'test-owner',
        'repo' => 'test-repo',
        'full_name' => 'test-owner/test-repo',
        'branch' => 'main',
        'processing_status' => 'pending'
    ]);

    expect($repo->isPending())->toBeTrue();

    $repo->markAsProcessing();
    expect($repo->isProcessing())->toBeTrue();

    $repo->markAsCompleted(['type' => 'single_plugin'], 5);
    expect($repo->isProcessed())->toBeTrue();
    expect($repo->file_count)->toBe(5);

    $repo->markAsFailed('Test error');
    expect($repo->hasFailed())->toBeTrue();
    expect($repo->processing_error)->toBe('Test error');
});

test('github repository model relationships work', function () {
    $repo = GitHubRepository::create([
        'user_id' => $this->user->id,
        'owner' => 'test-owner',
        'repo' => 'test-repo',
        'full_name' => 'test-owner/test-repo',
        'branch' => 'main',
        'processing_status' => 'pending'
    ]);

    expect($repo->user)->toBeInstanceOf(User::class);
    expect($repo->user->id)->toBe($this->user->id);
});

// GitHub Service Tests
test('github service validates repository url format', function () {
    $validUrls = [
        'https://github.com/owner/repo',
        'https://github.com/owner/repo.git',
        'git@github.com:owner/repo.git',
        'owner/repo',
    ];

    foreach ($validUrls as $url) {
        $result = $this->githubService->validateRepositoryUrl($url);
        expect($result)->toHaveKeys(['owner', 'repo', 'full_name', 'url']);
    }
});

test('github service rejects invalid repository urls', function () {
    $invalidUrls = [
        '',
        'not-a-url',
        'https://example.com/owner/repo',
        'https://github.com/',
        'https://github.com/owner',
        'javascript:alert(1)',
        'data:text/html,<script>alert(1)</script>',
    ];

    foreach ($invalidUrls as $url) {
        expect(fn() => $this->githubService->validateRepositoryUrl($url))
            ->toThrow(InvalidArgumentException::class);
    }
});

// GitHub Validation Service Tests
test('validation service validates github names', function () {
    $validNames = ['owner', 'repo-name', 'repo_name', 'repo.name', 'a1b2c3'];

    foreach ($validNames as $name) {
        $repoData = [
            'owner' => $name,
            'repo' => $name,
            'full_name' => "{$name}/{$name}",
            'url' => "https://github.com/{$name}/{$name}",
        ];

        expect(fn() => $this->validationService->validateRepositoryComponents($repoData))
            ->not->toThrow(Exception::class);
    }
});

test('validation service rejects invalid names', function () {
    $invalidNames = [
        '-invalid',
        'invalid-',
        '.invalid',
        'invalid.',
        'inv--alid',
        str_repeat('a', 101), // Too long
        'api', // Reserved name
        'github', // Reserved name
    ];

    foreach ($invalidNames as $name) {
        $repoData = [
            'owner' => $name,
            'repo' => 'valid-repo',
            'full_name' => "{$name}/valid-repo",
            'url' => "https://github.com/{$name}/valid-repo",
        ];

        expect(fn() => $this->validationService->validateRepositoryComponents($repoData))
            ->toThrow(InvalidArgumentException::class);
    }
});

test('validation service validates branch names', function () {
    $validBranches = ['main', 'develop', 'feature/new-feature', 'hotfix-1.0', 'v1.0.0'];

    foreach ($validBranches as $branch) {
        expect(fn() => $this->validationService->validateBranchName($branch))
            ->not->toThrow(Exception::class);
    }
});

test('validation service rejects invalid branch names', function () {
    $invalidBranches = [
        '',
        '.invalid',
        'invalid/',
        'invalid//',
        'invalid..branch',
        'invalid.lock',
        str_repeat('a', 251), // Too long
    ];

    foreach ($invalidBranches as $branch) {
        expect(fn() => $this->validationService->validateBranchName($branch))
            ->toThrow(InvalidArgumentException::class);
    }
});

test('validation service validates repository size', function () {
    // Valid size (under 50MB)
    $validSize = 1024 * 1024 * 10; // 10MB
    expect(fn() => $this->validationService->validateRepositorySize($validSize))
        ->not->toThrow(Exception::class);

    // Invalid size (over 50MB)
    $invalidSize = 1024 * 1024 * 60; // 60MB
    expect(fn() => $this->validationService->validateRepositorySize($invalidSize))
        ->toThrow(RuntimeException::class);
});

test('validation service validates file count', function () {
    // Valid file count
    $validCount = 500;
    expect(fn() => $this->validationService->validateFileCount($validCount))
        ->not->toThrow(Exception::class);

    // Invalid file count
    $invalidCount = 1500;
    expect(fn() => $this->validationService->validateFileCount($invalidCount))
        ->toThrow(RuntimeException::class);
});

test('validation service sanitizes content', function () {
    $content = "<?php\necho 'Hello World';\n\0null byte";
    $sanitized = $this->validationService->sanitizeFileContent($content);

    expect($sanitized)->not->toContain("\0");
    expect($sanitized)->toContain("<?php");
    expect($sanitized)->toContain("Hello World");
});
