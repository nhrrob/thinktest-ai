<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    /**
     * Show the API token management page.
     */
    public function index(): Response
    {
        $user = Auth::user();

        $tokens = $user->apiTokens()
            ->orderBy('provider')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'provider' => $token->provider,
                    'provider_display_name' => $token->provider_display_name,
                    'display_name' => $token->display_name,
                    'masked_token' => $token->masked_token,
                    'is_active' => $token->is_active,
                    'last_used_at' => $token->last_used_at?->diffForHumans(),
                    'created_at' => $token->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('settings/api-tokens', [
            'tokens' => $tokens,
            'availableProviders' => $this->getAvailableProviders(),
            'instructions' => $this->getProviderInstructions(),
        ]);
    }

    /**
     * Store a new API token.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'provider' => ['required', 'string', Rule::in(['openai', 'anthropic'])],
            'token' => 'required|string|min:10',
            'display_name' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        // Check if user already has a token for this provider
        $existingToken = $user->apiTokens()->where('provider', $request->provider)->first();

        if ($existingToken) {
            return back()->withErrors([
                'provider' => 'You already have an API token for this provider. Please update or delete the existing token first.',
            ]);
        }

        // Validate the token format based on provider
        $validationResult = $this->validateTokenFormat($request->provider, $request->token);
        if (!$validationResult['valid']) {
            return back()->withErrors([
                'token' => $validationResult['message'],
            ]);
        }

        UserApiToken::create([
            'user_id' => $user->id,
            'provider' => $request->provider,
            'token' => $request->token,
            'display_name' => $request->display_name ?: $this->getDefaultDisplayName($request->provider),
            'is_active' => true,
        ]);

        return back()->with('success', 'API token added successfully.');
    }

    /**
     * Update an existing API token.
     */
    public function update(Request $request, UserApiToken $token): RedirectResponse
    {
        // Ensure the token belongs to the authenticated user
        if ($token->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'token' => 'required|string|min:10',
            'display_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        // Validate the token format based on provider
        $validationResult = $this->validateTokenFormat($token->provider, $request->token);
        if (!$validationResult['valid']) {
            return back()->withErrors([
                'token' => $validationResult['message'],
            ]);
        }

        $token->update([
            'token' => $request->token,
            'display_name' => $request->display_name ?: $this->getDefaultDisplayName($token->provider),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'API token updated successfully.');
    }

    /**
     * Delete an API token.
     */
    public function destroy(UserApiToken $token): RedirectResponse
    {
        // Ensure the token belongs to the authenticated user
        if ($token->user_id !== Auth::id()) {
            abort(403);
        }

        $token->delete();

        return back()->with('success', 'API token deleted successfully.');
    }

    /**
     * Toggle the active status of an API token.
     */
    public function toggle(UserApiToken $token): RedirectResponse
    {
        // Ensure the token belongs to the authenticated user
        if ($token->user_id !== Auth::id()) {
            abort(403);
        }

        $token->update(['is_active' => !$token->is_active]);

        $status = $token->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "API token {$status} successfully.");
    }

    /**
     * Get available AI providers.
     */
    private function getAvailableProviders(): array
    {
        return [
            'openai' => [
                'name' => 'OpenAI',
                'description' => 'Access to GPT models including GPT-4 and GPT-5',
                'website' => 'https://platform.openai.com',
            ],
            'anthropic' => [
                'name' => 'Anthropic',
                'description' => 'Access to Claude models including Claude 3.5 Sonnet',
                'website' => 'https://console.anthropic.com',
            ],
        ];
    }

    /**
     * Get step-by-step instructions for obtaining API keys.
     */
    private function getProviderInstructions(): array
    {
        return [
            'openai' => [
                'title' => 'How to get your OpenAI API Key',
                'steps' => [
                    'Go to <a href="https://platform.openai.com" target="_blank" class="text-blue-600 hover:text-blue-800">platform.openai.com</a>',
                    'Sign in to your OpenAI account or create a new one',
                    'Navigate to the "API Keys" section in your dashboard',
                    'Click "Create new secret key"',
                    'Give your key a descriptive name (e.g., "ThinkTest AI")',
                    'Copy the generated API key (it starts with "sk-")',
                    'Paste the key in the form below',
                ],
                'notes' => [
                    'Keep your API key secure and never share it publicly',
                    'You can set usage limits in your OpenAI dashboard',
                    'API usage will be billed to your OpenAI account',
                ],
            ],
            'anthropic' => [
                'title' => 'How to get your Anthropic API Key',
                'steps' => [
                    'Go to <a href="https://console.anthropic.com" target="_blank" class="text-blue-600 hover:text-blue-800">console.anthropic.com</a>',
                    'Sign in to your Anthropic account or create a new one',
                    'Navigate to the "API Keys" section',
                    'Click "Create Key"',
                    'Give your key a descriptive name (e.g., "ThinkTest AI")',
                    'Copy the generated API key (it starts with "sk-ant-")',
                    'Paste the key in the form below',
                ],
                'notes' => [
                    'Keep your API key secure and never share it publicly',
                    'You can monitor usage in your Anthropic console',
                    'API usage will be billed to your Anthropic account',
                ],
            ],
        ];
    }

    /**
     * Validate token format based on provider.
     */
    private function validateTokenFormat(string $provider, string $token): array
    {
        return match ($provider) {
            'openai' => [
                'valid' => str_starts_with($token, 'sk-') && strlen($token) >= 20,
                'message' => 'OpenAI API keys should start with "sk-" and be at least 20 characters long.',
            ],
            'anthropic' => [
                'valid' => str_starts_with($token, 'sk-ant-') && strlen($token) >= 30,
                'message' => 'Anthropic API keys should start with "sk-ant-" and be at least 30 characters long.',
            ],
            default => [
                'valid' => false,
                'message' => 'Unsupported provider.',
            ],
        };
    }

    /**
     * Get default display name for a provider.
     */
    private function getDefaultDisplayName(string $provider): string
    {
        return match ($provider) {
            'openai' => 'OpenAI API Key',
            'anthropic' => 'Anthropic API Key',
            default => ucfirst($provider) . ' API Key',
        };
    }
}
