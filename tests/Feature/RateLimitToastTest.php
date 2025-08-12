<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitToastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiter cache before each test
        RateLimiter::clear('github_global_1');
        RateLimiter::clear('github_minute_1');
    }

    public function test_github_rate_limit_returns_proper_error_format()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Manually trigger the rate limit by hitting the rate limiter directly
        $perMinuteKey = "github_minute_{$user->id}";

        // Hit the rate limiter 31 times to exceed the 30 per minute limit
        for ($i = 0; $i < 31; $i++) {
            \Illuminate\Support\Facades\RateLimiter::hit($perMinuteKey, 60);
        }

        // Now make a request that should be rate limited
        $response = $this->postJson('/thinktest/github/tree', [
            'owner' => 'test-owner',
            'repo' => 'test-repo',
            'branch' => 'main',
            'recursive' => true,
        ]);

        // Verify the rate limit response format
        $response->assertStatus(429)
            ->assertJsonStructure([
                'success',
                'message',
                'retry_after'
            ]);

        $data = $response->json();

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Too many requests', $data['message']);
        $this->assertIsInt($data['retry_after']);
        $this->assertGreaterThan(0, $data['retry_after']);
    }

    public function test_github_file_endpoint_rate_limit_handling()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Manually trigger the rate limit by hitting the rate limiter directly
        $perMinuteKey = "github_minute_{$user->id}";

        // Hit the rate limiter 31 times to exceed the 30 per minute limit
        for ($i = 0; $i < 31; $i++) {
            \Illuminate\Support\Facades\RateLimiter::hit($perMinuteKey, 60);
        }

        // Now make a request that should be rate limited
        $response = $this->postJson('/thinktest/github/file', [
            'owner' => 'test-owner',
            'repo' => 'test-repo',
            'path' => 'test.php',
            'branch' => 'main',
        ]);

        // Verify the rate limit response format
        $response->assertStatus(429)
            ->assertJsonStructure([
                'success',
                'message',
                'retry_after'
            ]);

        $data = $response->json();

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Too many requests', $data['message']);
        $this->assertIsInt($data['retry_after']);
        $this->assertGreaterThan(0, $data['retry_after']);
    }

    public function test_rate_limit_message_format_consistency()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Hit the per-minute rate limit
        $perMinuteKey = "github_minute_{$user->id}";
        RateLimiter::hit($perMinuteKey, 60);
        
        // Make one more request to trigger the limit
        for ($i = 0; $i < 35; $i++) {
            RateLimiter::hit($perMinuteKey, 60);
        }

        $response = $this->postJson('/thinktest/github/tree', [
            'owner' => 'test-owner',
            'repo' => 'test-repo',
            'branch' => 'main',
            'recursive' => true,
        ]);

        $response->assertStatus(429);
        $data = $response->json();

        // Verify the message format includes seconds
        $this->assertMatchesRegularExpression(
            '/Too many requests\. Try again in \d+ seconds\./',
            $data['message']
        );
    }
}
