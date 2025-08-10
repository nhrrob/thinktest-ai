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
