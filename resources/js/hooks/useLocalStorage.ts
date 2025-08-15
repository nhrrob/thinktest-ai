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
