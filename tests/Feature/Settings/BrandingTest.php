<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('branding settings screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/branding');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('settings/branding')
        ->has('currentLogo')
        ->has('availableLogos')
    );
});

test('branding settings can be updated with default logo', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/branding', [
        'logo_type' => 'default',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Branding settings updated successfully.');
    
    // Check that the session has the correct logo type
    expect(session('app_logo_type'))->toBe('default');
});

test('branding settings validation works', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/branding', [
        'logo_type' => 'invalid_type',
    ]);

    $response->assertSessionHasErrors(['logo_type']);
});

test('file upload validation works for branding settings', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/branding', [
        'logo_type' => 'uploaded',
        // No file provided
    ]);

    $response->assertSessionHasErrors(['logo_file']);
});

test('branding settings can be updated with custom code logo', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/branding', [
        'logo_type' => 'custom',
        'custom_logo_id' => 'thinktest_code',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Branding settings updated successfully.');

    // Check that the session has the correct logo type and path
    expect(session('app_logo_type'))->toBe('custom');
    expect(session('app_logo_path'))->toBe('thinktest_code');
});

test('code logo can be set via dedicated route', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/branding/set-code-logo');

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Logo updated to code-themed version successfully.');

    // Check that the session has the correct logo type and path
    expect(session('app_logo_type'))->toBe('custom');
    expect(session('app_logo_path'))->toBe('thinktest_code');
});

test('branding settings defaults to code logo when no preference exists', function () {
    $user = User::factory()->create();

    // Clear any existing session data
    session()->forget(['app_logo_type', 'app_logo_path']);

    $response = $this->actingAs($user)->get('/settings/branding');

    $response->assertStatus(200);

    // Check that the session now has the code logo as default
    expect(session('app_logo_type'))->toBe('custom');
    expect(session('app_logo_path'))->toBe('thinktest_code');
});
