<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    /**
     * Show the branding settings form.
     */
    public function edit(Request $request): Response
    {
        $currentLogo = $this->getCurrentLogo();
        $availableLogos = $this->getAvailableLogos();

        return Inertia::render('settings/branding', [
            'currentLogo' => $currentLogo,
            'availableLogos' => $availableLogos,
        ]);
    }

    /**
     * Update the application branding settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'logo_type' => 'required|in:default,custom,uploaded',
            'logo_file' => [
                'nullable',
                'required_if:logo_type,uploaded',
                File::types(['png', 'jpg', 'jpeg', 'svg'])
                    ->max(2048), // 2MB max
            ],
            'custom_logo_id' => 'nullable|required_if:logo_type,custom|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $logoType = $request->input('logo_type');
        $logoPath = null;

        if ($logoType === 'uploaded' && $request->hasFile('logo_file')) {
            // Handle file upload
            $file = $request->file('logo_file');
            $logoPath = $file->store('logos', 'public');
        } elseif ($logoType === 'custom') {
            $logoPath = $request->input('custom_logo_id');
        }

        // Store the logo preference in session/cache for now
        // In a real application, you might want to store this in a settings table
        session(['app_logo_type' => $logoType]);
        if ($logoPath) {
            session(['app_logo_path' => $logoPath]);
        }

        return back()->with('success', 'Branding settings updated successfully.');
    }

    /**
     * Get the current logo configuration.
     */
    private function getCurrentLogo(): array
    {
        $logoType = session('app_logo_type', 'default');
        $logoPath = session('app_logo_path', null);

        return [
            'type' => $logoType,
            'path' => $logoPath,
            'url' => $logoPath && $logoType === 'uploaded' ? Storage::url($logoPath) : null,
        ];
    }

    /**
     * Get available predefined logos.
     */
    private function getAvailableLogos(): array
    {
        return [
            [
                'id' => 'thinktest_brain',
                'name' => 'ThinkTest AI (Brain)',
                'description' => 'Default ThinkTest AI logo with brain icon',
                'preview' => '/images/logos/thinktest-brain-preview.png',
            ],
            [
                'id' => 'thinktest_minimal',
                'name' => 'ThinkTest AI (Minimal)',
                'description' => 'Minimal text-only version',
                'preview' => '/images/logos/thinktest-minimal-preview.png',
            ],
            [
                'id' => 'thinktest_dark',
                'name' => 'ThinkTest AI (Dark)',
                'description' => 'Dark theme optimized version',
                'preview' => '/images/logos/thinktest-dark-preview.png',
            ],
        ];
    }
}
