import { useState, useEffect } from 'react';

/**
 * Custom hook for managing localStorage state with TypeScript support
 * @param key - The localStorage key
 * @param initialValue - The initial value if no stored value exists
 * @returns [storedValue, setValue] - Similar to useState but persisted
 */
export function useLocalStorage<T>(key: string, initialValue: T): [T, (value: T | ((val: T) => T)) => void] {
    // Get from local storage then parse stored json or return initialValue
    const [storedValue, setStoredValue] = useState<T>(() => {
        try {
            const item = window.localStorage.getItem(key);
            return item ? JSON.parse(item) : initialValue;
        } catch (error) {
            console.warn(`Error reading localStorage key "${key}":`, error);
            return initialValue;
        }
    });

    // Return a wrapped version of useState's setter function that persists the new value to localStorage
    const setValue = (value: T | ((val: T) => T)) => {
        try {
            // Allow value to be a function so we have the same API as useState
            const valueToStore = value instanceof Function ? value(storedValue) : value;
            setStoredValue(valueToStore);
            window.localStorage.setItem(key, JSON.stringify(valueToStore));
        } catch (error) {
            console.warn(`Error setting localStorage key "${key}":`, error);
        }
    };

    return [storedValue, setValue];
}

/**
 * Custom hook for managing session storage state
 * @param key - The sessionStorage key
 * @param initialValue - The initial value if no stored value exists
 * @returns [storedValue, setValue] - Similar to useState but persisted in session
 */
export function useSessionStorage<T>(key: string, initialValue: T): [T, (value: T | ((val: T) => T)) => void] {
    const [storedValue, setStoredValue] = useState<T>(() => {
        try {
            const item = window.sessionStorage.getItem(key);
            return item ? JSON.parse(item) : initialValue;
        } catch (error) {
            console.warn(`Error reading sessionStorage key "${key}":`, error);
            return initialValue;
        }
    });

    const setValue = (value: T | ((val: T) => T)) => {
        try {
            const valueToStore = value instanceof Function ? value(storedValue) : value;
            setStoredValue(valueToStore);
            window.sessionStorage.setItem(key, JSON.stringify(valueToStore));
        } catch (error) {
            console.warn(`Error setting sessionStorage key "${key}":`, error);
        }
    };

    return [storedValue, setValue];
}

/**
 * Hook for managing GitHub-specific state persistence
 */
export interface GitHubPersistedState {
    selectedRepository: {
        owner: string;
        repo: string;
        full_name: string;
        default_branch: string;
    } | null;
    selectedBranch: {
        name: string;
        commit: {
            sha: string;
            url: string;
        };
        protected: boolean;
    } | null;
    selectedFile: {
        name: string;
        path: string;
        type: 'file' | 'dir';
        size: number;
        sha: string;
        url: string;
        html_url: string;
        download_url?: string;
    } | null;
    expandedPaths: string[];
    searchQuery: string;
}

/**
 * Branch cache interface for storing branch data with expiration
 */
export interface BranchCacheEntry {
    branches: Array<{
        name: string;
        commit_sha: string;
        commit_url: string;
        protected: boolean;
    }>;
    timestamp: number;
    expiresAt: number;
}

export interface BranchCache {
    [repositoryKey: string]: BranchCacheEntry;
}

/**
 * Repository tree cache interface for storing tree data with expiration
 */
export interface TreeCacheEntry {
    tree: Array<{
        name: string;
        path: string;
        type: 'file' | 'dir';
        size: number;
        sha: string;
        url: string;
        html_url: string;
        download_url?: string;
    }>;
    timestamp: number;
    expiresAt: number;
}

export interface TreeCache {
    [repositoryKey: string]: TreeCacheEntry;
}

/**
 * Hook for managing branch cache with localStorage persistence
 */
export function useBranchCache() {
    const [cache, setCache] = useLocalStorage<BranchCache>('thinktest-branch-cache', {});

    // Cache duration in milliseconds (5 minutes)
    const CACHE_DURATION = 5 * 60 * 1000;

    const generateCacheKey = (owner: string, repo: string): string => {
        return `${owner}/${repo}`.toLowerCase();
    };

    const isExpired = (entry: BranchCacheEntry): boolean => {
        return Date.now() > entry.expiresAt;
    };

    const getCachedBranches = (owner: string, repo: string) => {
        const cacheKey = generateCacheKey(owner, repo);
        const entry = cache[cacheKey];

        if (!entry || isExpired(entry)) {
            return null;
        }

        return entry.branches;
    };

    const setCachedBranches = (owner: string, repo: string, branches: BranchCacheEntry['branches']) => {
        const cacheKey = generateCacheKey(owner, repo);
        const now = Date.now();

        const entry: BranchCacheEntry = {
            branches,
            timestamp: now,
            expiresAt: now + CACHE_DURATION
        };

        setCache(prev => ({
            ...prev,
            [cacheKey]: entry
        }));
    };

    const invalidateCache = (owner?: string, repo?: string) => {
        if (owner && repo) {
            // Invalidate specific repository cache
            const cacheKey = generateCacheKey(owner, repo);
            setCache(prev => {
                const newCache = { ...prev };
                delete newCache[cacheKey];
                return newCache;
            });
        } else {
            // Clear all cache
            setCache({});
        }
    };

    const cleanExpiredEntries = () => {
        const now = Date.now();
        setCache(prev => {
            const newCache: BranchCache = {};
            let cleanedCount = 0;
            Object.entries(prev).forEach(([key, entry]) => {
                if (entry.expiresAt > now) {
                    newCache[key] = entry;
                } else {
                    cleanedCount++;
                }
            });
            if (cleanedCount > 0) {
                console.log(`[BranchCache] Cleaned ${cleanedCount} expired cache entries`);
            }
            return newCache;
        });
    };

    const getCacheInfo = (owner: string, repo: string) => {
        const cacheKey = generateCacheKey(owner, repo);
        const entry = cache[cacheKey];

        if (!entry) {
            return { cached: false, expired: false, age: 0 };
        }

        const age = Date.now() - entry.timestamp;
        const expired = isExpired(entry);

        return {
            cached: true,
            expired,
            age,
            expiresIn: Math.max(0, entry.expiresAt - Date.now())
        };
    };

    const getCacheStats = () => {
        const now = Date.now();
        const entries = Object.entries(cache);
        const total = entries.length;
        const expired = entries.filter(([, entry]) => isExpired(entry)).length;
        const active = total - expired;

        return {
            total,
            active,
            expired,
            repositories: entries.map(([key, entry]) => ({
                repository: key,
                cached: !isExpired(entry),
                age: now - entry.timestamp,
                expiresIn: Math.max(0, entry.expiresAt - now),
                branchCount: entry.branches.length
            }))
        };
    };

    return {
        getCachedBranches,
        setCachedBranches,
        invalidateCache,
        cleanExpiredEntries,
        getCacheInfo,
        getCacheStats
    };
}

