<?php

namespace App\Services\GitHub;

use Github\Exception\RuntimeException as GitHubRuntimeException;
use Github\Exception\ValidationFailedException;
use Github\Exception\ErrorException;
use Illuminate\Support\Facades\Log;

class GitHubErrorHandler
{
    /**
     * Handle GitHub API exceptions and return user-friendly messages
     */
    public static function handleException(\Exception $exception, array $context = []): array
    {
        $errorCode = $exception->getCode();
        $errorMessage = $exception->getMessage();
        
        Log::error('GitHub API Error', array_merge($context, [
            'exception_class' => get_class($exception),
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'trace' => $exception->getTraceAsString(),
        ]));

        // Handle specific GitHub API exceptions
        if ($exception instanceof GitHubRuntimeException) {
            return self::handleGitHubRuntimeException($exception, $context);
        }

        if ($exception instanceof ValidationFailedException) {
            return self::handleValidationException($exception, $context);
        }

        if ($exception instanceof ErrorException) {
            return self::handleErrorException($exception, $context);
        }

        // Handle network and general exceptions
        return self::handleGeneralException($exception, $context);
    }

    /**
     * Handle GitHub Runtime exceptions (API errors)
     */
    private static function handleGitHubRuntimeException(GitHubRuntimeException $exception, array $context): array
    {
        $code = $exception->getCode();
        $message = $exception->getMessage();

        switch ($code) {
            case 401:
                return [
                    'user_message' => 'Authentication failed. Please check your GitHub credentials or token.',
                    'technical_message' => 'GitHub API authentication failed',
                    'error_code' => 'GITHUB_AUTH_FAILED',
                    'http_status' => 401,
                    'retry_possible' => false,
                ];

            case 403:
                if (str_contains($message, 'rate limit')) {
                    return [
                        'user_message' => 'GitHub API rate limit exceeded. Please try again later.',
                        'technical_message' => 'GitHub API rate limit exceeded',
                        'error_code' => 'GITHUB_RATE_LIMIT',
                        'http_status' => 429,
                        'retry_possible' => true,
                        'retry_after' => self::extractRateLimitResetTime($message),
                    ];
                }

                if (str_contains($message, 'private') || str_contains($message, 'access')) {
                    return [
                        'user_message' => 'Access denied. The repository may be private or you may not have permission to access it.',
                        'technical_message' => 'GitHub repository access denied',
                        'error_code' => 'GITHUB_ACCESS_DENIED',
                        'http_status' => 403,
                        'retry_possible' => false,
                    ];
                }

                return [
                    'user_message' => 'Access forbidden. Please check your permissions.',
                    'technical_message' => 'GitHub API access forbidden',
                    'error_code' => 'GITHUB_FORBIDDEN',
                    'http_status' => 403,
                    'retry_possible' => false,
                ];

            case 404:
                return [
                    'user_message' => 'Repository not found. Please check the repository URL and ensure it exists.',
                    'technical_message' => 'GitHub repository not found',
                    'error_code' => 'GITHUB_NOT_FOUND',
                    'http_status' => 404,
                    'retry_possible' => false,
                ];

            case 422:
                return [
                    'user_message' => 'Invalid repository data. Please check your input and try again.',
                    'technical_message' => 'GitHub API validation failed',
                    'error_code' => 'GITHUB_VALIDATION_FAILED',
                    'http_status' => 422,
                    'retry_possible' => false,
                ];

            case 500:
            case 502:
            case 503:
            case 504:
                return [
                    'user_message' => 'GitHub is experiencing issues. Please try again in a few minutes.',
                    'technical_message' => 'GitHub API server error',
                    'error_code' => 'GITHUB_SERVER_ERROR',
                    'http_status' => 503,
                    'retry_possible' => true,
                    'retry_after' => 300, // 5 minutes
                ];

            default:
                return [
                    'user_message' => 'An unexpected error occurred while accessing GitHub. Please try again.',
                    'technical_message' => "GitHub API error: {$message}",
                    'error_code' => 'GITHUB_UNKNOWN_ERROR',
                    'http_status' => 500,
                    'retry_possible' => true,
                ];
        }
    }

