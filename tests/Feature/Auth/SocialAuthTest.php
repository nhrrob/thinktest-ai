<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('google oauth redirect works', function () {
    $response = $this->get(route('auth.google'));
    
    $response->assertStatus(302);
    $response->assertRedirect();
});

test('user model oauth method works correctly', function () {
    // Ensure the 'user' role exists
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']);

    // Mock the Socialite user
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('123456789');
    $socialiteUser->shouldReceive('getName')->andReturn('John Doe');
    $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    // Test the User model method directly
    $user = User::createOrUpdateFromOAuth($socialiteUser);

    expect($user)->not->toBeNull();
    expect($user->name)->toBe('John Doe');
    expect($user->email)->toBe('john@example.com');
    expect($user->google_id)->toBe('123456789');
    expect($user->avatar)->toBe('https://example.com/avatar.jpg');
    expect($user->hasRole('user'))->toBeTrue();
});

test('google oauth callback updates existing user', function () {
    // Create existing user
    $existingUser = User::factory()->create([
        'email' => 'john@example.com',
        'name' => 'John Smith',
        'google_id' => null,
        'avatar' => null,
    ]);

    // Mock the Socialite user
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('123456789');
    $socialiteUser->shouldReceive('getName')->andReturn('John Doe');
    $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    // Mock Socialite
    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUser);

    // Make the callback request
    $response = $this->get(route('auth.google.callback'));

    // Assert user was updated
    $existingUser->refresh();
    expect($existingUser->google_id)->toBe('123456789');
    expect($existingUser->avatar)->toBe('https://example.com/avatar.jpg');

    // Assert user is logged in and redirected
    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($existingUser);
});

test('google oauth callback handles errors gracefully', function () {
    // Mock Socialite to throw an exception
    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturnSelf();
    Socialite::shouldReceive('user')->andThrow(new Exception('OAuth error'));

    // Make the callback request
    $response = $this->get(route('auth.google.callback'));

    // Assert redirected to login with error
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Unable to login with Google. Please try again.');
    $this->assertGuest();
});
