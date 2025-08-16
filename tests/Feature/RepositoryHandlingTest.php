<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MocksExternalApis;

uses(RefreshDatabase::class, MocksExternalApis::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    // Create required permissions
    \Spatie\Permission\Models\Permission::create(['name' => 'generate tests', 'group_name' => 'ai-test-generation']);
    $this->user->givePermissionTo('generate tests');

    $this->actingAs($this->user);

    // Set up API mocks to prevent external API calls
    $this->setUpApiMocks();
});

test('github repository validation works correctly', function () {
    // Mock successful GitHub API response
    Http::fake([
        'api.github.com/repos/WordPress/hello-dolly' => Http::response([
            'id' => 123456,
            'name' => 'hello-dolly',
            'full_name' => 'WordPress/hello-dolly',
            'owner' => [
                'login' => 'WordPress',
                'type' => 'Organization'
            ],
            'default_branch' => 'trunk',
            'language' => 'PHP',
            'size' => 100,
            'stargazers_count' => 500,
            'forks_count' => 200,
            'open_issues_count' => 5,
            'html_url' => 'https://github.com/WordPress/hello-dolly',
            'clone_url' => 'https://github.com/WordPress/hello-dolly.git',
            'ssh_url' => 'git@github.com:WordPress/hello-dolly.git',
            'created_at' => '2020-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z',
            'pushed_at' => '2023-06-01T00:00:00Z'
        ], 200)
    ]);

    $response = $this->post('/thinktest/github/validate', [
        'repository_url' => 'https://github.com/WordPress/hello-dolly'
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'repository' => [
            'name' => 'hello-dolly',
            'full_name' => 'WordPress/hello-dolly',
            'default_branch' => 'trunk'
        ]
    ]);
});

