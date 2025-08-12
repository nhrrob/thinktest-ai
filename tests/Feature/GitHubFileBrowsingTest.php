<?php

use App\Models\GitHubFileTestGeneration;
use App\Models\GitHubRepository;
use App\Models\User;
use App\Services\GitHub\GitHubService;
use App\Services\GitHub\GitHubValidationService;
use App\Services\TestGeneration\TestGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->githubService = app(GitHubService::class);
    $this->validationService = app(GitHubValidationService::class);
    $this->testGenerationService = app(TestGenerationService::class);
});

test('github browse endpoint requires authentication', function () {
    $this->post('/logout');

    $response = $this->postJson('/thinktest/github/browse', [
        'owner' => 'owner',
        'repo' => 'repo',
    ]);

    $response->assertStatus(401);
});

test('github browse endpoint validates required parameters', function () {
    $response = $this->postJson('/thinktest/github/browse', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['owner', 'repo']);
});

test('github browse endpoint returns repository contents', function () {
    // Mock the GitHub service
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('getRepositoryContents')
            ->with('owner', 'repo', '', null)
            ->andReturn([
                [
                    'name' => 'index.php',
                    'path' => 'index.php',
                    'type' => 'file',
                    'size' => 1024,
                    'sha' => 'abc123',
                    'url' => 'https://api.github.com/repos/owner/repo/contents/index.php',
                    'html_url' => 'https://github.com/owner/repo/blob/main/index.php',
                    'download_url' => 'https://raw.githubusercontent.com/owner/repo/main/index.php',
                ],
                [
                    'name' => 'src',
                    'path' => 'src',
                    'type' => 'dir',
                    'size' => 0,
                    'sha' => 'def456',
                    'url' => 'https://api.github.com/repos/owner/repo/contents/src',
                    'html_url' => 'https://github.com/owner/repo/tree/main/src',
                    'download_url' => null,
                ],
            ]);
    });

    // Mock validation service
    $this->mock(GitHubValidationService::class, function ($mock) {
        $mock->shouldReceive('validateRateLimit')->with($this->user->id)->andReturn(true);
        $mock->shouldReceive('validateRepositoryComponents')->andReturn(true);
    });

    $response = $this->postJson('/thinktest/github/browse', [
        'owner' => 'owner',
        'repo' => 'repo',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'contents' => [
                [
                    'name' => 'index.php',
                    'path' => 'index.php',
                    'type' => 'file',
                ],
                [
                    'name' => 'src',
                    'path' => 'src',
                    'type' => 'dir',
                ],
            ],
        ]);
});

test('github tree endpoint returns repository tree structure', function () {
    // Mock the GitHub service
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('getRepositoryTree')
            ->with('owner', 'repo', null, true)
            ->andReturn([
                [
                    'path' => 'index.php',
                    'type' => 'file',
                    'sha' => 'abc123',
                    'size' => 1024,
                    'url' => 'https://api.github.com/repos/owner/repo/git/blobs/abc123',
                ],
                [
                    'path' => 'src/class.php',
                    'type' => 'file',
                    'sha' => 'def456',
                    'size' => 2048,
                    'url' => 'https://api.github.com/repos/owner/repo/git/blobs/def456',
                ],
            ]);
    });

    // Mock validation service
    $this->mock(GitHubValidationService::class, function ($mock) {
        $mock->shouldReceive('validateRateLimit')->with($this->user->id)->andReturn(true);
        $mock->shouldReceive('validateRepositoryComponents')->andReturn(true);
    });

    $response = $this->postJson('/thinktest/github/tree', [
        'owner' => 'owner',
        'repo' => 'repo',
        'recursive' => true,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'tree' => [
                [
                    'path' => 'index.php',
                    'type' => 'file',
                ],
                [
                    'path' => 'src/class.php',
                    'type' => 'file',
                ],
            ],
        ]);
});

