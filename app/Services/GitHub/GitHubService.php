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
     * Get repository contents (files and directories) at a specific path
     */
    public function getRepositoryContents(string $owner, string $repo, string $path = '', ?string $branch = null): array
    {
        $cacheKey = "github_repo_contents_{$owner}_{$repo}_".md5($path)."_{$branch}";

        return Cache::remember($cacheKey, $this->config['cache_repository_info_minutes'] * 60, function () use ($owner, $repo, $path, $branch) {
            try {
                $repoInfo = $this->getRepositoryInfo($owner, $repo);
                $branch = $branch ?: $repoInfo['default_branch'];

                $contents = $this->client->api('repo')->contents()->show($owner, $repo, $path, $branch);

                // If it's a single file, wrap it in an array
                if (isset($contents['type']) && $contents['type'] === 'file') {
                    $contents = [$contents];
                }

                return array_map(function ($item) {
                    return [
                        'name' => $item['name'],
                        'path' => $item['path'],
                        'type' => $item['type'], // 'file' or 'dir'
                        'size' => $item['size'] ?? 0,
                        'sha' => $item['sha'],
                        'url' => $item['url'],
                        'html_url' => $item['html_url'],
                        'download_url' => $item['download_url'] ?? null,
                    ];
                }, $contents);
            } catch (GitHubRuntimeException $e) {
                $errorInfo = GitHubErrorHandler::handleException($e, [
                    'owner' => $owner,
                    'repo' => $repo,
                    'path' => $path,
                    'branch' => $branch,
                    'action' => 'fetch_repository_contents',
                ]);

                throw new \RuntimeException($errorInfo['user_message']);
            }
        });
    }

    /**
     * Get file content from repository
     */
    public function getFileContent(string $owner, string $repo, string $path, ?string $branch = null): array
    {
        $cacheKey = "github_file_content_{$owner}_{$repo}_".md5($path)."_{$branch}";

        return Cache::remember($cacheKey, $this->config['cache_repository_info_minutes'] * 60, function () use ($owner, $repo, $path, $branch) {
            try {
                $repoInfo = $this->getRepositoryInfo($owner, $repo);
                $branch = $branch ?: $repoInfo['default_branch'];

                $fileData = $this->client->api('repo')->contents()->show($owner, $repo, $path, $branch);

                // Ensure it's a file
                if ($fileData['type'] !== 'file') {
                    throw new \RuntimeException("Path '{$path}' is not a file");
                }

                // Decode content if it's base64 encoded
                $content = $fileData['content'];
                if ($fileData['encoding'] === 'base64') {
                    $content = base64_decode($content);
                }

                return [
                    'name' => $fileData['name'],
                    'path' => $fileData['path'],
                    'content' => $content,
                    'size' => $fileData['size'],
                    'sha' => $fileData['sha'],
                    'encoding' => $fileData['encoding'],
                    'url' => $fileData['url'],
                    'html_url' => $fileData['html_url'],
                    'download_url' => $fileData['download_url'],
                ];
            } catch (GitHubRuntimeException $e) {
                $errorInfo = GitHubErrorHandler::handleException($e, [
                    'owner' => $owner,
                    'repo' => $repo,
                    'path' => $path,
                    'branch' => $branch,
                    'action' => 'fetch_file_content',
                ]);

                throw new \RuntimeException($errorInfo['user_message']);
            }
        });
    }

    /**
     * Get repository file tree (recursive directory structure)
     */
    public function getRepositoryTree(string $owner, string $repo, ?string $branch = null, bool $recursive = false): array
    {
        $cacheKey = "github_repo_tree_{$owner}_{$repo}_{$branch}_".($recursive ? 'recursive' : 'flat');

        return Cache::remember($cacheKey, $this->config['cache_repository_info_minutes'] * 60, function () use ($owner, $repo, $branch, $recursive) {
            try {
                $repoInfo = $this->getRepositoryInfo($owner, $repo);
                $branch = $branch ?: $repoInfo['default_branch'];

                // Get the latest commit SHA for the branch
                $branches = $this->client->api('repo')->branches($owner, $repo);
                $branchData = collect($branches)->firstWhere('name', $branch);

                if (!$branchData) {
                    throw new \RuntimeException("Branch '{$branch}' not found");
                }

                $commitSha = $branchData['commit']['sha'];

                // Get the tree using git data API
                $tree = $this->client->api('gitData')->trees()->show($owner, $repo, $commitSha, $recursive);

                // Filter and format the tree
                $supportedExtensions = $this->config['supported_file_extensions'];
                $ignoredDirectories = $this->config['ignored_directories'];

                Log::info('GitHub tree filtering started', [
                    'repository' => "{$owner}/{$repo}",
                    'branch' => $branch,
                    'total_items' => count($tree['tree']),
                    'supported_extensions' => $supportedExtensions,
                    'ignored_directories' => $ignoredDirectories,
                ]);

                $filteredTree = array_filter($tree['tree'], function ($item) use ($supportedExtensions, $ignoredDirectories) {
                    // Skip ignored directories
                    foreach ($ignoredDirectories as $ignoredDir) {
                        if (str_starts_with($item['path'], $ignoredDir.'/') || $item['path'] === $ignoredDir) {
                            return false;
                        }
                    }

                    // For files, check if extension is supported
                    if ($item['type'] === 'blob') {
                        $pathExtension = pathinfo($item['path'], PATHINFO_EXTENSION);
                        if (empty($pathExtension)) {
                            return false; // Files without extensions are not supported
                        }
                        $extension = '.' . $pathExtension;
                        return in_array($extension, $supportedExtensions);
                    }

                    // Include directories
                    return $item['type'] === 'tree';
                });

                $finalTree = array_map(function ($item) use ($owner, $repo, $branch) {
                    $name = basename($item['path']);
                    return [
                        'name' => $name,
                        'path' => $item['path'],
                        'type' => $item['type'] === 'blob' ? 'file' : 'dir',
                        'sha' => $item['sha'],
                        'size' => $item['size'] ?? 0,
                        'url' => $item['url'],
                        'html_url' => "https://github.com/{$owner}/{$repo}/blob/{$branch}/" . $item['path'],
                        'download_url' => $item['type'] === 'blob' ? "https://raw.githubusercontent.com/{$owner}/{$repo}/{$branch}/" . $item['path'] : null,
                    ];
                }, array_values($filteredTree));

                Log::info('GitHub tree filtering completed', [
                    'repository' => "{$owner}/{$repo}",
                    'branch' => $branch,
                    'filtered_items' => count($finalTree),
                    'files_count' => count(array_filter($finalTree, fn($item) => $item['type'] === 'file')),
                    'directories_count' => count(array_filter($finalTree, fn($item) => $item['type'] === 'dir')),
                ]);

                return $finalTree;
            } catch (GitHubRuntimeException $e) {
                $errorInfo = GitHubErrorHandler::handleException($e, [
                    'owner' => $owner,
                    'repo' => $repo,
                    'branch' => $branch,
                    'recursive' => $recursive,
                    'action' => 'fetch_repository_tree',
                ]);

                throw new \RuntimeException($errorInfo['user_message']);
            }
        });
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

        // Clear file browsing cache patterns
        $patterns = [
            "github_repo_contents_{$owner}_{$repo}_*",
            "github_file_content_{$owner}_{$repo}_*",
            "github_repo_tree_{$owner}_{$repo}_*",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Clear pattern-based cache keys
        foreach ($patterns as $pattern) {
            try {
                if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                    $cacheKeys = Cache::getRedis()->keys($pattern);
                    foreach ($cacheKeys as $key) {
                        Cache::forget($key);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to clear cache pattern', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage(),
                ]);
            }
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

            $response = Http::withHeaders([
                'Authorization' => 'token '.$this->config['api_token'],
                'User-Agent' => 'ThinkTest-AI/1.0',
                'Accept' => 'application/vnd.github.v3+json',
            ])->get('https://api.github.com/user');

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
