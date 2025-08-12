<?php

namespace App\Services\GitHub;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PharData;

class GitHubRepositoryService
{
    private GitHubService $githubService;

    private array $config;

    public function __construct(GitHubService $githubService)
    {
        $this->githubService = $githubService;
        $this->config = config('thinktest_ai.github');
    }

    /**
     * Process GitHub repository for WordPress plugin analysis
     */
    public function processRepository(string $owner, string $repo, ?string $branch = null, ?int $userId = null): array
    {
        // Only log for debugging when needed
        if (config('app.debug')) {
            Log::debug('Starting GitHub repository processing', [
                'owner' => $owner,
                'repo' => $repo,
                'branch' => $branch,
            ]);
        }

        try {
            // Verify GitHub API authentication before processing
            $authVerification = $this->githubService->verifyApiToken();
            if (! $authVerification['valid']) {
                Log::error('GitHub API authentication failed', [
                    'error' => $authVerification['error'],
                    'owner' => $owner,
                    'repo' => $repo,
                ]);
                throw new \RuntimeException('GitHub API authentication failed: '.$authVerification['error']);
            }

            Log::info('GitHub API authentication verified', [
                'user' => $authVerification['user'] ?? 'unknown',
                'scopes' => $authVerification['scopes'] ?? 'unknown',
                'rate_limit_remaining' => $authVerification['rate_limit_remaining'] ?? 'unknown',
            ]);

            // Get repository information
            $repoInfo = $this->githubService->getRepositoryInfo($owner, $repo);

            // Use default branch if none specified
            $branch = $branch ?: $repoInfo['default_branch'];

            // Download repository tarball
            $tarballPath = $this->githubService->downloadRepositoryTarball($owner, $repo, $branch);

            // Extract and process files
            $extractedPath = $this->extractTarball($tarballPath);

            // Detect WordPress plugin structure
            $pluginStructure = $this->detectWordPressPluginStructure($extractedPath);

            // Process plugin files
            $processedContent = $this->processPluginFiles($extractedPath, $pluginStructure);

            // Generate unique filename for storage
            $filename = $this->generateStorageFilename($owner, $repo, $branch);
            $fileHash = hash('sha256', $processedContent['content']);

            // Store processed content
            $storedPath = $this->storeProcessedContent($processedContent['content'], $filename);

            // Cleanup temporary files
            $this->cleanupTemporaryFiles([$tarballPath, $extractedPath]);

            // Only log success in debug mode to reduce noise
            if (config('app.debug')) {
                Log::debug('GitHub repository processed successfully', [
                    'owner' => $owner,
                    'repo' => $repo,
                    'file_count' => $processedContent['file_count'],
                ]);
            }

            return [
                'filename' => "{$owner}/{$repo}@{$branch}",
                'stored_path' => $storedPath,
                'file_hash' => $fileHash,
                'content' => $processedContent['content'],
                'size' => strlen($processedContent['content']),
                'extension' => 'php',
                'repository_info' => $repoInfo,
                'branch' => $branch,
                'plugin_structure' => $pluginStructure,
                'file_count' => $processedContent['file_count'],
                'processed_files' => $processedContent['processed_files'],
            ];

        } catch (\Exception $e) {
            Log::error('GitHub repository processing failed', [
                'owner' => $owner,
                'repo' => $repo,
                'branch' => $branch,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Repository processing failed: '.$e->getMessage());
        }
    }

    /**
     * Extract tarball to temporary directory
     */
    private function extractTarball(string $tarballPath): string
    {
        $extractPath = storage_path('app/temp/github_extracts/'.uniqid('repo_', true));

        if (! is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        try {
            $phar = new PharData($tarballPath);
            $phar->extractTo($extractPath);

            // GitHub tarballs create a subdirectory with the commit hash
            // Find the actual extracted directory
            $extractedDirs = glob($extractPath.'/*', GLOB_ONLYDIR);
            if (empty($extractedDirs)) {
                throw new \RuntimeException('No directories found in extracted tarball');
            }

            return $extractedDirs[0]; // Return the first (and should be only) directory

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to extract tarball: '.$e->getMessage());
        }
    }

    /**
     * Detect WordPress plugin structure in repository
     */
    private function detectWordPressPluginStructure(string $extractedPath): array
    {
        $structure = [
            'type' => 'unknown',
            'main_plugin_file' => null,
            'plugin_directories' => [],
            'has_wordpress_files' => false,
            'detected_patterns' => [],
        ];

        // Look for WordPress plugin indicators
        $this->scanForWordPressPatterns($extractedPath, $structure);

        // Determine plugin type
        if (! empty($structure['plugin_directories'])) {
            $structure['type'] = 'multi_plugin';
        } elseif ($structure['main_plugin_file']) {
            $structure['type'] = 'single_plugin';
        } elseif ($structure['has_wordpress_files']) {
            $structure['type'] = 'wordpress_project';
        }

        return $structure;
    }

    /**
     * Scan directory for WordPress patterns
     */
    private function scanForWordPressPatterns(string $path, array &$structure): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($path.'/', '', $file->getPathname());

                // Skip ignored directories
                if ($this->shouldIgnoreFile($relativePath)) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());

                // Check for WordPress plugin header
                if (preg_match('/Plugin Name:\s*(.+)/i', $content, $matches)) {
                    $structure['main_plugin_file'] = $relativePath;
                    $structure['detected_patterns'][] = 'plugin_header';
                }

                // Check for WordPress functions
                if (preg_match('/\b(add_action|add_filter|wp_enqueue_script|wp_enqueue_style)\b/', $content)) {
                    $structure['has_wordpress_files'] = true;
                    $structure['detected_patterns'][] = 'wordpress_functions';
                }

                // Check for Elementor patterns
                if (preg_match('/\b(Widget_Base|Controls_Manager|Elementor)\b/', $content)) {
                    $structure['detected_patterns'][] = 'elementor_widget';
                }

                // Detect plugin directories (directories with plugin files)
                $dir = dirname($relativePath);
                if ($dir !== '.' && ! in_array($dir, $structure['plugin_directories'])) {
                    if (preg_match('/Plugin Name:/i', $content) ||
                        (basename($file->getPathname()) === 'index.php' && preg_match('/wp_die|exit|die/', $content))) {
                        $structure['plugin_directories'][] = $dir;
                    }
                }
            }
        }
    }

    /**
     * Process plugin files and combine content
     */
    private function processPluginFiles(string $extractedPath, array $pluginStructure): array
    {
        $content = '';
        $fileCount = 0;
        $processedFiles = [];
        $maxFiles = $this->config['max_files_per_repo'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractedPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($fileCount >= $maxFiles) {
                Log::warning('Repository contains too many files, limiting processing', [
                    'max_files' => $maxFiles,
                    'total_found' => $fileCount,
                ]);
                break;
            }

            if ($file->isFile()) {
                $relativePath = str_replace($extractedPath.'/', '', $file->getPathname());
                $extension = $file->getExtension();

                // Skip ignored files and directories
                if ($this->shouldIgnoreFile($relativePath) ||
                    ! in_array('.'.$extension, $this->config['supported_file_extensions'])) {
                    continue;
                }

                $fileContent = file_get_contents($file->getPathname());

                // Add file separator and content
                $content .= "\n\n// File: {$relativePath}\n";
                $content .= $fileContent;

                $fileCount++;
                $processedFiles[] = [
                    'path' => $relativePath,
                    'size' => $file->getSize(),
                    'extension' => $extension,
                ];
            }
        }

        if (empty($content)) {
            throw new \RuntimeException('No supported files found in repository');
        }

        return [
            'content' => $content,
            'file_count' => $fileCount,
            'processed_files' => $processedFiles,
        ];
    }

    /**
     * Check if file should be ignored
     */
    private function shouldIgnoreFile(string $relativePath): bool
    {
        $ignoredDirs = $this->config['ignored_directories'];

        foreach ($ignoredDirs as $ignoredDir) {
            if (str_starts_with($relativePath, $ignoredDir.'/') || $relativePath === $ignoredDir) {
                return true;
            }
        }

        // Ignore hidden files and directories
        if (str_contains($relativePath, '/.')) {
            return true;
        }

        return false;
    }

    /**
     * Generate storage filename
     */
    private function generateStorageFilename(string $owner, string $repo, string $branch): string
    {
        $timestamp = time();
        $hash = substr(md5("{$owner}/{$repo}@{$branch}"), 0, 8);

        return "github_{$owner}_{$repo}_{$branch}_{$timestamp}_{$hash}.php";
    }

    /**
     * Store processed content
     */
    private function storeProcessedContent(string $content, string $filename): string
    {
        $uploadPath = config('thinktest_ai.file_processing.upload_path', 'uploads/plugins');
        $storedPath = Storage::put($uploadPath.'/'.$filename, $content);

        return $uploadPath.'/'.$filename;
    }

    /**
     * Cleanup temporary files and directories
     */
    private function cleanupTemporaryFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDirectory($path);
            }
        }
    }

    /**
     * Recursively remove directory
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
