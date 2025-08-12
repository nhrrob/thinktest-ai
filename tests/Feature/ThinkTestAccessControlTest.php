<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('demo user can access thinktest page', function () {
    $demoUser = User::where('email', 'demo@example.com')->first();
    
    $response = $this->actingAs($demoUser)->get('/thinktest');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('ThinkTest/Index'));
});

test('regular user can access thinktest page', function () {
    $regularUser = User::where('email', 'user@example.com')->first();
    
    $response = $this->actingAs($regularUser)->get('/thinktest');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('ThinkTest/Index'));
});

test('admin user can access thinktest page', function () {
    $adminUser = User::where('email', 'admin@example.com')->first();
    
    $response = $this->actingAs($adminUser)->get('/thinktest');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('ThinkTest/Index'));
});

test('super admin user can access thinktest page', function () {
    $superAdminUser = User::where('email', 'superadmin@example.com')->first();
    
    $response = $this->actingAs($superAdminUser)->get('/thinktest');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('ThinkTest/Index'));
});

test('unauthenticated user cannot access thinktest page', function () {
    $response = $this->get('/thinktest');
    
    $response->assertRedirect('/login');
});

test('demo user has correct permissions', function () {
    $demoUser = User::where('email', 'demo@example.com')->first();
    
    expect($demoUser->can('generate tests'))->toBeTrue();
    expect($demoUser->can('upload files'))->toBeTrue();
    expect($demoUser->can('download test results'))->toBeTrue();
    expect($demoUser->can('connect github'))->toBeTrue();
    expect($demoUser->can('manage repositories'))->toBeTrue();
});

test('regular user has correct permissions', function () {
    $regularUser = User::where('email', 'user@example.com')->first();
    
    expect($regularUser->can('generate tests'))->toBeTrue();
    expect($regularUser->can('upload files'))->toBeTrue();
    expect($regularUser->can('download test results'))->toBeTrue();
    expect($regularUser->can('connect github'))->toBeTrue();
    expect($regularUser->can('manage repositories'))->toBeTrue();
});

test('newly registered user gets user role automatically', function () {
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];
    
    $response = $this->post('/register', $userData);
    
    $response->assertRedirect('/dashboard');
    
    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('user'))->toBeTrue();
    expect($user->can('generate tests'))->toBeTrue();
});
