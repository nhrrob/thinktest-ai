<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    // Create required permissions
    \Spatie\Permission\Models\Permission::create(['name' => 'generate tests', 'group_name' => 'ai-test-generation']);

    // Give user required permissions
    $this->user->givePermissionTo('generate tests');

    $this->actingAs($this->user);
});

test('csrf utility handles token refresh correctly', function () {
    // First, make a request to establish session
    $response = $this->actingAs($this->user)->get('/thinktest');
    $response->assertStatus(200);

    // Extract CSRF token from the response
    $content = $response->getContent();
    $this->assertStringContainsString('csrf-token', $content);

    // Test the auth/check endpoint that the utility uses
    $response = $this->actingAs($this->user)->get('/auth/check');
    $response->assertStatus(200);
    $response->assertJson([
        'authenticated' => true,
        'user' => [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
        ],
    ]);
    $response->assertJsonStructure([
        'authenticated',
        'user' => ['id', 'name', 'email'],
        'csrf_token',
    ]);

    // Verify the CSRF token is present and valid
    $data = $response->json();
    $this->assertNotEmpty($data['csrf_token']);
    $this->assertTrue($data['authenticated']);
});

test('github validate endpoint works with proper csrf handling', function () {
    // Clear rate limiter cache to avoid interference
    \Illuminate\Support\Facades\RateLimiter::clear("github_global_{$this->user->id}");
    \Illuminate\Support\Facades\RateLimiter::clear("github_minute_{$this->user->id}");

    // Make a request to the GitHub validate endpoint
    $response = $this->actingAs($this->user)
        ->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->postJson('/thinktest/github/validate', [
            'repository_url' => 'https://github.com/octocat/Hello-World',
        ]);

    // Should not get CSRF or authentication errors
    $this->assertNotEquals(419, $response->getStatusCode()); // CSRF error
    $this->assertNotEquals(401, $response->getStatusCode()); // Auth error
    $this->assertNotEquals(302, $response->getStatusCode()); // Redirect to login

    // Should get a valid response (either success or validation error)
    $this->assertContains($response->getStatusCode(), [200, 422, 404, 429]);
});

test('csrf token is properly included in meta tag', function () {
    $response = $this->actingAs($this->user)->get('/thinktest');
    
    $response->assertStatus(200);
    
    // Check that the CSRF token meta tag is present
    $content = $response->getContent();
    $this->assertStringContainsString('<meta name="csrf-token"', $content);
    $this->assertStringContainsString('content="', $content);
    
    // Extract the token from the meta tag
    preg_match('/<meta name="csrf-token" content="([^"]+)"/', $content, $matches);
    $this->assertNotEmpty($matches[1]);
    
    // Verify the token is valid by comparing with Laravel's csrf_token()
    $this->assertEquals(csrf_token(), $matches[1]);
});
