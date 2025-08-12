import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Calendar, ExternalLink, FileText, GitBranch, Github, Loader2 } from 'lucide-react';
import { useState } from 'react';

interface Repository {
    id: number;
    name: string;
    full_name: string;
    description?: string;
    private: boolean;
    default_branch: string;
    size: number;
    language?: string;
    updated_at: string;
    html_url: string;
    owner: string;
    repo: string;
}

interface GitHubRepositoryInputProps {
    onRepositoryValidated: (repository: Repository) => void;
    onError: (error: string) => void;
    disabled?: boolean;
}

export default function GitHubRepositoryInput({ onRepositoryValidated, onError, disabled = false }: GitHubRepositoryInputProps) {
    const [repositoryUrl, setRepositoryUrl] = useState('');
    const [isValidating, setIsValidating] = useState(false);
    const [validatedRepository, setValidatedRepository] = useState<Repository | null>(null);
    const [error, setError] = useState<string | null>(null);

    // Demo repository for evaluation purposes
    const demoRepository = 'https://github.com/nhrrob/nhrrob-core-contributions';

    const handleValidateRepository = async () => {
        if (!repositoryUrl.trim()) {
            setError('Please enter a repository URL');
            return;
        }

        setIsValidating(true);
        setError(null);
        setValidatedRepository(null);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/thinktest/github/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify({
                    repository_url: repositoryUrl,
                }),
            });

            // Handle CSRF token mismatch (419) by reloading the page
            if (response.status === 419) {
                console.error('CSRF token mismatch detected');
                alert('Session expired. Please refresh the page and try again.');
                window.location.reload();
                return;
            }

            // Handle authentication errors (401) by reloading the page
            if (response.status === 401) {
                console.error('Unauthorized access detected');
                alert('Authentication required. Please refresh the page and log in again.');
                window.location.reload();
                return;
            }

            const result = await response.json();

            if (result.success) {
                setValidatedRepository(result.repository);
                onRepositoryValidated(result.repository);
            } else {
                let errorMessage = result.message || 'Failed to validate repository';

                // Handle specific HTTP status codes
                if (response.status === 422) {
                    errorMessage = result.message || 'Invalid repository URL format.';
                } else if (response.status === 429) {
                    errorMessage = result.message || 'Rate limit exceeded. Please try again later.';
                }

                setError(errorMessage);
                onError(errorMessage);

                // Handle retry scenarios
                if (result.retry_possible && result.retry_after) {
                    setTimeout(() => {
                        setError(null);
                    }, result.retry_after * 1000);
                }
            }
        } catch (err) {
            let errorMessage = 'Network error occurred while validating repository';

            if (err instanceof Error) {
                if (err.message.includes('timeout')) {
                    errorMessage = 'Request timed out. Please try again.';
                } else if (err.message.includes('network')) {
                    errorMessage = 'Network connection error. Please check your internet connection.';
                }
            }

            setError(errorMessage);
            onError(errorMessage);
        } finally {
            setIsValidating(false);
        }
    };

    const handleUrlChange = (value: string) => {
        setRepositoryUrl(value);
        setError(null);
        setValidatedRepository(null);
    };

    const handleUseDemoRepository = () => {
        setRepositoryUrl(demoRepository);
        setError(null);
        setValidatedRepository(null);
    };

    const formatFileSize = (bytes: number): string => {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        return `${size.toFixed(1)} ${units[unitIndex]}`;
    };

    const formatDate = (dateString: string): string => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    return (
        <div className="space-y-4">
            <div className="space-y-2">
                <Label htmlFor="repository-url" className="text-sm font-medium text-gray-700">
                    GitHub Repository URL
                </Label>
                <div className="flex space-x-2">
                    <div className="relative flex-1">
                        <Github className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                        <Input
                            id="repository-url"
                            type="text"
                            placeholder="https://github.com/owner/repository"
                            value={repositoryUrl}
                            onChange={(e) => handleUrlChange(e.target.value)}
                            disabled={disabled || isValidating}
                            className="pl-10"
                        />
                    </div>
                    <Button onClick={handleValidateRepository} disabled={disabled || isValidating || !repositoryUrl.trim()} className="px-4">
                        {isValidating ? (
                            <>
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                Validating...
                            </>
                        ) : (
                            'Validate'
                        )}
                    </Button>
                </div>
                <div className="flex items-center justify-between">
                    <p className="text-xs text-muted-foreground">Enter a GitHub repository URL (e.g., https://github.com/owner/repo)</p>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={handleUseDemoRepository}
                        disabled={disabled || isValidating}
                        className="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        Use Demo Repository
                    </Button>
                </div>
            </div>

            {/* Demo Repository Notice */}
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950">
                <div className="flex items-start space-x-2">
                    <div className="flex-shrink-0">
                        <svg className="h-4 w-4 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                        </svg>
                    </div>
                    <div className="flex-1">
                        <p className="text-xs text-amber-800 dark:text-amber-200">
                            <strong>For Evaluation:</strong> Click "Use Demo Repository" to quickly test with the WordPress Hello Dolly plugin repository during the August 2025 evaluation period.
                        </p>
                    </div>
                </div>
            </div>

            {error && (
                <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {validatedRepository && (
                <div className="rounded-md border border-green-200 bg-green-50 p-4">
                    <div className="flex items-start justify-between">
                        <div className="flex-1">
                            <div className="mb-2 flex items-center space-x-2">
                                <Github className="h-5 w-5 text-green-600" />
                                <h4 className="text-lg font-medium text-green-800">{validatedRepository.full_name}</h4>
                                {validatedRepository.private && (
                                    <span className="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                                        Private
                                    </span>
                                )}
                            </div>

                            {validatedRepository.description && <p className="mb-3 text-green-700">{validatedRepository.description}</p>}

                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div className="flex items-center space-x-2">
                                    <GitBranch className="h-4 w-4 text-green-600" />
                                    <span className="text-green-700">Default: {validatedRepository.default_branch}</span>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <FileText className="h-4 w-4 text-green-600" />
                                    <span className="text-green-700">Size: {formatFileSize(validatedRepository.size)}</span>
                                </div>

                                {validatedRepository.language && (
                                    <div className="flex items-center space-x-2">
                                        <div className="h-4 w-4 rounded-full bg-green-600"></div>
                                        <span className="text-green-700">{validatedRepository.language}</span>
                                    </div>
                                )}

                                <div className="flex items-center space-x-2">
                                    <Calendar className="h-4 w-4 text-green-600" />
                                    <span className="text-green-700">Updated: {formatDate(validatedRepository.updated_at)}</span>
                                </div>
                            </div>
                        </div>

                        <a
                            href={validatedRepository.html_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="ml-4 inline-flex items-center text-green-600 hover:text-green-800"
                        >
                            <ExternalLink className="h-4 w-4" />
                        </a>
                    </div>
                </div>
            )}

            {validatedRepository && (
                <div className="rounded-md border border-blue-200 bg-blue-50 p-4">
                    <div className="flex items-start space-x-3">
                        <div className="flex-shrink-0">
                            <svg className="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <div className="flex-1">
                            <h4 className="text-sm font-medium text-blue-800 mb-1">Repository Validated Successfully!</h4>
                            <p className="text-sm text-blue-700">
                                After selecting a branch, you can choose to either:
                            </p>
                            <ul className="mt-2 text-sm text-blue-700 list-disc list-inside space-y-1">
                                <li><strong>Select a specific file</strong> for targeted test generation</li>
                                <li><strong>Process the entire repository</strong> for comprehensive test coverage</li>
                            </ul>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