/**
 * Hook for managing repository tree cache with localStorage persistence
 */
export function useTreeCache() {
    const [cache, setCache] = useLocalStorage<TreeCache>('thinktest-tree-cache', {});

    // Cache duration in milliseconds (10 minutes for tree data)
    const CACHE_DURATION = 10 * 60 * 1000;

    const generateCacheKey = (owner: string, repo: string, branch: string): string => {
        return `${owner}/${repo}@${branch}`.toLowerCase();
    };

    const isExpired = (entry: TreeCacheEntry): boolean => {
        return Date.now() > entry.expiresAt;
    };

    const getCachedTree = (owner: string, repo: string, branch: string) => {
        const cacheKey = generateCacheKey(owner, repo, branch);
        const entry = cache[cacheKey];

        if (!entry || isExpired(entry)) {
            return null;
        }

        return entry.tree;
    };

    const setCachedTree = (owner: string, repo: string, branch: string, tree: TreeCacheEntry['tree']) => {
        const cacheKey = generateCacheKey(owner, repo, branch);
        const now = Date.now();

        const entry: TreeCacheEntry = {
            tree,
            timestamp: now,
            expiresAt: now + CACHE_DURATION
        };

        setCache(prev => ({
            ...prev,
            [cacheKey]: entry
        }));
    };

    const invalidateCache = (owner?: string, repo?: string, branch?: string) => {
        if (owner && repo && branch) {
            // Invalidate specific repository/branch cache
            const cacheKey = generateCacheKey(owner, repo, branch);
            setCache(prev => {
                const newCache = { ...prev };
                delete newCache[cacheKey];
                return newCache;
            });
        } else {
            // Clear all cache
            setCache({});
        }
    };

    const cleanExpiredEntries = () => {
        const now = Date.now();
        setCache(prev => {
            const newCache = { ...prev };
            let cleanedCount = 0;

            Object.entries(newCache).forEach(([key, entry]) => {
                if (isExpired(entry)) {
                    delete newCache[key];
                    cleanedCount++;
                }
            });

            if (cleanedCount > 0) {
                console.log(`[TreeCache] Cleaned ${cleanedCount} expired entries`);
            }

            return newCache;
        });
    };

    const getCacheInfo = (owner: string, repo: string, branch: string) => {
        const cacheKey = generateCacheKey(owner, repo, branch);
        const entry = cache[cacheKey];

        if (!entry) {
            return { cached: false, age: null, expiresIn: null };
        }

        const now = Date.now();
        return {
            cached: !isExpired(entry),
            age: entry.timestamp,
            expiresIn: Math.max(0, entry.expiresAt - now)
        };
    };

    return {
        getCachedTree,
        setCachedTree,
        invalidateCache,
        cleanExpiredEntries,
        getCacheInfo
    };
}

export function useGitHubState() {
    const [persistedState, setPersistedState] = useLocalStorage<GitHubPersistedState>('thinktest-github-state', {
        selectedRepository: null,
        selectedBranch: null,
        selectedFile: null,
        expandedPaths: [],
        searchQuery: ''
    });

    const updateRepository = (repository: GitHubPersistedState['selectedRepository']) => {
        setPersistedState(prev => ({
            ...prev,
            selectedRepository: repository,
            // Reset dependent state when repository changes
            selectedBranch: null,
            selectedFile: null,
            expandedPaths: [],
            searchQuery: ''
        }));
    };

    const updateBranch = (branch: GitHubPersistedState['selectedBranch']) => {
        setPersistedState(prev => ({
            ...prev,
            selectedBranch: branch,
            // Reset file selection when branch changes
            selectedFile: null,
            expandedPaths: [],
            searchQuery: ''
        }));
    };

    const updateSelectedFile = (file: GitHubPersistedState['selectedFile']) => {
        setPersistedState(prev => ({
            ...prev,
            selectedFile: file
        }));
    };

    const updateExpandedPaths = (paths: string[]) => {
        setPersistedState(prev => ({
            ...prev,
            expandedPaths: paths
        }));
    };

    const updateSearchQuery = (query: string) => {
        setPersistedState(prev => ({
            ...prev,
            searchQuery: query
        }));
    };

    const clearState = () => {
        setPersistedState({
            selectedRepository: null,
            selectedBranch: null,
            selectedFile: null,
            expandedPaths: [],
            searchQuery: ''
        });
    };

    return {
        state: persistedState,
        updateRepository,
        updateBranch,
        updateSelectedFile,
        updateExpandedPaths,
        updateSearchQuery,
        clearState
    };
}
