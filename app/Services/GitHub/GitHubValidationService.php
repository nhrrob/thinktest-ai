<?php

namespace App\Services\GitHub;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class GitHubValidationService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('thinktest_ai.github');
    }

    /**
     * Validate GitHub repository URL with comprehensive security checks
     */
    public function validateRepositoryUrl(string $url, ?int $userId = null): array
    {
        // Basic URL validation
        $this->validateUrlFormat($url);

        // Security validation
        $this->validateUrlSecurity($url);

        // Rate limiting validation
        if ($userId) {
            $this->validateRateLimit($userId);
        }

        // Extract and validate repository components
        $repoData = $this->extractRepositoryData($url);

        // Validate repository name components
        $this->validateRepositoryComponents($repoData);

        return $repoData;
    }

    /**
     * Validate URL format
     */
    private function validateUrlFormat(string $url): void
    {
        if (empty(trim($url))) {
            throw new \InvalidArgumentException('Repository URL cannot be empty');
        }

        if (strlen($url) > 500) {
            throw new \InvalidArgumentException('Repository URL is too long');
        }

        // Check for valid URL format
        if (! filter_var($url, FILTER_VALIDATE_URL) && ! preg_match('/^[a-zA-Z0-9\-_]+\/[a-zA-Z0-9\-_\.]+$/', $url)) {
            throw new \InvalidArgumentException('Invalid URL format');
        }
    }

    /**
     * Validate URL security
     */
    private function validateUrlSecurity(string $url): void
    {
        // Check for allowed domains
        $allowedDomains = $this->config['allowed_domains'] ?? ['github.com'];

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($url);
            $domain = $parsedUrl['host'] ?? '';

            if (! in_array($domain, $allowedDomains)) {
                throw new \InvalidArgumentException("Domain '{$domain}' is not allowed. Only ".implode(', ', $allowedDomains).' are permitted');
            }
        }

        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/javascript:/i',
            '/data:/i',
            '/vbscript:/i',
            '/file:/i',
            '/ftp:/i',
            '/<script/i',
            '/\.\./i', // Path traversal
            '/localhost/i',
            '/127\.0\.0\.1/i',
            '/192\.168\./i',
            '/10\./i',
            '/172\.(1[6-9]|2[0-9]|3[01])\./i', // Private IP ranges
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                throw new \InvalidArgumentException('URL contains suspicious patterns');
            }
        }
    }

    /**
     * Validate rate limiting
     */
    public function validateRateLimit(int $userId): void
    {
        $hourlyKey = "github_requests_hourly_{$userId}";
        $minuteKey = "github_requests_minute_{$userId}";

        $hourlyLimit = $this->config['rate_limit_requests_per_hour'] ?? 100;
        $minuteLimit = $this->config['rate_limit_requests_per_minute'] ?? 10;

        // Check hourly limit
        if (RateLimiter::tooManyAttempts($hourlyKey, $hourlyLimit)) {
            $seconds = RateLimiter::availableIn($hourlyKey);
            throw new \RuntimeException('Too many requests. Try again in '.ceil($seconds / 60).' minutes.');
        }

        // Check minute limit
        if (RateLimiter::tooManyAttempts($minuteKey, $minuteLimit)) {
            $seconds = RateLimiter::availableIn($minuteKey);
            throw new \RuntimeException("Too many requests. Try again in {$seconds} seconds.");
        }

        // Increment counters
        RateLimiter::hit($hourlyKey, 3600); // 1 hour
        RateLimiter::hit($minuteKey, 60);   // 1 minute
    }

    /**
     * Extract repository data from URL
     */
    private function extractRepositoryData(string $url): array
    {
        // Clean and normalize URL
        $url = trim($url);
        $url = preg_replace('/\.git$/', '', $url);

        // Support various GitHub URL formats
        $patterns = [
            '/^https:\/\/github\.com\/([^\/]+)\/([^\/\?#]+)\/?(\?.*)?$/',
            '/^git@github\.com:([^\/]+)\/([^\/]+)\.git$/',
            '/^([a-zA-Z0-9\-_]+)\/([a-zA-Z0-9\-_\.]+)$/', // Simple owner/repo format
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $owner = $matches[1];
                $repo = $matches[2];

                return [
                    'owner' => $owner,
                    'repo' => $repo,
                    'full_name' => "{$owner}/{$repo}",
                    'url' => "https://github.com/{$owner}/{$repo}",
                ];
            }
        }

        throw new \InvalidArgumentException('Invalid GitHub repository URL format');
    }

    /**
     * Validate repository components
     */
    public function validateRepositoryComponents(array $repoData): void
    {
        $owner = $repoData['owner'];
        $repo = $repoData['repo'];

        // Validate owner name
        if (! $this->isValidGitHubName($owner)) {
            throw new \InvalidArgumentException("Invalid repository owner name: {$owner}");
        }

        // Validate repository name
        if (! $this->isValidGitHubName($repo)) {
            throw new \InvalidArgumentException("Invalid repository name: {$repo}");
        }

        // Check for reserved names
        $reservedNames = ['api', 'www', 'github', 'admin', 'root', 'support'];
        if (in_array(strtolower($owner), $reservedNames) || in_array(strtolower($repo), $reservedNames)) {
            throw new \InvalidArgumentException('Repository uses reserved names');
        }
    }

    /**
     * Validate GitHub username/repository name
     */
    private function isValidGitHubName(string $name): bool
    {
        // GitHub username/repo name rules:
        // - Can contain alphanumeric characters, hyphens, dots, and underscores
        // - Cannot start or end with hyphen or dot
        // - Cannot contain consecutive hyphens
        // - Maximum 39 characters for usernames, 100 for repositories

        if (strlen($name) > 100) {
            return false;
        }

        if (strlen($name) < 1) {
            return false;
        }

        // Check valid characters and patterns
        if (! preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-\._]{0,98}[a-zA-Z0-9])?$/', $name)) {
            return false;
        }

        // Check for consecutive hyphens
        if (str_contains($name, '--')) {
            return false;
        }

        return true;
    }

    /**
     * Validate repository size
     */
    public function validateRepositorySize(int $sizeBytes): void
    {
        $maxSize = $this->config['max_repository_size'] ?? 52428800; // 50MB

        if ($sizeBytes > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 1);
            $currentSizeMB = round($sizeBytes / 1024 / 1024, 1);
            throw new \RuntimeException("Repository size ({$currentSizeMB}MB) exceeds maximum allowed size ({$maxSizeMB}MB)");
        }
    }

    /**
     * Validate file count
     */
    public function validateFileCount(int $fileCount): void
    {
        $maxFiles = $this->config['max_files_per_repo'] ?? 1000;

        if ($fileCount > $maxFiles) {
            throw new \RuntimeException("Repository contains too many files ({$fileCount}). Maximum allowed: {$maxFiles}");
        }
    }

    /**
     * Validate branch name
     */
    public function validateBranchName(string $branchName): void
    {
        if (empty(trim($branchName))) {
            throw new \InvalidArgumentException('Branch name cannot be empty');
        }

        if (strlen($branchName) > 250) {
            throw new \InvalidArgumentException('Branch name is too long');
        }

        // Check for invalid characters
        $invalidPatterns = [
            '/\.\.$/',     // Cannot end with ..
            '/^\./',       // Cannot start with .
            '/\/$/',       // Cannot end with /
            '/\/\//',      // Cannot contain //
            '/[\x00-\x1f\x7f]/', // Control characters
            '/[~^:?*\[\]]/', // Invalid characters
            '/\.lock$/',   // Cannot end with .lock
        ];

        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $branchName)) {
                throw new \InvalidArgumentException("Invalid branch name: {$branchName}");
            }
        }
    }

    /**
     * Sanitize file content
     */
    public function sanitizeFileContent(string $content): string
    {
        // Remove null bytes
        $content = str_replace("\0", '', $content);

        // Limit content size
        $maxSize = $this->config['max_repository_size'] ?? 52428800;
        if (strlen($content) > $maxSize) {
            throw new \RuntimeException('Content size exceeds maximum allowed size');
        }

        return $content;
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        Log::warning("GitHub Security Event: {$event}", array_merge($context, [
            'timestamp' => now()->toISOString(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
        ]));
    }
}