test('github file endpoint returns file content', function () {
    // Mock the GitHub service
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('getFileContent')
            ->with('owner', 'repo', 'index.php', null)
            ->andReturn([
                'name' => 'index.php',
                'path' => 'index.php',
                'content' => '<?php echo "Hello World"; ?>',
                'size' => 29,
                'sha' => 'abc123',
                'encoding' => 'base64',
                'url' => 'https://api.github.com/repos/owner/repo/contents/index.php',
                'html_url' => 'https://github.com/owner/repo/blob/main/index.php',
                'download_url' => 'https://raw.githubusercontent.com/owner/repo/main/index.php',
            ]);
    });

    // Mock validation service
    $this->mock(GitHubValidationService::class, function ($mock) {
        $mock->shouldReceive('validateRateLimit')->with($this->user->id)->andReturn(true);
        $mock->shouldReceive('validateRepositoryComponents')->andReturn(true);
    });

    $response = $this->postJson('/thinktest/github/file', [
        'owner' => 'owner',
        'repo' => 'repo',
        'path' => 'index.php',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'file' => [
                'name' => 'index.php',
                'path' => 'index.php',
                'content' => '<?php echo "Hello World"; ?>',
            ],
        ]);
});

test('single file test generation endpoint works correctly', function () {
    // Disable middleware for this test
    $this->withoutMiddleware();

    // Mock validation service first to avoid rate limiting
    $this->mock(GitHubValidationService::class, function ($mock) {
        $mock->shouldReceive('validateRateLimit')->with($this->user->id)->andReturn(true);
        $mock->shouldReceive('validateRepositoryComponents')->andReturn(true);
    });

    // Mock the GitHub service
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('getFileContent')
            ->with('owner', 'repo', 'index.php', null)
            ->andReturn([
                'name' => 'index.php',
                'path' => 'index.php',
                'content' => '<?php echo "Hello World"; ?>',
                'size' => 29,
                'sha' => 'abc123',
                'encoding' => 'base64',
                'url' => 'https://api.github.com/repos/owner/repo/contents/index.php',
                'html_url' => 'https://github.com/owner/repo/blob/main/index.php',
                'download_url' => 'https://raw.githubusercontent.com/owner/repo/main/index.php',
            ]);

        $mock->shouldReceive('getRepositoryInfo')
            ->with('owner', 'repo')
            ->andReturn([
                'id' => 123,
                'name' => 'repo',
                'full_name' => 'owner/repo',
                'description' => 'Test repository',
                'private' => false,
                'default_branch' => 'main',
                'language' => 'PHP',
                'html_url' => 'https://github.com/owner/repo',
            ]);
    });

    // Mock test generation service
    $this->mock(TestGenerationService::class, function ($mock) {
        $mock->shouldReceive('generateTestsForSingleFile')
            ->andReturn([
                'success' => true,
                'framework' => 'phpunit',
                'provider' => 'openai-gpt5',
                'model' => 'gpt-5',
                'analysis' => ['functions' => [], 'classes' => []],
                'tests' => ['main_test_file' => ['filename' => 'IndexTest.php', 'content' => 'test content']],
                'main_test_file' => 'test content',
                'file_context' => [
                    'filename' => 'index.php',
                    'file_path' => 'index.php',
                    'repository' => ['full_name' => 'owner/repo'],
                ],
            ]);
    });

    $response = $this->postJson('/thinktest/generate-single-file', [
        'owner' => 'owner',
        'repo' => 'repo',
        'file_path' => 'index.php',
        'provider' => 'openai-gpt5',
        'framework' => 'phpunit',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'tests' => 'test content',
            'framework' => 'phpunit',
        ]);

    // Verify database records were created
    $this->assertDatabaseHas('github_repositories', [
        'owner' => 'owner',
        'repo' => 'repo',
    ]);

    $this->assertDatabaseHas('github_file_test_generations', [
        'file_path' => 'index.php',
        'file_name' => 'index.php',
        'generation_status' => 'completed',
    ]);
});

