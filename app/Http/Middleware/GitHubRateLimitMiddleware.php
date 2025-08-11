<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class GitHubRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        // Global rate limiting for GitHub operations
        $globalKey = "github_global_{$user->id}";
        $perMinuteKey = "github_minute_{$user->id}";

        $globalLimit = config('thinktest_ai.github.rate_limit_requests_per_hour', 100);
        $minuteLimit = config('thinktest_ai.github.rate_limit_requests_per_minute', 10);

        // Check per-minute limit
        if (RateLimiter::tooManyAttempts($perMinuteKey, $minuteLimit)) {
            $seconds = RateLimiter::availableIn($perMinuteKey);
            return response()->json([
                'success' => false,
                'message' => "Too many requests. Try again in {$seconds} seconds.",
                'retry_after' => $seconds,
            ], 429);
        }

        // Check hourly limit
        if (RateLimiter::tooManyAttempts($globalKey, $globalLimit)) {
            $seconds = RateLimiter::availableIn($globalKey);
            $minutes = ceil($seconds / 60);
            return response()->json([
                'success' => false,
                'message' => "Hourly rate limit exceeded. Try again in {$minutes} minutes.",
                'retry_after' => $seconds,
            ], 429);
        }

        // Increment counters
        RateLimiter::hit($perMinuteKey, 60);    // 1 minute
        RateLimiter::hit($globalKey, 3600);     // 1 hour

        return $next($request);
    }
}
