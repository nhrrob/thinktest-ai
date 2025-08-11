<?php

use App\Models\User;
use App\Models\GitHubRepository;
use App\Services\GitHub\GitHubService;
use App\Services\GitHub\GitHubValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
