<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google OAuth provider.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Create or update user from OAuth data
            $user = User::createOrUpdateFromOAuth($googleUser, 'google');

            // Log the user in
            Auth::login($user);

            return redirect()->intended(route('thinktest.index', absolute: false));

        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Unable to login with Google. Please try again.');
        }
    }

    /**
     * Redirect to GitHub OAuth provider.
     */
    public function redirectToGitHub(): RedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * Handle GitHub OAuth callback.
     */
    public function handleGitHubCallback(): RedirectResponse
    {
        try {
            $githubUser = Socialite::driver('github')->user();

            // Create or update user from OAuth data
            $user = User::createOrUpdateFromOAuth($githubUser, 'github');

            // Log the user in
            Auth::login($user);

            return redirect()->intended(route('thinktest.index', absolute: false));

        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Unable to login with GitHub. Please try again.');
        }
    }
}
