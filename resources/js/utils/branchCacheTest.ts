/**
 * Test utility for verifying branch cache functionality
 * This file can be imported in the browser console to test caching behavior
 */

export const testBranchCache = () => {
    console.log('=== Branch Cache Test ===');
    
    // Get cache from localStorage
    const cacheData = localStorage.getItem('thinktest-branch-cache');
    if (!cacheData) {
        console.log('No cache data found');
        return;
    }
    
    try {
        const cache = JSON.parse(cacheData);
        console.log('Current cache contents:', cache);
        
        // Analyze cache entries
        const now = Date.now();
        Object.entries(cache).forEach(([repo, entry]: [string, any]) => {
            const isExpired = now > entry.expiresAt;
            const age = now - entry.timestamp;
            const expiresIn = Math.max(0, entry.expiresAt - now);
            
            console.log(`Repository: ${repo}`);
            console.log(`  - Branches: ${entry.branches.length}`);
            console.log(`  - Age: ${Math.floor(age / 1000)}s`);
            console.log(`  - Expires in: ${Math.floor(expiresIn / 1000)}s`);
            console.log(`  - Status: ${isExpired ? 'EXPIRED' : 'VALID'}`);
            console.log('---');
        });
        
    } catch (error) {
        console.error('Error parsing cache data:', error);
    }
};

export const clearBranchCache = () => {
    localStorage.removeItem('thinktest-branch-cache');
    console.log('Branch cache cleared');
};

export const simulateNetworkError = () => {
    console.log('To simulate network error:');
    console.log('1. Open Network tab in DevTools');
    console.log('2. Set throttling to "Offline"');
    console.log('3. Try to refresh branches - should fall back to cache');
};

// Make functions available globally for console testing
if (typeof window !== 'undefined') {
    (window as any).testBranchCache = testBranchCache;
    (window as any).clearBranchCache = clearBranchCache;
    (window as any).simulateNetworkError = simulateNetworkError;
}