test('github file test generation handles duplicate file regeneration correctly', function () {
    // Mock GitHub service
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('getFileContent')
            ->with('owner', 'repo', 'index.php', null)
            ->andReturn([
                'name' => 'index.php',
                'path' => 'index.php',
                'content' => '<?php echo "Hello World"; ?>',
                'size' => 29,
                'sha' => 'abc123',
                'encoding' => 'base64',
                'url' => 'https://api.github.com/repos/owner/repo/contents/index.php',
                'html_url' => 'https://github.com/owner/repo/blob/main/index.php',
                'download_url' => 'https://raw.githubusercontent.com/owner/repo/main/index.php',
            ]);

        $mock->shouldReceive('getRepositoryInfo')
            ->with('owner', 'repo')
            ->andReturn([
                'id' => 123,
                'name' => 'repo',
                'full_name' => 'owner/repo',
                'description' => 'Test repository',
                'private' => false,
                'default_branch' => 'main',
                'language' => 'PHP',
                'html_url' => 'https://github.com/owner/repo',
            ]);
    });

    // Mock test generation service
    $this->mock(TestGenerationService::class, function ($mock) {
        $mock->shouldReceive('generateTestsForSingleFile')
            ->andReturn([
                'success' => true,
                'framework' => 'phpunit',
                'provider' => 'openai-gpt5',
                'model' => 'gpt-5',
                'analysis' => ['functions' => [], 'classes' => []],
                'tests' => ['main_test_file' => ['filename' => 'IndexTest.php', 'content' => 'updated test content']],
                'main_test_file' => 'updated test content',
                'file_context' => [
                    'filename' => 'index.php',
                    'file_path' => 'index.php',
                    'repository' => ['full_name' => 'owner/repo'],
                ],
            ]);
    });

    // First generation
    $response1 = $this->postJson('/thinktest/generate-single-file', [
        'owner' => 'owner',
        'repo' => 'repo',
        'file_path' => 'index.php',
        'provider' => 'openai-gpt5',
        'framework' => 'phpunit',
    ]);

    $response1->assertStatus(200)
        ->assertJson([
            'success' => true,
            'framework' => 'phpunit',
        ]);

    // Verify first record was created
    $this->assertDatabaseHas('github_file_test_generations', [
        'file_path' => 'index.php',
        'file_name' => 'index.php',
        'generation_status' => 'completed',
    ]);

    $initialCount = GitHubFileTestGeneration::count();

    // Second generation (should update, not create new record)
    $response2 = $this->postJson('/thinktest/generate-single-file', [
        'owner' => 'owner',
        'repo' => 'repo',
        'file_path' => 'index.php',
        'provider' => 'openai-gpt5',
        'framework' => 'phpunit',
    ]);

    $response2->assertStatus(200)
        ->assertJson([
            'success' => true,
            'framework' => 'phpunit',
        ]);

    // Verify no duplicate record was created
    expect(GitHubFileTestGeneration::count())->toBe($initialCount);

    // Verify the record was updated with new content
    $this->assertDatabaseHas('github_file_test_generations', [
        'file_path' => 'index.php',
        'file_name' => 'index.php',
        'generation_status' => 'completed',
    ]);
});

test('github file test generation handles different providers and frameworks for same file', function () {
    // Mock GitHub service
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('getFileContent')
            ->with('owner', 'repo', 'index.php', null)
            ->andReturn([
                'name' => 'index.php',
                'path' => 'index.php',
                'content' => '<?php echo "Hello World"; ?>',
                'size' => 29,
                'sha' => 'abc123',
                'encoding' => 'base64',
                'url' => 'https://api.github.com/repos/owner/repo/contents/index.php',
                'html_url' => 'https://github.com/owner/repo/blob/main/index.php',
                'download_url' => 'https://raw.githubusercontent.com/owner/repo/main/index.php',
            ]);

        $mock->shouldReceive('getRepositoryInfo')
            ->with('owner', 'repo')
            ->andReturn([
                'id' => 123,
                'name' => 'repo',
                'full_name' => 'owner/repo',
                'description' => 'Test repository',
                'private' => false,
                'default_branch' => 'main',
                'language' => 'PHP',
                'html_url' => 'https://github.com/owner/repo',
            ]);
    });

    // Mock test generation service
    $this->mock(TestGenerationService::class, function ($mock) {
        $mock->shouldReceive('generateTestsForSingleFile')
            ->andReturn([
                'success' => true,
                'framework' => 'phpunit',
                'provider' => 'openai-gpt5',
                'model' => 'gpt-5',
                'analysis' => ['functions' => [], 'classes' => []],
                'tests' => ['main_test_file' => ['filename' => 'IndexTest.php', 'content' => 'test content']],
                'main_test_file' => 'test content',
                'file_context' => [
                    'filename' => 'index.php',
                    'file_path' => 'index.php',
                    'repository' => ['full_name' => 'owner/repo'],
                ],
            ]);
    });

    // First generation with OpenAI + PHPUnit
    $response1 = $this->postJson('/thinktest/generate-single-file', [
        'owner' => 'owner',
        'repo' => 'repo',
        'file_path' => 'index.php',
        'provider' => 'openai-gpt5',
        'framework' => 'phpunit',
    ]);

    $response1->assertStatus(200);

    // Verify record was created
    $this->assertDatabaseHas('github_file_test_generations', [
        'file_path' => 'index.php',
        'provider' => 'openai-gpt5',
        'framework' => 'phpunit',
    ]);

    $initialCount = GitHubFileTestGeneration::count();

    // Second generation with different provider but same framework (should update)
    $response2 = $this->postJson('/thinktest/generate-single-file', [
        'owner' => 'owner',
        'repo' => 'repo',
        'file_path' => 'index.php',
        'provider' => 'anthropic-claude',
        'framework' => 'phpunit',
    ]);

    $response2->assertStatus(200);

    // Should still be same count (updated, not created new)
    expect(GitHubFileTestGeneration::count())->toBe($initialCount);

    // Verify the record was updated with new provider
    $this->assertDatabaseHas('github_file_test_generations', [
        'file_path' => 'index.php',
        'provider' => 'anthropic-claude',
        'framework' => 'phpunit',
    ]);

    // Third generation with different framework (should update)
    $response3 = $this->postJson('/thinktest/generate-single-file', [
        'owner' => 'owner',
        'repo' => 'repo',
        'file_path' => 'index.php',
        'provider' => 'anthropic-claude',
        'framework' => 'pest',
    ]);

    $response3->assertStatus(200);

    // Should still be same count (updated, not created new)
    expect(GitHubFileTestGeneration::count())->toBe($initialCount);

    // Verify the record was updated with new framework
    $this->assertDatabaseHas('github_file_test_generations', [
        'file_path' => 'index.php',
        'provider' => 'anthropic-claude',
        'framework' => 'pest',
    ]);
});

