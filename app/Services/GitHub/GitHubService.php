<?php

namespace App\Services\GitHub;

use Github\Client;
use Github\Exception\RuntimeException as GitHubRuntimeException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    private Client $client;

    private array $config;

    public function __construct(?Client $client = null)
    {
        $this->config = config('thinktest_ai.github');

        // Use injected client or create new one
        if ($client) {
            $this->client = $client;
        } else {
            $this->client = new Client;

            // Authenticate if token is available
            if (! empty($this->config['api_token'])) {
                $this->client->authenticate($this->config['api_token'], null, Client::AUTH_ACCESS_TOKEN);

                Log::info('GitHub Service: Authenticated with API token', [
                    'token_prefix' => substr($this->config['api_token'], 0, 7).'...',
                ]);
            } else {
                Log::warning('GitHub Service: No API token configured');
            }
        }
    }

    /**
     * Validate GitHub repository URL and extract owner/repo
     */
    public function validateRepositoryUrl(string $url): array
    {
        // Clean and normalize URL
        $url = trim($url);

        // Remove .git suffix if present
        $url = preg_replace('/\.git$/', '', $url);

        // Support various GitHub URL formats
        $patterns = [
            '/^https:\/\/github\.com\/([^\/\?#]+)\/([^\/\?#]+)\/?(\?.*)?$/',
            '/^git@github\.com:([^\/]+)\/([^\/]+)\.git$/',
            '/^([a-zA-Z0-9\-_\.]+)\/([a-zA-Z0-9\-_\.]+)$/', // Simple owner/repo format
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $owner = $matches[1];
                $repo = $matches[2];

                // Validate owner and repo names
                if ($this->isValidGitHubName($owner) && $this->isValidGitHubName($repo)) {
                    return [
                        'owner' => $owner,
                        'repo' => $repo,
                        'full_name' => "{$owner}/{$repo}",
                        'url' => "https://github.com/{$owner}/{$repo}",
                    ];
                }
            }
        }

        throw new \InvalidArgumentException('Invalid GitHub repository URL format');
    }

    /**
     * Get repository information
     */
    public function getRepositoryInfo(string $owner, string $repo): array
    {
        $cacheKey = "github_repo_info_{$owner}_{$repo}";

        return Cache::remember($cacheKey, $this->config['cache_repository_info_minutes'] * 60, function () use ($owner, $repo) {
            try {
                $repoData = $this->client->api('repo')->show($owner, $repo);

                return [
                    'id' => $repoData['id'],
                    'name' => $repoData['name'],
                    'full_name' => $repoData['full_name'],
                    'description' => $repoData['description'] ?? '',
                    'private' => $repoData['private'],
                    'default_branch' => $repoData['default_branch'],
                    'size' => $repoData['size'] * 1024, // Convert KB to bytes
                    'language' => $repoData['language'],
                    'languages_url' => $repoData['languages_url'],
                    'updated_at' => $repoData['updated_at'],
                    'clone_url' => $repoData['clone_url'],
                    'ssh_url' => $repoData['ssh_url'],
                    'html_url' => $repoData['html_url'],
                ];
            } catch (GitHubRuntimeException $e) {
                $errorInfo = GitHubErrorHandler::handleException($e, [
                    'owner' => $owner,
                    'repo' => $repo,
                    'action' => 'fetch_repository_info',
                ]);

                throw new \RuntimeException($errorInfo['user_message']);
            }
        });
    }

    /**
     * Get repository branches
     */
    public function getRepositoryBranches(string $owner, string $repo): array
    {
        $cacheKey = "github_repo_branches_{$owner}_{$repo}";

        return Cache::remember($cacheKey, $this->config['cache_branches_minutes'] * 60, function () use ($owner, $repo) {
            try {
                $branches = $this->client->api('repo')->branches($owner, $repo);

                return array_map(function ($branch) {
                    return [
                        'name' => $branch['name'],
                        'commit_sha' => $branch['commit']['sha'],
                        'commit_url' => $branch['commit']['url'],
                        'protected' => $branch['protected'] ?? false,
                    ];
                }, $branches);
            } catch (GitHubRuntimeException $e) {
                Log::error('GitHub API error when fetching repository branches', [
                    'owner' => $owner,
                    'repo' => $repo,
                    'error' => $e->getMessage(),
                ]);

                throw new \RuntimeException('Failed to fetch repository branches: '.$e->getMessage());
            }
        });
    }

    /**
     * Get repository languages
     */
    public function getRepositoryLanguages(string $owner, string $repo): array
    {
        try {
            $languages = $this->client->api('repo')->languages($owner, $repo);

            return $languages;
        } catch (GitHubRuntimeException $e) {
            Log::warning('Failed to fetch repository languages', [
                'owner' => $owner,
                'repo' => $repo,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Download repository as tarball
     */
    public function downloadRepositoryTarball(string $owner, string $repo, ?string $branch = null): string
    {
        try {
            $repoInfo = $this->getRepositoryInfo($owner, $repo);

            // Check repository size
            if ($repoInfo['size'] > $this->config['max_repository_size']) {
                throw new \RuntimeException("Repository size ({$repoInfo['size']} bytes) exceeds maximum allowed size ({$this->config['max_repository_size']} bytes)");
            }

            $branch = $branch ?: $repoInfo['default_branch'];

            // Use GitHub API to get tarball
            $tarballUrl = "https://api.github.com/repos/{$owner}/{$repo}/tarball/{$branch}";

            $headers = [
                'User-Agent' => 'ThinkTest-AI/1.0',
                'Accept' => 'application/vnd.github.v3+json',
            ];

            if (! empty($this->config['api_token'])) {
                $headers['Authorization'] = 'token '.$this->config['api_token'];
                Log::info('GitHub API: Using authentication token for tarball download', [
                    'owner' => $owner,
                    'repo' => $repo,
                    'branch' => $branch,
                    'token_prefix' => substr($this->config['api_token'], 0, 7).'...',
                ]);
            } else {
                Log::warning('GitHub API: No authentication token provided for tarball download', [
                    'owner' => $owner,
                    'repo' => $repo,
                    'branch' => $branch,
                ]);
            }

            Log::info('GitHub API: Requesting repository tarball', [
                'url' => $tarballUrl,
                'headers' => array_keys($headers),
                'timeout' => $this->config['clone_timeout'],
            ]);

            $response = Http::withHeaders($headers)
                ->timeout($this->config['clone_timeout'])
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => true,
                        'referer' => true,
                        'track_redirects' => true,
                    ],
                ])
                ->get($tarballUrl);

            // Log detailed response information
            Log::info('GitHub API: Tarball download response received', [
                'status_code' => $response->status(),
                'headers' => $response->headers(),
                'content_type' => $response->header('Content-Type'),
                'content_length' => $response->header('Content-Length'),
                'redirect_history' => $response->transferStats?->getHandlerStats()['redirect_url'] ?? null,
            ]);

            if (! $response->successful()) {
                $responseBody = $response->body();
                $isJson = $this->isJsonResponse($responseBody);

                Log::error('GitHub API: Tarball download failed', [
                    'status_code' => $response->status(),
                    'response_headers' => $response->headers(),
                    'response_body_preview' => substr($responseBody, 0, 500),
                    'is_json_response' => $isJson,
                    'content_type' => $response->header('Content-Type'),
                ]);

                if ($response->status() === 302) {
                    $location = $response->header('Location');
                    Log::error('GitHub API: Received 302 redirect', [
                        'redirect_location' => $location,
                        'original_url' => $tarballUrl,
                    ]);

                    if ($location) {
                        throw new \RuntimeException("Repository tarball download redirected to: {$location}. This may indicate authentication issues or repository access problems.");
                    }
                }

                throw new \RuntimeException("Failed to download repository tarball: HTTP {$response->status()}. Response: ".substr($responseBody, 0, 200));
            }

            // Validate response content
            $responseBody = $response->body();
            if (empty($responseBody)) {
                throw new \RuntimeException('Repository tarball download returned empty content');
            }

            // Check if we received HTML instead of tarball
            if ($this->isHtmlResponse($responseBody)) {
                Log::error('GitHub API: Received HTML response instead of tarball', [
                    'response_preview' => substr($responseBody, 0, 500),
                    'content_type' => $response->header('Content-Type'),
                ]);
                throw new \RuntimeException('Received HTML page instead of repository tarball. This may indicate authentication or access issues.');
            }

            // Save to temporary file
            $tempPath = storage_path('app/temp/github_downloads');
            if (! is_dir($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            $filename = "{$owner}_{$repo}_{$branch}_".time().'.tar.gz';
            $filePath = $tempPath.'/'.$filename;

            file_put_contents($filePath, $responseBody);

            Log::info('Repository tarball downloaded successfully', [
                'owner' => $owner,
                'repo' => $repo,
                'branch' => $branch,
                'file_path' => $filePath,
                'file_size' => filesize($filePath),
                'content_type' => $response->header('Content-Type'),
            ]);

            return $filePath;

        } catch (GitHubRuntimeException $e) {
            Log::error('GitHub API error when downloading repository', [
                'owner' => $owner,
                'repo' => $repo,
                'branch' => $branch,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            throw new \RuntimeException('Failed to download repository: '.$e->getMessage());
        } catch (\Exception $e) {
            Log::error('Unexpected error when downloading repository tarball', [
                'owner' => $owner,
                'repo' => $repo,
                'branch' => $branch,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Failed to download repository: '.$e->getMessage());
        }
    }

    /**
     * Check if repository is accessible
     */
    public function isRepositoryAccessible(string $owner, string $repo): bool
    {
        try {
            $this->getRepositoryInfo($owner, $repo);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Validate GitHub username/repository name
     */
    private function isValidGitHubName(string $name): bool
    {
        // GitHub username/repo name rules:
        // - Can contain alphanumeric characters and hyphens
        // - Cannot start or end with hyphen
        // - Cannot contain consecutive hyphens
        // - Maximum 39 characters
        return preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,37}[a-zA-Z0-9])?$/', $name);
    }

    /**
     * Get rate limit information
     */
    public function getRateLimitInfo(): array
    {
        try {
            $rateLimit = $this->client->api('rate_limit')->getRateLimits();

            return $rateLimit['rate'];
        } catch (GitHubRuntimeException $e) {
            Log::warning('Failed to fetch rate limit info', [
                'error' => $e->getMessage(),
            ]);

            return [
                'limit' => 60, // Default for unauthenticated requests
                'remaining' => 60,
                'reset' => time() + 3600,
            ];
        }
    }

    /**
     * Clear repository cache
     */
    public function clearRepositoryCache(string $owner, string $repo): void
    {
        $keys = [
            "github_repo_info_{$owner}_{$repo}",
            "github_repo_branches_{$owner}_{$repo}",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Check if response content is JSON
     */
    private function isJsonResponse(string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        json_decode($content);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check if response content is HTML
     */
    private function isHtmlResponse(string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        // Check for common HTML indicators
        $htmlIndicators = [
            '<!DOCTYPE',
            '<html',
            '<HTML',
            '<head>',
            '<body>',
            '<title>',
        ];

        $contentStart = substr($content, 0, 200);
        foreach ($htmlIndicators as $indicator) {
            if (stripos($contentStart, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify GitHub API token validity
     */
    public function verifyApiToken(): array
    {
        try {
            if (empty($this->config['api_token'])) {
                return [
                    'valid' => false,
                    'error' => 'No API token configured',
                ];
            }

            Log::info('GitHub API: Verifying API token', [
                'token_prefix' => substr($this->config['api_token'], 0, 7).'...',
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'token '.$this->config['api_token'],
                'User-Agent' => 'ThinkTest-AI/1.0',
                'Accept' => 'application/vnd.github.v3+json',
            ])->get('https://api.github.com/user');

            Log::info('GitHub API: Token verification response', [
                'status_code' => $response->status(),
                'headers' => $response->headers(),
            ]);

            if ($response->successful()) {
                $userData = $response->json();

                return [
                    'valid' => true,
                    'user' => $userData['login'] ?? 'unknown',
                    'scopes' => $response->header('X-OAuth-Scopes'),
                    'rate_limit_remaining' => $response->header('X-RateLimit-Remaining'),
                ];
            } else {
                Log::error('GitHub API: Token verification failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return [
                    'valid' => false,
                    'error' => "HTTP {$response->status()}: ".$response->body(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('GitHub API: Token verification exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
