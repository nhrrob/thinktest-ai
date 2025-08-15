import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Github, Loader2 } from 'lucide-react';
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
    const [error, setError] = useState<string | null>(null);

    const handleValidateRepository = async () => {
        if (!repositoryUrl.trim()) {
            setError('Please enter a repository URL');
            return;
        }

        setIsValidating(true);
        setError(null);

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
                alert('Session expired. Please refresh the page and try again.');
                window.location.reload();
                return;
            }

            // Handle authentication errors (401) by reloading the page
            if (response.status === 401) {
                alert('Authentication required. Please refresh the page and log in again.');
                window.location.reload();
                return;
            }

            const result = await response.json();

            if (result.success) {
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
    };

    const handleUseDemoRepository = () => {
        const demoUrl = 'https://github.com/nhrrob/nhrrob-core-contributions';
        setRepositoryUrl(demoUrl);
        setError(null);
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
                        variant="ghost"
                        size="sm"
                        onClick={handleUseDemoRepository}
                        disabled={disabled || isValidating}
                        className="text-xs h-auto py-1 px-2 text-muted-foreground hover:text-foreground"
                    >
                        Use Demo Repository
                    </Button>
                </div>
            </div>



            {error && (
                <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}


        </div>
    );
}