test('github repository validation handles invalid URLs', function () {
    $response = $this->post('/thinktest/github/validate', [
        'repository_url' => 'invalid-url'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['repository_url']);
});

test('github repository validation handles non-existent repositories', function () {
    Http::fake([
        'api.github.com/repos/nonexistent/repo' => Http::response(['message' => 'Not Found'], 404)
    ]);

    $response = $this->post('/thinktest/github/validate', [
        'repository_url' => 'https://github.com/nonexistent/repo'
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => false,
        'error' => 'Repository not found or is private'
    ]);
});

test('github branch fetching works correctly', function () {
    Http::fake([
        'api.github.com/repos/WordPress/hello-dolly/branches*' => Http::response([
            [
                'name' => 'trunk',
                'commit' => [
                    'sha' => 'abc123',
                    'url' => 'https://api.github.com/repos/WordPress/hello-dolly/commits/abc123'
                ],
                'protected' => false
            ],
            [
                'name' => 'develop',
                'commit' => [
                    'sha' => 'def456',
                    'url' => 'https://api.github.com/repos/WordPress/hello-dolly/commits/def456'
                ],
                'protected' => false
            ]
        ], 200)
    ]);

    $response = $this->post('/thinktest/github/branches', [
        'owner' => 'WordPress',
        'repo' => 'hello-dolly'
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'branches' => [
            '*' => ['name', 'commit_sha', 'commit_url', 'protected']
        ]
    ]);

    $branches = $response->json('branches');
    expect($branches)->toHaveCount(2);
    expect($branches[0]['name'])->toBe('trunk');
    expect($branches[1]['name'])->toBe('develop');
});

test('github branch fetching handles single branch repositories', function () {
    Http::fake([
        'api.github.com/repos/WordPress/hello-dolly/branches*' => Http::response([
            [
                'name' => 'main',
                'commit' => [
                    'sha' => 'abc123',
                    'url' => 'https://api.github.com/repos/WordPress/hello-dolly/commits/abc123'
                ],
                'protected' => false
            ]
        ], 200)
    ]);

    $response = $this->post('/thinktest/github/branches', [
        'owner' => 'WordPress',
        'repo' => 'hello-dolly'
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'branches' => [
            '*' => ['name', 'commit_sha', 'commit_url', 'protected']
        ]
    ]);

    // Verify single branch is handled correctly
    $branches = $response->json('branches');
    expect($branches)->toHaveCount(1);
    expect($branches[0]['name'])->toBe('main');
});

test('github tree fetching works correctly', function () {
    Http::fake([
        'api.github.com/repos/WordPress/hello-dolly/git/trees/abc123*' => Http::response([
            'sha' => 'abc123',
            'url' => 'https://api.github.com/repos/WordPress/hello-dolly/git/trees/abc123',
            'tree' => [
                [
                    'path' => 'hello.php',
                    'mode' => '100644',
                    'type' => 'blob',
                    'sha' => 'file123',
                    'size' => 1024,
                    'url' => 'https://api.github.com/repos/WordPress/hello-dolly/git/blobs/file123'
                ],
                [
                    'path' => 'readme.txt',
                    'mode' => '100644',
                    'type' => 'blob',
                    'sha' => 'file456',
                    'size' => 512,
                    'url' => 'https://api.github.com/repos/WordPress/hello-dolly/git/blobs/file456'
                ],
                [
                    'path' => 'tests',
                    'mode' => '040000',
                    'type' => 'tree',
                    'sha' => 'tree789',
                    'url' => 'https://api.github.com/repos/WordPress/hello-dolly/git/trees/tree789'
                ]
            ],
            'truncated' => false
        ], 200)
    ]);

    $response = $this->post('/thinktest/github/tree', [
        'owner' => 'WordPress',
        'repo' => 'hello-dolly',
        'branch' => 'trunk',
        'path' => ''
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'tree' => [
            '*' => ['name', 'path', 'type', 'sha', 'size', 'url', 'html_url', 'download_url']
        ],
        'repository'
    ]);

    $tree = $response->json('tree');
    expect($tree)->toHaveCount(3); // 2 files + 1 directory

    $fileNames = collect($tree)->where('type', 'file')->pluck('name')->toArray();
    expect($fileNames)->toContain('hello.php');
    expect($fileNames)->toContain('readme.txt');
});

test('github file content fetching works correctly', function () {
    Http::fake([
        'api.github.com/repos/WordPress/hello-dolly/contents/hello.php*' => Http::response([
            'name' => 'hello.php',
            'path' => 'hello.php',
            'sha' => 'file123',
            'size' => 1024,
            'url' => 'https://api.github.com/repos/WordPress/hello-dolly/contents/hello.php',
            'html_url' => 'https://github.com/WordPress/hello-dolly/blob/trunk/hello.php',
            'git_url' => 'https://api.github.com/repos/WordPress/hello-dolly/git/blobs/file123',
            'download_url' => 'https://raw.githubusercontent.com/WordPress/hello-dolly/trunk/hello.php',
            'type' => 'file',
            'content' => base64_encode('<?php
/**
 * Plugin Name: Hello Dolly
 */
echo "Hello Dolly!";'),
            'encoding' => 'base64'
        ], 200)
    ]);

    $response = $this->post('/thinktest/github/file', [
        'owner' => 'WordPress',
        'repo' => 'hello-dolly',
        'branch' => 'trunk',
        'path' => 'hello.php'
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'file' => [
            'name', 'path', 'content', 'size', 'sha', 'encoding', 'url', 'html_url', 'download_url'
        ],
        'repository'
    ]);

    $file = $response->json('file');
    expect($file['name'])->toBe('hello.php');
    expect($file['path'])->toBe('hello.php');

    $content = $file['content'];
    expect($content)->toContain('Hello Dolly');
    expect($content)->toContain('Plugin Name');
});

test('github API handles rate limiting gracefully', function () {
    Http::fake([
        'api.github.com/repos/WordPress/hello-dolly/branches*' => Http::response([
            'message' => 'API rate limit exceeded',
            'documentation_url' => 'https://docs.github.com/rest/overview/resources-in-the-rest-api#rate-limiting'
        ], 403, [
            'X-RateLimit-Limit' => '60',
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Reset' => (string)(time() + 3600)
        ])
    ]);

    $response = $this->post('/thinktest/github/branches', [
        'owner' => 'WordPress',
        'repo' => 'hello-dolly'
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => false,
        'error' => 'GitHub API rate limit exceeded. Please try again later.'
    ]);
});

test('github API handles network errors gracefully', function () {
    Http::fake([
        'api.github.com/*' => Http::response(null, 500)
    ]);

    $response = $this->post('/thinktest/github/branches', [
        'owner' => 'WordPress',
        'repo' => 'hello-dolly'
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => false
    ]);
    
    expect($response->json('error'))->toContain('GitHub API error');
});

test('repository processing handles WordPress plugins correctly', function () {
    // Mock GitHub API responses for a typical WordPress plugin structure
    Http::fake([
        'api.github.com/repos/WordPress/hello-dolly' => Http::response([
            'name' => 'hello-dolly',
            'full_name' => 'WordPress/hello-dolly',
            'default_branch' => 'trunk'
        ], 200),
        'api.github.com/repos/WordPress/hello-dolly/git/trees/*' => Http::response([
            'tree' => [
                [
                    'path' => 'hello.php',
                    'type' => 'blob',
                    'size' => 2048
                ],
                [
                    'path' => 'readme.txt',
                    'type' => 'blob',
                    'size' => 1024
                ],
                [
                    'path' => 'composer.json',
                    'type' => 'blob',
                    'size' => 512
                ]
            ]
        ], 200)
    ]);

    $response = $this->post('/thinktest/github/process', [
        'repository' => [
            'name' => 'hello-dolly',
            'full_name' => 'WordPress/hello-dolly',
            'owner' => 'WordPress',
            'repo' => 'hello-dolly'
        ],
        'branch' => 'trunk',
        'ai_provider' => 'mock'
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true
    ]);
    
    // Should detect WordPress plugin structure
    expect($response->json('infrastructure.has_composer_json'))->toBeTrue();
});

test('caching mechanism works for repository data', function () {
    // First request should hit the API
    Http::fake([
        'api.github.com/repos/WordPress/hello-dolly/branches*' => Http::response([
            [
                'name' => 'trunk',
                'commit' => ['sha' => 'abc123'],
                'protected' => false
            ]
        ], 200)
    ]);

    $response1 = $this->post('/thinktest/github/branches', [
        'owner' => 'WordPress',
        'repo' => 'hello-dolly'
    ]);

    $response1->assertStatus(200);
    
    // Second request should use cache (no HTTP call)
    Http::fake(); // Clear all fakes - if cache works, no API call should be made
    
    $response2 = $this->post('/thinktest/github/branches', [
        'owner' => 'WordPress',
        'repo' => 'hello-dolly'
    ]);

    $response2->assertStatus(200);
    $response2->assertJson([
        'success' => true,
        'branches' => [
            ['name' => 'trunk']
        ]
    ]);
});
