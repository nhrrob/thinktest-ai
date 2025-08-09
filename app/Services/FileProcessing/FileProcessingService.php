<?php

namespace App\Services\FileProcessing;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class FileProcessingService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('thinktest_ai.file_processing', ['upload_path' => 'uploads/plugins', 'temp_path' => 'temp/processing', 'output_path' => 'outputs/tests', 'cleanup_after_hours' => 24, 'max_processing_time' => 300]);
    }

    /**
     * Process uploaded WordPress plugin file
     */
    public function processUploadedFile(UploadedFile $file, int $userId): array
    {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename
        $filename = $this->generateUniqueFilename($file);
        $fileHash = hash_file('sha256', $file->getPathname());

        // Store file
        $storedPath = $file->storeAs($this->config['upload_path'], $filename, 'local');

        Log::info('File uploaded successfully', [
            'user_id' => $userId,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'file_hash' => $fileHash,
            'file_size' => $file->getSize(),
        ]);

        // Extract and process file content
        $content = $this->extractFileContent($storedPath, $file->getClientOriginalExtension());

        return [
            'filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'file_hash' => $fileHash,
            'content' => $content,
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ];
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): void
    {
        $maxSize = config('thinktest_ai.wordpress.analysis.max_file_size');
        $allowedExtensions = config('thinktest_ai.wordpress.analysis.allowed_extensions');

        // Check file size
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException("File size exceeds maximum allowed size of " . ($maxSize / 1024 / 1024) . "MB");
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException("File type '{$extension}' is not allowed. Allowed types: " . implode(', ', $allowedExtensions));
        }

        // Basic security check
        $this->performSecurityCheck($file);
    }

    /**
     * Perform basic security checks on uploaded file
     */
    private function performSecurityCheck(UploadedFile $file): void
    {
        // Check for malicious file signatures
        $content = file_get_contents($file->getPathname());
        
        // Check for potentially dangerous PHP functions
        $dangerousFunctions = config('thinktest_ai.security.file_validation.blocked_php_functions');
        
        foreach ($dangerousFunctions as $function) {
            if (strpos($content, $function) !== false) {
                Log::warning('Potentially dangerous function detected in uploaded file', [
                    'function' => $function,
                    'filename' => $file->getClientOriginalName(),
                ]);
                
                if (config('thinktest_ai.security.file_validation.scan_for_malicious_code')) {
                    throw new \InvalidArgumentException("File contains potentially dangerous function: {$function}");
                }
            }
        }
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Extract content from file based on extension
     */
    private function extractFileContent(string $storedPath, string $extension): string
    {
        $fullPath = Storage::path($storedPath);

        switch (strtolower($extension)) {
            case 'php':
                return file_get_contents($fullPath);
                
            case 'zip':
                return $this->extractZipContent($fullPath);
                
            default:
                throw new \InvalidArgumentException("Unsupported file extension: {$extension}");
        }
    }

    /**
     * Extract content from ZIP file
     */
    private function extractZipContent(string $zipPath): string
    {
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath) !== TRUE) {
            throw new \RuntimeException('Failed to open ZIP file');
        }

        $content = '';
        $fileCount = 0;
        $maxFiles = config('thinktest_ai.wordpress.analysis.max_files_in_zip');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Skip directories and non-PHP files
            if (substr($filename, -1) === '/' || !str_ends_with($filename, '.php')) {
                continue;
            }

            $fileCount++;
            if ($fileCount > $maxFiles) {
                Log::warning('ZIP file contains too many PHP files', [
                    'zip_path' => $zipPath,
                    'file_count' => $zip->numFiles,
                    'max_allowed' => $maxFiles,
                ]);
                break;
            }

            $fileContent = $zip->getFromIndex($i);
            if ($fileContent !== false) {
                $content .= "\n\n// File: {$filename}\n";
                $content .= $fileContent;
            }
        }

        $zip->close();

        if (empty($content)) {
            throw new \RuntimeException('No PHP files found in ZIP archive');
        }

        return $content;
    }

    /**
     * Clean up old files
     */
    public function cleanupOldFiles(): int
    {
        $cleanupAfterHours = $this->config['cleanup_after_hours'];
        $cutoffTime = now()->subHours($cleanupAfterHours);
        
        $uploadPath = $this->config['upload_path'];
        $files = Storage::files($uploadPath);
        
        $deletedCount = 0;
        
        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            
            if ($lastModified < $cutoffTime->timestamp) {
                Storage::delete($file);
                $deletedCount++;
                
                Log::info('Cleaned up old file', [
                    'file' => $file,
                    'last_modified' => date('Y-m-d H:i:s', $lastModified),
                ]);
            }
        }

        return $deletedCount;
    }

    /**
     * Get file content by stored path
     */
    public function getFileContent(string $storedPath): string
    {
        if (!Storage::exists($storedPath)) {
            throw new \RuntimeException("File not found: {$storedPath}");
        }

        return Storage::get($storedPath);
    }

    /**
     * Delete file by stored path
     */
    public function deleteFile(string $storedPath): bool
    {
        if (Storage::exists($storedPath)) {
            return Storage::delete($storedPath);
        }

        return false;
    }
}