test('github file test generation creates new record when file content changes', function () {
    // Mock GitHub service for first call
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('getFileContent')
            ->with('owner', 'repo', 'index.php', null)
            ->andReturnUsing(function () {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    return [
                        'name' => 'index.php',
                        'path' => 'index.php',
                        'content' => '<?php echo "Hello World"; ?>',
                        'size' => 29,
                        'sha' => 'abc123',
                        'encoding' => 'base64',
                        'url' => 'https://api.github.com/repos/owner/repo/contents/index.php',
                        'html_url' => 'https://github.com/owner/repo/blob/main/index.php',
                        'download_url' => 'https://raw.githubusercontent.com/owner/repo/main/index.php',
                    ];
                } else {
                    return [
                        'name' => 'index.php',
                        'path' => 'index.php',
                        'content' => '<?php echo "Hello Updated World"; ?>',
                        'size' => 37,
                        'sha' => 'def456',
                        'encoding' => 'base64',
                        'url' => 'https://api.github.com/repos/owner/repo/contents/index.php',
                        'html_url' => 'https://github.com/owner/repo/blob/main/index.php',
                        'download_url' => 'https://raw.githubusercontent.com/owner/repo/main/index.php',
                    ];
                }
            });

        $mock->shouldReceive('getRepositoryInfo')
            ->with('owner', 'repo')
            ->andReturn([
                'id' => 123,
                'name' => 'repo',
                'full_name' => 'owner/repo',
                'description' => 'Test repository',
                'private' => false,
                'default_branch' => 'main',
                'language' => 'PHP',
                'html_url' => 'https://github.com/owner/repo',
            ]);
    });

    // Mock test generation service
    $this->mock(TestGenerationService::class, function ($mock) {
        $mock->shouldReceive('generateTestsForSingleFile')
            ->andReturn([
                'success' => true,
                'framework' => 'phpunit',
                'provider' => 'openai-gpt5',
                'model' => 'gpt-5',
                'analysis' => ['functions' => [], 'classes' => []],
                'tests' => ['main_test_file' => ['filename' => 'IndexTest.php', 'content' => 'test content']],
                'main_test_file' => 'test content',
                'file_context' => [
                    'filename' => 'index.php',
                    'file_path' => 'index.php',
                    'repository' => ['full_name' => 'owner/repo'],
                ],
            ]);
    });

    // First generation with original content
    $response1 = $this->postJson('/thinktest/generate-single-file', [
        'owner' => 'owner',
        'repo' => 'repo',
        'file_path' => 'index.php',
        'provider' => 'openai-gpt5',
        'framework' => 'phpunit',
    ]);

    $response1->assertStatus(200);

    $initialCount = GitHubFileTestGeneration::count();

    // Second generation with updated content (different hash)
    $response2 = $this->postJson('/thinktest/generate-single-file', [
        'owner' => 'owner',
        'repo' => 'repo',
        'file_path' => 'index.php',
        'provider' => 'openai-gpt5',
        'framework' => 'phpunit',
    ]);

    $response2->assertStatus(200);

    // Should create a new record since content hash is different
    expect(GitHubFileTestGeneration::count())->toBe($initialCount + 1);

    // Verify both records exist with different content hashes
    $this->assertDatabaseHas('github_file_test_generations', [
        'file_path' => 'index.php',
        'file_content_hash' => hash('sha256', '<?php echo "Hello World"; ?>'),
    ]);

    $this->assertDatabaseHas('github_file_test_generations', [
        'file_path' => 'index.php',
        'file_content_hash' => hash('sha256', '<?php echo "Hello Updated World"; ?>'),
    ]);
});

