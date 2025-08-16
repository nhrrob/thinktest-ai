<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('github oauth redirect works', function () {
    $response = $this->get(route('auth.github'));

    $response->assertStatus(302);
    $response->assertRedirect();
});

test('github oauth callback creates new user', function () {
    // Mock the Socialite user
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('123456789');
    $socialiteUser->shouldReceive('getName')->andReturn('John Doe');
    $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    // Mock Socialite
    Socialite::shouldReceive('driver')
        ->with('github')
        ->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUser);

    $response = $this->get(route('auth.github.callback'));

    $response->assertRedirect('/thinktest');

    // Check if user was created
    $user = User::where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->github_id)->toBe('123456789');
    expect($user->name)->toBe('John Doe');
    expect($user->avatar)->toBe('https://example.com/avatar.jpg');
    expect($user->hasRole('user'))->toBeTrue();
    expect($user->can('generate tests'))->toBeTrue();
});

test('github oauth callback updates existing user', function () {
    // Create existing user
    $existingUser = User::factory()->create([
        'email' => 'john@example.com',
        'name' => 'John Smith',
        'github_id' => null,
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
        ->with('github')
        ->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUser);

    $response = $this->get(route('auth.github.callback'));

    $response->assertRedirect('/thinktest');

    // Check if user was updated
    $existingUser->refresh();
    expect($existingUser->github_id)->toBe('123456789');
    expect($existingUser->avatar)->toBe('https://example.com/avatar.jpg');
    // Name should not be updated for existing users
    expect($existingUser->name)->toBe('John Smith');
});

test('github oauth callback handles errors gracefully', function () {
    // Mock Socialite to throw an exception
    Socialite::shouldReceive('driver')
        ->with('github')
        ->andThrow(new Exception('OAuth error'));

    $response = $this->get(route('auth.github.callback'));

    $response->assertRedirect('/login');
    $response->assertSessionHas('error', 'Unable to login with GitHub. Please try again.');
});

test('user model createOrUpdateFromOAuth handles github provider', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('123456789');
    $socialiteUser->shouldReceive('getName')->andReturn('John Doe');
    $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    $user = User::createOrUpdateFromOAuth($socialiteUser, 'github');

    expect($user->github_id)->toBe('123456789');
    expect($user->google_id)->toBeNull();
    expect($user->name)->toBe('John Doe');
    expect($user->email)->toBe('john@example.com');
    expect($user->avatar)->toBe('https://example.com/avatar.jpg');
    expect($user->hasRole('user'))->toBeTrue();
});

test('user model createOrUpdateFromOAuth handles google provider', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('987654321');
    $socialiteUser->shouldReceive('getName')->andReturn('Jane Doe');
    $socialiteUser->shouldReceive('getEmail')->andReturn('jane@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar2.jpg');

    $user = User::createOrUpdateFromOAuth($socialiteUser, 'google');

    expect($user->google_id)->toBe('987654321');
    expect($user->github_id)->toBeNull();
    expect($user->name)->toBe('Jane Doe');
    expect($user->email)->toBe('jane@example.com');
    expect($user->avatar)->toBe('https://example.com/avatar2.jpg');
    expect($user->hasRole('user'))->toBeTrue();
});
