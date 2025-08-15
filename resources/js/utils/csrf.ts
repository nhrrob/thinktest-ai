/**
 * CSRF Token Utility Functions
 * 
 * Provides utilities for handling CSRF tokens in API requests,
 * including automatic token refresh when tokens become stale.
 */

/**
 * Get the current CSRF token from the meta tag
 */
export const getCurrentCsrfToken = (): string | null => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
};

/**
 * Get a fresh CSRF token from the server
 */
export const getFreshCsrfToken = async (): Promise<string | null> => {
    try {
        const response = await fetch('/auth/check', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const data = await response.json();
            if (data.authenticated && data.csrf_token) {
                // Update the meta tag with the fresh token
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.setAttribute('content', data.csrf_token);
                }
                return data.csrf_token;
            }
        }
    } catch (error) {
        console.error('Failed to get fresh CSRF token:', error);
    }
    return null;
};

/**
 * Make a fetch request with automatic CSRF token refresh on 419 errors
 */
export const fetchWithCsrfRetry = async (
    url: string,
    options: RequestInit = {}
): Promise<Response> => {
    const currentToken = getCurrentCsrfToken();

    // Prepare headers with CSRF token
    const headers = {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers,
        'X-CSRF-TOKEN': currentToken || '',
    };

    // Prepare request options
    const requestOptions: RequestInit = {
        ...options,
        headers,
        credentials: 'same-origin',
    };

    // Make the initial request
    let response = await fetch(url, requestOptions);

    // Handle CSRF token mismatch (419) by getting a fresh token and retrying
    if (response.status === 419) {
        console.log('CSRF token mismatch, attempting to refresh token...');
        const freshToken = await getFreshCsrfToken();

        if (freshToken) {
            console.log('Got fresh CSRF token, retrying request...');

            // Update headers with fresh token
            const updatedHeaders = {
                ...headers,
                'X-CSRF-TOKEN': freshToken,
            };

            // Retry the request with fresh token
            response = await fetch(url, {
                ...requestOptions,
                headers: updatedHeaders,
            });
        }
    }

    return response;
};

/**
 * Handle common response errors for GitHub API requests
 */
export const handleApiResponse = async (response: Response): Promise<any> => {
    // Handle CSRF token mismatch (419) - should not happen with fetchWithCsrfRetry
    if (response.status === 419) {
        alert('Session expired. Please refresh the page and try again.');
        window.location.reload();
        return;
    }

    // Handle authentication errors (401)
    if (response.status === 401) {
        alert('Authentication required. Please refresh the page and log in again.');
        window.location.reload();
        return;
    }

    // Parse JSON response
    const result = await response.json();
    return result;
};
