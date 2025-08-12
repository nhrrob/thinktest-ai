/**
 * Centralized error message mapping for GitHub integration
 * Provides consistent, actionable error messages across the application
 */

export interface ErrorContext {
    action: 'fetch_tree' | 'fetch_file' | 'validate_repo' | 'browse_contents';
    repository?: string;
    branch?: string;
    filePath?: string;
    statusCode?: number;
    originalMessage?: string;
}

export class GitHubErrorMessages {
    /**
     * Get a user-friendly error message based on the error context
     */
    static getErrorMessage(context: ErrorContext): string {
        const { action, statusCode, originalMessage } = context;

        // Handle specific HTTP status codes
        if (statusCode) {
            switch (statusCode) {
                case 401:
                    return this.getUnauthorizedMessage(action);
                case 403:
                    return this.getForbiddenMessage(action, originalMessage);
                case 404:
                    return this.getNotFoundMessage(action, context);
                case 422:
                    return this.getValidationMessage(action, originalMessage);
                case 429:
                    return this.getRateLimitMessage(originalMessage);
                case 500:
                case 502:
                case 503:
                case 504:
                    return this.getServerErrorMessage(action);
                default:
                    return this.getGenericErrorMessage(action, statusCode, originalMessage);
            }
        }

        // Handle action-specific errors without status codes
        switch (action) {
            case 'fetch_tree':
                return 'Failed to load repository files. Please check the repository URL and branch name, then try again.';
            case 'fetch_file':
                return 'Failed to load file content. Please verify the file exists and try again.';
            case 'validate_repo':
                return 'Unable to validate repository. Please check the repository URL format and accessibility.';
            case 'browse_contents':
                return 'Failed to browse repository contents. Please verify repository access and try again.';
            default:
                return originalMessage || 'An unexpected error occurred. Please try again.';
        }
    }

    /**
     * Get network error message
     */
    static getNetworkErrorMessage(action: string): string {
        const actionMessages = {
            fetch_tree: 'Network error while loading repository files',
            fetch_file: 'Network error while loading file content',
            validate_repo: 'Network error while validating repository',
            browse_contents: 'Network error while browsing repository'
        };

        const baseMessage = actionMessages[action as keyof typeof actionMessages] || 'Network error occurred';
        return `${baseMessage}. Please check your internet connection and try again.`;
    }

    /**
     * Get rate limit error message with retry information
     */
    static getRateLimitMessage(originalMessage?: string, retryAfter?: number): string {
        if (retryAfter) {
            return `Rate limit exceeded. Retrying automatically in ${retryAfter} seconds. You can also try again manually after waiting.`;
        }
        
        if (originalMessage?.includes('retry')) {
            return originalMessage;
        }
        
        return 'Rate limit exceeded. Please wait a moment before trying again. Consider authenticating with GitHub for higher rate limits.';
    }

    /**
     * Get empty repository message
     */
    static getEmptyRepositoryMessage(): string {
        return 'Repository appears to be empty or contains no supported file types. Please verify the repository has files and the correct branch is selected.';
    }

    /**
     * Get invalid data structure message
     */
    static getInvalidDataMessage(expectedType: string, actualType: string): string {
        return `Invalid data received from GitHub API. Expected ${expectedType} but received ${actualType}. This may indicate a temporary API issue - please try again.`;
    }

    private static getUnauthorizedMessage(action: string): string {
        return 'Authentication required. Please ensure you have proper access to this repository and try again.';
    }

    private static getForbiddenMessage(action: string, originalMessage?: string): string {
        if (originalMessage?.toLowerCase().includes('rate limit')) {
            return this.getRateLimitMessage(originalMessage);
        }
        
        return 'Access denied. You may not have permission to access this repository, or it may be private. Please check repository permissions.';
    }

    private static getNotFoundMessage(action: string, context: ErrorContext): string {
        const { repository, branch, filePath } = context;
        
        switch (action) {
            case 'fetch_tree':
                if (branch) {
                    return `Repository or branch not found. Please verify that "${repository}" exists and branch "${branch}" is correct.`;
                }
                return `Repository "${repository}" not found. Please check the repository URL and ensure it exists.`;
            
            case 'fetch_file':
                return `File "${filePath}" not found. The file may have been moved, deleted, or the path may be incorrect.`;
            
            case 'validate_repo':
                return `Repository "${repository}" not found or is not accessible. Please check the repository URL.`;
            
            default:
                return 'The requested resource was not found. Please verify the repository and branch information.';
        }
    }

    private static getValidationMessage(action: string, originalMessage?: string): string {
        if (originalMessage) {
            return `Validation error: ${originalMessage}. Please check your input and try again.`;
        }
        
        return 'Invalid request data. Please check the repository URL, branch name, and file path format.';
    }

    private static getServerErrorMessage(action: string): string {
        const actionMessages = {
            fetch_tree: 'loading repository files',
            fetch_file: 'loading file content',
            validate_repo: 'validating repository',
            browse_contents: 'browsing repository contents'
        };

        const actionText = actionMessages[action as keyof typeof actionMessages] || 'processing your request';
        return `Server error occurred while ${actionText}. This is likely a temporary issue - please try again in a few moments.`;
    }

    private static getGenericErrorMessage(action: string, statusCode?: number, originalMessage?: string): string {
        let message = originalMessage || 'An unexpected error occurred';
        
        if (statusCode) {
            message += ` (HTTP ${statusCode})`;
        }
        
        message += '. Please try again or contact support if the problem persists.';
        
        return message;
    }
}

/**
 * Helper function to create error context
 */
export function createErrorContext(
    action: ErrorContext['action'],
    options: Partial<Omit<ErrorContext, 'action'>> = {}
): ErrorContext {
    return {
        action,
        ...options
    };
}

/**
 * Helper function to get error message quickly
 */
export function getGitHubErrorMessage(
    action: ErrorContext['action'],
    statusCode?: number,
    originalMessage?: string,
    additionalContext?: Partial<ErrorContext>
): string {
    const context = createErrorContext(action, {
        statusCode,
        originalMessage,
        ...additionalContext
    });
    
    return GitHubErrorMessages.getErrorMessage(context);
}