test('github file test generation model relationships work correctly', function () {
    $githubRepo = GitHubRepository::factory()->create([
        'owner' => 'owner',
        'repo' => 'repo',
        'user_id' => $this->user->id,
    ]);

    $fileTestGeneration = GitHubFileTestGeneration::create([
        'user_id' => $this->user->id,
        'github_repository_id' => $githubRepo->id,
        'file_path' => 'index.php',
        'file_name' => 'index.php',
        'file_sha' => 'abc123',
        'file_size' => 1024,
        'branch' => 'main',
        'provider' => 'openai-gpt5',
        'framework' => 'phpunit',
        'file_content_hash' => hash('sha256', 'test content'),
        'generation_status' => 'completed',
    ]);

    // Test relationships
    expect($fileTestGeneration->user)->toBeInstanceOf(User::class);
    expect($fileTestGeneration->githubRepository)->toBeInstanceOf(GitHubRepository::class);
    expect($githubRepo->fileTestGenerations)->toHaveCount(1);
    expect($fileTestGeneration->isSuccessful())->toBeTrue();
});

test('github tree filtering correctly includes supported file extensions', function () {
    // Mock the GitHub service directly instead of the client
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('getRepositoryTree')
            ->with('owner', 'repo', 'main', true)
            ->andReturn([
                // PHP files (should be included)
                ['path' => 'index.php', 'type' => 'file', 'sha' => 'sha1', 'size' => 100, 'url' => 'url1'],
                ['path' => 'includes/Admin.php', 'type' => 'file', 'sha' => 'sha2', 'size' => 200, 'url' => 'url2'],

                // JS files (should be included)
                ['path' => 'assets/js/script.js', 'type' => 'file', 'sha' => 'sha3', 'size' => 300, 'url' => 'url3'],

                // JSON files (should be included)
                ['path' => 'package.json', 'type' => 'file', 'sha' => 'sha4', 'size' => 400, 'url' => 'url4'],

                // Directories (should be included)
                ['path' => 'includes', 'type' => 'dir', 'sha' => 'sha9', 'size' => 0, 'url' => 'url9'],
                ['path' => 'assets', 'type' => 'dir', 'sha' => 'sha10', 'size' => 0, 'url' => 'url10'],
            ]);
    });

    // Mock validation service
    $this->mock(GitHubValidationService::class, function ($mock) {
        $mock->shouldReceive('validateRateLimit')->with($this->user->id)->andReturn(true);
        $mock->shouldReceive('validateRepositoryComponents')->andReturn(true);
        $mock->shouldReceive('validateBranchName')->with('main')->andReturn(true);
    });

    $response = $this->postJson('/thinktest/github/tree', [
        'owner' => 'owner',
        'repo' => 'repo',
        'branch' => 'main',
        'recursive' => true,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'tree' => [
                ['path' => 'index.php', 'type' => 'file'],
                ['path' => 'includes/Admin.php', 'type' => 'file'],
                ['path' => 'assets/js/script.js', 'type' => 'file'],
                ['path' => 'package.json', 'type' => 'file'],
                ['path' => 'includes', 'type' => 'dir'],
                ['path' => 'assets', 'type' => 'dir'],
            ],
        ]);

    // Verify the response contains the expected number of items
    $responseData = $response->json();
    expect($responseData['tree'])->toHaveCount(6); // 4 files + 2 directories

    // Check that supported files are included
    $filePaths = collect($responseData['tree'])->where('type', 'file')->pluck('path')->toArray();
    expect($filePaths)->toContain('index.php');
    expect($filePaths)->toContain('includes/Admin.php');
    expect($filePaths)->toContain('assets/js/script.js');
    expect($filePaths)->toContain('package.json');

    // Check that allowed directories are included
    $dirPaths = collect($responseData['tree'])->where('type', 'dir')->pluck('path')->toArray();
    expect($dirPaths)->toContain('includes');
    expect($dirPaths)->toContain('assets');
});
