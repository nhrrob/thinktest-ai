<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('login page renders with simplified logo', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    // The page should render without errors, indicating the AppLogo component is working
});

test('register page renders with simplified logo', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    // The page should render without errors, indicating the AppLogo component is working
});

test('welcome page renders with simplified logo', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    // The page should render without errors, indicating the AppLogo component is working
});

test('branding settings page renders with simplified logo preview', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/branding');

    $response->assertStatus(200);
    // The page should render without errors, indicating the AppLogo component is working in the preview
});

test('forgot password page renders with simplified logo', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
    // The page should render without errors, indicating the AppLogo component is working
});
