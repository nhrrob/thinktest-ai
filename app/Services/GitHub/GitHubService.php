<?php

namespace App\Services\GitHub;

use Github\Client;
use Github\Exception\RuntimeException as GitHubRuntimeException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GitHubService
{
    private Client $client;
    private array $config;

    public function __construct()
    {
        $this->config = config('thinktest_ai.github');
        $this->client = new Client();
        
        // Authenticate if token is available
        if (!empty($this->config['api_token'])) {
            $this->client->authenticate($this->config['api_token'], null, Client::AUTH_ACCESS_TOKEN);
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
                
                throw new \RuntimeException("Failed to fetch repository branches: " . $e->getMessage());
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
            
            $headers = [];
            if (!empty($this->config['api_token'])) {
                $headers['Authorization'] = 'token ' . $this->config['api_token'];
            }
            
            $response = Http::withHeaders($headers)
                ->timeout($this->config['clone_timeout'])
                ->get($tarballUrl);
            
            if (!$response->successful()) {
                throw new \RuntimeException("Failed to download repository tarball: HTTP {$response->status()}");
            }
            
            // Save to temporary file
            $tempPath = storage_path('app/temp/github_downloads');
            if (!is_dir($tempPath)) {
                mkdir($tempPath, 0755, true);
            }
            
            $filename = "{$owner}_{$repo}_{$branch}_" . time() . '.tar.gz';
            $filePath = $tempPath . '/' . $filename;
            
            file_put_contents($filePath, $response->body());
            
            Log::info('Repository tarball downloaded successfully', [
                'owner' => $owner,
                'repo' => $repo,
                'branch' => $branch,
                'file_path' => $filePath,
                'file_size' => filesize($filePath),
            ]);
            
            return $filePath;
            
        } catch (GitHubRuntimeException $e) {
            Log::error('GitHub API error when downloading repository', [
                'owner' => $owner,
                'repo' => $repo,
                'branch' => $branch,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException("Failed to download repository: " . $e->getMessage());
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
}