    /**
     * Handle validation exceptions
     */
    private static function handleValidationException(ValidationFailedException $exception, array $context): array
    {
        return [
            'user_message' => 'Invalid data provided. Please check your input and try again.',
            'technical_message' => 'GitHub API validation failed: ' . $exception->getMessage(),
            'error_code' => 'GITHUB_VALIDATION_ERROR',
            'http_status' => 422,
            'retry_possible' => false,
        ];
    }

    /**
     * Handle error exceptions
     */
    private static function handleErrorException(ErrorException $exception, array $context): array
    {
        return [
            'user_message' => 'A GitHub API error occurred. Please try again.',
            'technical_message' => 'GitHub API error: ' . $exception->getMessage(),
            'error_code' => 'GITHUB_API_ERROR',
            'http_status' => 500,
            'retry_possible' => true,
        ];
    }

    /**
     * Handle general exceptions (network, timeout, etc.)
     */
    private static function handleGeneralException(\Exception $exception, array $context): array
    {
        $message = $exception->getMessage();

        // Network timeout
        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return [
                'user_message' => 'Request timed out. The repository may be too large or GitHub is slow to respond. Please try again.',
                'technical_message' => 'Network timeout while accessing GitHub',
                'error_code' => 'NETWORK_TIMEOUT',
                'http_status' => 504,
                'retry_possible' => true,
                'retry_after' => 60,
            ];
        }

        // Connection issues
        if (str_contains($message, 'connection') || str_contains($message, 'network')) {
            return [
                'user_message' => 'Network connection error. Please check your internet connection and try again.',
                'technical_message' => 'Network connection error',
                'error_code' => 'NETWORK_ERROR',
                'http_status' => 503,
                'retry_possible' => true,
                'retry_after' => 30,
            ];
        }

        // SSL/TLS issues
        if (str_contains($message, 'SSL') || str_contains($message, 'certificate')) {
            return [
                'user_message' => 'Secure connection error. Please try again later.',
                'technical_message' => 'SSL/TLS connection error',
                'error_code' => 'SSL_ERROR',
                'http_status' => 503,
                'retry_possible' => true,
                'retry_after' => 60,
            ];
        }

        // Generic error
        return [
            'user_message' => 'An unexpected error occurred. Please try again later.',
            'technical_message' => $message,
            'error_code' => 'UNKNOWN_ERROR',
            'http_status' => 500,
            'retry_possible' => true,
        ];
    }

    /**
     * Extract rate limit reset time from error message
     */
    private static function extractRateLimitResetTime(string $message): ?int
    {
        // Try to extract reset time from GitHub rate limit message
        if (preg_match('/reset at (\d+)/', $message, $matches)) {
            $resetTime = (int) $matches[1];
            return max(0, $resetTime - time());
        }

        // Default to 1 hour if we can't parse the reset time
        return 3600;
    }

    /**
     * Get user-friendly error message for common scenarios
     */
    public static function getScenarioMessage(string $scenario, array $params = []): string
    {
        $messages = [
            'repository_too_large' => 'The repository is too large to process. Maximum size allowed is {max_size}MB.',
            'too_many_files' => 'The repository contains too many files. Maximum allowed is {max_files} files.',
            'invalid_repository_url' => 'Invalid repository URL. Please use the format: https://github.com/owner/repository',
            'private_repository_no_access' => 'This is a private repository and you don\'t have access to it. Please ensure you have the necessary permissions.',
            'branch_not_found' => 'The specified branch "{branch}" was not found in the repository.',
            'no_php_files' => 'No PHP files were found in the repository. Please ensure this is a WordPress plugin repository.',
            'processing_timeout' => 'Repository processing timed out. The repository may be too large or complex.',
            'rate_limit_exceeded' => 'You have exceeded the rate limit. Please wait {retry_after} seconds before trying again.',
        ];

        $message = $messages[$scenario] ?? 'An error occurred while processing your request.';

        // Replace parameters in the message
        foreach ($params as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }

    /**
     * Log user action for debugging
     */
    public static function logUserAction(string $action, array $context = []): void
    {
        Log::info("GitHub User Action: {$action}", array_merge($context, [
            'timestamp' => now()->toISOString(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
        ]));
    }
}
