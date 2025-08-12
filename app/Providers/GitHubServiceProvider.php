<?php

namespace App\Providers;

use App\Services\GitHub\GitHubRepositoryService;
use App\Services\GitHub\GitHubService;
use App\Services\GitHub\GitHubValidationService;
use Github\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class GitHubServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register GitHub Client
        $this->app->singleton(Client::class, function ($app) {
            try {
                $client = new Client;

                $config = config('thinktest_ai.github');
                if (! empty($config['api_token'])) {
                    $client->authenticate($config['api_token'], null, Client::AUTH_ACCESS_TOKEN);
                    // Remove verbose authentication log
                } else {
                    Log::warning('GitHub Client: No API token configured, using unauthenticated access');
                }

                return $client;
            } catch (\Exception $e) {
                Log::error('GitHub Client: Failed to create client instance', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        });

        // Register GitHub Service
        $this->app->singleton(GitHubService::class, function ($app) {
            return new GitHubService;
        });

        // Register GitHub Repository Service
        $this->app->singleton(GitHubRepositoryService::class, function ($app) {
            return new GitHubRepositoryService($app->make(GitHubService::class));
        });

        // Register GitHub Validation Service
        $this->app->singleton(GitHubValidationService::class, function ($app) {
            return new GitHubValidationService;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Verify GitHub integration is properly configured
        if (config('thinktest_ai.github.enabled', false)) {
            $this->verifyGitHubConfiguration();
        }
    }

    /**
     * Verify GitHub configuration
     */
    private function verifyGitHubConfiguration(): void
    {
        try {
            $config = config('thinktest_ai.github');

            $issues = [];

            if (empty($config['api_token'])) {
                $issues[] = 'GitHub API token not configured';
            }

            if (empty($config['max_repository_size'])) {
                $issues[] = 'Maximum repository size not configured';
            }

            if (empty($config['clone_timeout'])) {
                $issues[] = 'Clone timeout not configured';
            }

            if (! empty($issues)) {
                Log::warning('GitHub Integration: Configuration issues detected', [
                    'issues' => $issues,
                ]);
            }
            // Remove success log to reduce noise
        } catch (\Exception $e) {
            Log::error('GitHub Integration: Configuration verification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
