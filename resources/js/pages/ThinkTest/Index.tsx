import GitHubBranchSelector from '@/components/github/GitHubBranchSelector';
import GitHubFileBrowser from '@/components/github/GitHubFileBrowser';
import GitHubFileSelector from '@/components/github/GitHubFileSelector';
import GitHubRepositoryInput from '@/components/github/GitHubRepositoryInput';
import SourceToggle, { SourceType } from '@/components/github/SourceToggle';
import TestSetupWizard from '@/components/TestSetupWizard';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

interface Conversation {
    id: number;
    title: string;
    created_at: string;
    updated_at: string;
}

interface Analysis {
    id: number;
    file_name: string;
    analysis_type: string;
    created_at: string;
    updated_at: string;
}

interface UploadResult {
    success: boolean;
    message: string;
    data?: unknown;
}

interface GeneratedTests {
    tests: string;
    conversation_id: string;
}

interface Repository {
    owner: string;
    repo: string;
    url: string;
}

interface Branch {
    name: string;
    commit: {
        sha: string;
        url: string;
    };
}

interface FileItem {
    name: string;
    path: string;
    type: 'file' | 'dir';
    size: number;
    sha: string;
    url: string;
    html_url: string;
    download_url?: string;
}

interface FileContent {
    name: string;
    path: string;
    content: string;
    size: number;
    sha: string;
    encoding: string;
    url: string;
    html_url: string;
    download_url: string;
}

type GitHubProcessingMode = 'repository' | 'single-file';

interface TestInfrastructureDetection {
    has_phpunit_config: boolean;
    has_pest_config: boolean;
    has_composer_json: boolean;
    has_test_directory: boolean;
    has_test_dependencies: boolean;
    missing_components: string[];
    recommendations: Array<{
        type: string;
        title: string;
        description: string;
        action: string;
    }>;
    setup_priority: string;
}

interface TestSetupInstructions {
    framework: string;
    plugin_name: string;
    difficulty: string;
    estimated_time: string;
    prerequisites: Array<{
        title: string;
        description: string;
        check_command?: string;
        install_url?: string;
        options?: string[];
    }>;
    steps: Array<{
        number: number;
        title: string;
        description: string;
        commands?: string[];
        explanation: string;
        files_created: string[];
    }>;
    files_to_create: Array<{
        name: string;
        description: string;
        template: string;
    }>;
    commands: Array<{
        title: string;
        command: string;
        description: string;
    }>;
    troubleshooting: Array<{
        issue: string;
        solution: string;
        commands?: string[];
    }>;
}

interface ThinkTestProps {
    recentConversations: Conversation[];
    recentAnalyses: Analysis[];
    availableProviders: string[];
}

// Helper function to handle API responses with proper error checking
const handleApiResponse = async (response: Response): Promise<unknown> => {
    console.log('API Response received:', {
        status: response.status,
        statusText: response.statusText,
        redirected: response.redirected,
        url: response.url,
        headers: Object.fromEntries(response.headers.entries()),
    });

    // Check if response is redirected (authentication issue)
    if (response.redirected || response.status === 302) {
        console.error('Authentication redirect detected:', {
            status: response.status,
            redirected: response.redirected,
            url: response.url,
        });
        alert('Authentication required. Please refresh the page and log in again.');
        window.location.reload();
        return;
    }

    // Check if response is not ok
    if (!response.ok) {
        if (response.status === 419) {
            console.error('CSRF token mismatch detected');
            alert('Session expired. Please refresh the page and try again.');
            window.location.reload();
            return;
        }
        if (response.status === 401) {
            console.error('Unauthorized access detected');
            alert('Authentication required. Please refresh the page and log in again.');
            window.location.reload();
            return;
        }
        if (response.status === 403) {
            console.error('Forbidden access detected');
            alert('You do not have permission to perform this action. Please contact an administrator.');
            return;
        }

        // Try to get error details from response
        let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
        try {
            const errorData = await response.json();
            if (errorData.message) {
                errorMessage = errorData.message;
            }
        } catch {
            // If we can't parse JSON, use the default error message
        }

        throw new Error(errorMessage);
    }

    // Check if response is JSON
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
        const text = await response.text();
        console.error('Non-JSON response received:', text);
        throw new Error('Server returned an invalid response. Please try again.');
    }

    return await response.json();
};

// Helper function to get fresh CSRF token
const getCsrfToken = (): string => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
        console.error('CSRF token not found in meta tag');
        throw new Error('CSRF token not available. Please refresh the page.');
    }
    return token;
};

// Helper function to validate authentication status
const validateAuthentication = async (): Promise<boolean> => {
    try {
        console.log('Validating authentication status...');
        const response = await fetch('/auth/check', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin', // Ensure cookies are sent
        });

        console.log('Authentication check response:', {
            status: response.status,
            statusText: response.statusText,
            redirected: response.redirected,
            url: response.url,
        });

        if (response.ok) {
            const data = await response.json();
            console.log('Authentication data received:', {
                authenticated: data.authenticated,
                user: data.user ? { id: data.user.id, name: data.user.name } : null,
                csrf_token_present: !!data.csrf_token,
            });

            if (data.authenticated) {
                // Update CSRF token if provided
                if (data.csrf_token) {
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    if (metaTag) {
                        metaTag.setAttribute('content', data.csrf_token);
                        console.log('CSRF token updated successfully');
                    } else {
                        console.warn('CSRF meta tag not found, cannot update token');
                    }
                }
                return true;
            } else {
                console.error('User is not authenticated according to server response');
                return false;
            }
        }

        console.error('Authentication validation failed:', {
            status: response.status,
            statusText: response.statusText,
        });
        return false;
    } catch (error) {
        console.error('Authentication check error:', error);
        return false;
    }
};

export default function Index({ recentConversations, recentAnalyses }: ThinkTestProps) {
    const [sourceType, setSourceType] = useState<SourceType>('github');
    const [isUploading, setIsUploading] = useState<boolean>(false);
    const [isGenerating, setIsGenerating] = useState<boolean>(false);
    const [uploadResult, setUploadResult] = useState<UploadResult | null>(null);
    const [generatedTests, setGeneratedTests] = useState<GeneratedTests | null>(null);
    const [currentConversationId, setCurrentConversationId] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    // GitHub-related state
    const [validatedRepository, setValidatedRepository] = useState<Repository | null>(null);
    const [selectedBranch, setSelectedBranch] = useState<Branch | null>(null);
    const [isProcessingRepository, setIsProcessingRepository] = useState<boolean>(false);
    const [processingProgress, setProcessingProgress] = useState<string>('');

    // File selection state
    const [githubProcessingMode, setGithubProcessingMode] = useState<GitHubProcessingMode>('repository');
    const [selectedFile, setSelectedFile] = useState<FileItem | null>(null);
    const [fileContent, setFileContent] = useState<FileContent | null>(null);
    const [isGeneratingSingleFile, setIsGeneratingSingleFile] = useState<boolean>(false);

    // Test setup wizard state
    const [showTestSetupWizard, setShowTestSetupWizard] = useState<boolean>(false);
    const [testInfrastructureDetection, setTestInfrastructureDetection] = useState<TestInfrastructureDetection | null>(null);
    const [testSetupInstructions, setTestSetupInstructions] = useState<TestSetupInstructions | null>(null);

    const { data, setData } = useForm<{
        plugin_file: File | null;
        provider: string;
        framework: string;
    }>({
        plugin_file: null,
        provider: 'openai-gpt5',
        framework: 'phpunit',
    });

    const handleFileUpload = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.plugin_file) {
            alert('Please select a file to upload');
            return;
        }

        setIsUploading(true);
        setUploadResult(null);

        try {
            const formData = new FormData();
            formData.append('plugin_file', data.plugin_file);
            formData.append('provider', data.provider);
            formData.append('framework', data.framework);

            const response = await fetch('/thinktest/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });

            const result = await handleApiResponse(response);
            if (!result) return; // Handle redirect cases

            if (result.success) {
                setUploadResult(result);
                setCurrentConversationId(result.conversation_id);
            } else {
                alert('Upload failed: ' + result.message);
            }
        } catch (error) {
            console.error('Upload error:', error);
            alert('Upload failed: ' + (error instanceof Error ? error.message : 'Unknown error'));
        } finally {
            setIsUploading(false);
        }
    };

    const handleGenerateTests = async () => {
        if (!currentConversationId) {
            alert('No active conversation found');
            return;
        }

        setIsGenerating(true);
        setGeneratedTests(null);

        try {
            const response = await fetch('/thinktest/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    conversation_id: currentConversationId,
                    provider: data.provider,
                    framework: data.framework,
                }),
            });

            const result = await handleApiResponse(response);
            if (!result) return; // Handle redirect cases

            if (result.success) {
                setGeneratedTests({
                    tests: result.tests,
                    conversation_id: result.conversation_id,
                });
                setCurrentConversationId(result.conversation_id);
            } else {
                alert('Test generation failed: ' + result.message);
            }
        } catch (error) {
            console.error('Generation error:', error);
            alert('Test generation failed: ' + (error instanceof Error ? error.message : 'Unknown error'));
        } finally {
            setIsGenerating(false);
        }
    };

    const handleDownloadTests = async () => {
        if (!generatedTests?.conversation_id) {
            alert('No tests available for download');
            return;
        }

        try {
            const response = await fetch(`/thinktest/download?conversation_id=${generatedTests.conversation_id}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            // Check for authentication/session issues
            if (response.redirected || response.status === 302) {
                alert('Authentication required. Please refresh the page and log in again.');
                window.location.reload();
                return;
            }

            if (response.status === 419) {
                alert('Session expired. Please refresh the page and try again.');
                window.location.reload();
                return;
            }

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `thinktest_generated_tests.php`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                const result = await handleApiResponse(response);
                alert('Download failed: ' + result.message);
            }
        } catch (error) {
            console.error('Download error:', error);
            alert('Download failed: ' + (error instanceof Error ? error.message : 'Unknown error'));
        }
    };

    const handleRepositoryValidated = (repository: Repository) => {
        setValidatedRepository(repository);
        setSelectedBranch(null);
        setUploadResult(null);
        setGeneratedTests(null);
        setCurrentConversationId(null);
    };

    const handleBranchSelected = (branch: Branch) => {
        setSelectedBranch(branch);
    };

    const handleProcessRepository = async () => {
        if (!validatedRepository || !selectedBranch) {
            alert('Please select a repository and branch');
            return;
        }

        setIsProcessingRepository(true);
        setUploadResult(null);
        setProcessingProgress('Initializing...');

        try {
            // Step 1: Validate authentication
            setProcessingProgress('Validating authentication...');
            const isAuthenticated = await validateAuthentication();
            if (!isAuthenticated) {
                alert('Authentication required. Please refresh the page and log in again.');
                window.location.reload();
                return;
            }

            // Step 2: Prepare request
            setProcessingProgress('Preparing repository processing request...');
            const csrfToken = getCsrfToken();
            console.log('Making GitHub repository process request with CSRF token:', csrfToken.substring(0, 10) + '...');

            const requestData = {
                owner: validatedRepository.owner,
                repo: validatedRepository.repo,
                branch: selectedBranch.name,
                provider: data.provider,
                framework: data.framework,
            };

            console.log('Repository processing request data:', requestData);

            // Step 3: Make the request
            setProcessingProgress('Connecting to GitHub API...');
            const response = await fetch('/thinktest/github/process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(requestData),
            });

            // Step 4: Process response
            setProcessingProgress('Processing repository data...');
            const result = await handleApiResponse(response);
            if (!result) return; // Handle redirect cases

            if (result.success) {
                setProcessingProgress('Analysis complete!');
                setUploadResult(result);
                setCurrentConversationId(result.conversation_id);
                console.log('Repository processing successful:', {
                    conversation_id: result.conversation_id,
                    analysis_id: result.analysis_id,
                    file_count: result.repository?.file_count,
                    processing_time_ms: result.processing_time_ms,
                });
            } else {
                console.error('Repository processing failed:', result);

                // Provide more specific error messages based on error code
                let errorMessage = result.message || 'Repository processing failed';
                if (result.error_code) {
                    switch (result.error_code) {
                        case 'AUTH_REQUIRED':
                            errorMessage = 'Authentication required. Please refresh the page and log in again.';
                            window.location.reload();
                            return;
                        case 'VALIDATION_ERROR':
                            errorMessage = `Validation error: ${result.message}`;
                            break;
                        case 'REDIRECT_ERROR':
                        case 'HTML_RESPONSE_ERROR':
                        case 'AUTH_ERROR':
                            errorMessage = `${result.message}\n\nThis usually indicates a session or authentication issue. Please try refreshing the page and logging in again.`;
                            break;
                        default:
                            errorMessage = result.message;
                    }
                }

                alert(errorMessage);
            }
        } catch (error) {
            console.error('Repository processing error:', error);
            setProcessingProgress('Error occurred during processing');

            // Provide more specific error messages
            let errorMessage = 'Repository processing failed: ';
            if (error instanceof Error) {
                // Check for specific error patterns
                if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                    errorMessage = 'Network error occurred. Please check your internet connection and try again.';
                } else if (error.message.includes('Authentication') || error.message.includes('401')) {
                    errorMessage = 'Authentication error. Please refresh the page and log in again.';
                } else if (error.message.includes('Session expired') || error.message.includes('419')) {
                    errorMessage = 'Session expired. Please refresh the page and try again.';
                } else {
                    errorMessage += error.message;
                }
            } else {
                errorMessage += 'Unknown error occurred. Please try again or contact support if the problem persists.';
            }

            alert(errorMessage);
        } finally {
            setIsProcessingRepository(false);
            // Clear progress after a short delay
            setTimeout(() => setProcessingProgress(''), 2000);
        }
    };

    const handleSourceChange = (source: SourceType) => {
        setSourceType(source);
        // Reset state when switching sources
        setUploadResult(null);
        setGeneratedTests(null);
        setCurrentConversationId(null);
        setValidatedRepository(null);
        setSelectedBranch(null);
        setShowTestSetupWizard(false);
        setTestInfrastructureDetection(null);
        setTestSetupInstructions(null);
        // Reset file selection state
        setGithubProcessingMode('repository');
        setSelectedFile(null);
        setFileContent(null);
        setIsGeneratingSingleFile(false);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
        setData('plugin_file', null);
    };

    const handleProcessingModeChange = (mode: GitHubProcessingMode) => {
        setGithubProcessingMode(mode);
        // Reset file selection when switching modes
        setSelectedFile(null);
        setFileContent(null);
        setGeneratedTests(null);
        setCurrentConversationId(null);
    };

    const handleFileSelected = (file: FileItem) => {
        setSelectedFile(file);
        setFileContent(null);
    };

    const handleFileContentLoaded = (content: FileContent) => {
        setFileContent(content);
    };

    const handleGenerateTestsForSingleFile = async () => {
        if (!validatedRepository || !selectedBranch || !selectedFile || !fileContent) {
            alert('Please select a repository, branch, and file');
            return;
        }

        setIsGeneratingSingleFile(true);
        setGeneratedTests(null);

        try {
            const response = await fetch('/thinktest/generate-single-file', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    owner: validatedRepository.owner,
                    repo: validatedRepository.repo,
                    file_path: selectedFile.path,
                    branch: selectedBranch.name,
                    provider: data.provider,
                    framework: data.framework,
                }),
            });

            const result = await handleApiResponse(response);
            if (!result) return; // Handle redirect cases

            if (result.success) {
                setGeneratedTests({
                    tests: result.tests,
                    conversation_id: result.conversation_id,
                });
                setCurrentConversationId(result.conversation_id);
            } else {
                alert('Single-file test generation failed: ' + result.message);
            }
        } catch (error) {
            console.error('Single-file generation error:', error);
            alert('Single-file test generation failed: ' + (error instanceof Error ? error.message : 'Unknown error'));
        } finally {
            setIsGeneratingSingleFile(false);
        }
    };

    const handleDetectTestInfrastructure = async () => {
        if (!currentConversationId) {
            alert('Please upload a plugin file first');
            return;
        }

        try {
            const response = await fetch('/thinktest/detect-infrastructure', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    conversation_id: currentConversationId,
                    framework: data.framework,
                }),
            });

            const result = await handleApiResponse(response);
            if (!result) return; // Handle redirect cases

            if (result.success) {
                setTestInfrastructureDetection(result.detection);
                setTestSetupInstructions(result.instructions);
                setShowTestSetupWizard(true);
            } else {
                alert('Detection failed: ' + result.message);
            }
        } catch (error) {
            console.error('Test infrastructure detection error:', error);
            alert('Detection failed: ' + (error instanceof Error ? error.message : 'Unknown error'));
        }
    };

    const handleDownloadTemplate = async (template: string, filename: string) => {
        try {
            const response = await fetch('/thinktest/download-template', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    template: template,
                    framework: data.framework,
                    plugin_name: uploadResult?.plugin_name || 'WordPress Plugin',
                }),
            });

            // Check for authentication/session issues
            if (response.redirected || response.status === 302) {
                alert('Authentication required. Please refresh the page and log in again.');
                window.location.reload();
                return;
            }

            if (response.status === 419) {
                alert('Session expired. Please refresh the page and try again.');
                window.location.reload();
                return;
            }

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                const result = await handleApiResponse(response);
                alert('Download failed: ' + result.message);
            }
        } catch (error) {
            console.error('Template download error:', error);
            alert('Download failed: ' + (error instanceof Error ? error.message : 'Unknown error'));
        }
    };

    const handleError = (error: string) => {
        console.error('Error:', error);
        // You could also show a toast notification here
    };

    const breadcrumbs = [{ title: 'ThinkTest AI', href: '/thinktest' }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="ThinkTest AI - WordPress Plugin Test Generator" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-background shadow-lg sm:rounded-lg border">
                        <div className="p-6 text-foreground">
                            {/* Source Selection */}
                            <div className="mb-8">
                                <SourceToggle
                                    selectedSource={sourceType}
                                    onSourceChange={handleSourceChange}
                                    disabled={isUploading || isProcessingRepository || isGenerating}
                                />
                            </div>

                            {/* File Upload Section */}
                            {sourceType === 'file' && (
                                <div className="mb-8">
                                    <h3 className="mb-4 text-lg font-medium text-foreground">Upload WordPress Plugin</h3>

                                    <form onSubmit={handleFileUpload} className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-muted-foreground">Plugin File (.php or .zip)</label>
                                            <input
                                                ref={fileInputRef}
                                                type="file"
                                                accept=".php,.zip"
                                                onChange={(e) => setData('plugin_file', e.target.files?.[0] || null)}
                                                className="mt-1 block w-full text-sm text-muted-foreground file:mr-4 file:rounded-full file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20"
                                            />
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-muted-foreground">AI Provider</label>
                                                <select
                                                    value={data.provider}
                                                    onChange={(e) => setData('provider', e.target.value)}
                                                    className="mt-1 block w-full rounded-md border border-input bg-background p-1 shadow-sm focus:border-ring focus:ring-ring"
                                                >
                                                    <option value="openai-gpt5">OpenAI GPT-5</option>
                                                    <option value="anthropic-claude">Anthropic Claude 3.5 Sonnet</option>
                                                    {/* Legacy support - will be removed in future version */}
                                                    <option value="chatgpt-5">ChatGPT-5 (Legacy)</option>
                                                    <option value="anthropic">Anthropic (Claude) (Legacy)</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-muted-foreground">Test Framework</label>
                                                <select
                                                    value={data.framework}
                                                    onChange={(e) => setData('framework', e.target.value)}
                                                    className="mt-1 block w-full rounded-md border border-input bg-background p-1 shadow-sm focus:border-ring focus:ring-ring"
                                                >
                                                    <option value="phpunit">PHPUnit</option>
                                                    <option value="pest">Pest</option>
                                                </select>
                                            </div>
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={isUploading || !data.plugin_file}
                                            className="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                                        >
                                            {isUploading ? 'Uploading...' : 'Upload & Analyze'}
                                        </button>
                                    </form>
                                </div>
                            )}

                            {/* GitHub Repository Section */}
                            {sourceType === 'github' && (
                                <div className="mb-8 space-y-6">
                                    <h3 className="text-lg font-medium text-foreground">GitHub Repository</h3>

                                    <GitHubRepositoryInput
                                        onRepositoryValidated={handleRepositoryValidated}
                                        onError={handleError}
                                        disabled={isProcessingRepository || isGenerating}
                                    />

                                    {validatedRepository && (
                                        <GitHubBranchSelector
                                            repository={validatedRepository}
                                            onBranchSelected={handleBranchSelected}
                                            onError={handleError}
                                            disabled={isProcessingRepository || isGenerating}
                                        />
                                    )}

                                    {validatedRepository && selectedBranch && (
                                        <div className="space-y-4">
                                            <div>
                                                <label className="block text-sm font-medium text-muted-foreground mb-2">Processing Mode</label>
                                                <div className="flex space-x-4">
                                                    <label className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            value="repository"
                                                            checked={githubProcessingMode === 'repository'}
                                                            onChange={(e) => handleProcessingModeChange(e.target.value as GitHubProcessingMode)}
                                                            disabled={isProcessingRepository || isGenerating || isGeneratingSingleFile}
                                                            className="mr-2"
                                                        />
                                                        <span className="text-sm">Full Repository</span>
                                                    </label>
                                                    <label className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            value="single-file"
                                                            checked={githubProcessingMode === 'single-file'}
                                                            onChange={(e) => handleProcessingModeChange(e.target.value as GitHubProcessingMode)}
                                                            disabled={isProcessingRepository || isGenerating || isGeneratingSingleFile}
                                                            className="mr-2"
                                                        />
                                                        <span className="text-sm">Single File</span>
                                                    </label>
                                                </div>
                                            </div>

                                            {githubProcessingMode === 'single-file' && (
                                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                                    <GitHubFileBrowser
                                                        repository={validatedRepository}
                                                        branch={selectedBranch.name}
                                                        onFileSelected={handleFileSelected}
                                                        onError={handleError}
                                                        disabled={isGeneratingSingleFile}
                                                        selectedFilePath={selectedFile?.path}
                                                    />
                                                    <GitHubFileSelector
                                                        repository={validatedRepository}
                                                        branch={selectedBranch.name}
                                                        selectedFile={selectedFile}
                                                        onFileContentLoaded={handleFileContentLoaded}
                                                        onError={handleError}
                                                        onGenerateTests={handleGenerateTestsForSingleFile}
                                                        disabled={isGeneratingSingleFile}
                                                        isGenerating={isGeneratingSingleFile}
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {validatedRepository && selectedBranch && githubProcessingMode === 'repository' && (
                                        <div className="space-y-4">
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label className="block text-sm font-medium text-muted-foreground">AI Provider</label>
                                                    <select
                                                        value={data.provider}
                                                        onChange={(e) => setData('provider', e.target.value)}
                                                        disabled={isProcessingRepository || isGenerating}
                                                        className="mt-1 block w-full rounded-md border border-input bg-background p-1 shadow-sm focus:border-ring focus:ring-ring disabled:opacity-50"
                                                    >
                                                        <option value="openai-gpt5">OpenAI GPT-5</option>
                                                        <option value="anthropic-claude">Anthropic Claude 3.5 Sonnet</option>
                                                        {/* Legacy support - will be removed in future version */}
                                                        <option value="chatgpt-5">ChatGPT-5 (Legacy)</option>
                                                        <option value="anthropic">Anthropic (Claude) (Legacy)</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label className="block text-sm font-medium text-muted-foreground">Test Framework</label>
                                                    <select
                                                        value={data.framework}
                                                        onChange={(e) => setData('framework', e.target.value)}
                                                        disabled={isProcessingRepository || isGenerating}
                                                        className="mt-1 block w-full rounded-md border border-input bg-background p-1 shadow-sm focus:border-ring focus:ring-ring disabled:opacity-50"
                                                    >
                                                        <option value="phpunit">PHPUnit</option>
                                                        <option value="pest">Pest</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <button
                                                onClick={handleProcessRepository}
                                                disabled={isProcessingRepository || isGenerating}
                                                className="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                                            >
                                                {isProcessingRepository ? (
                                                    <div className="flex items-center">
                                                        <svg
                                                            className="mr-3 -ml-1 h-5 w-5 animate-spin text-white"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <circle
                                                                className="opacity-25"
                                                                cx="12"
                                                                cy="12"
                                                                r="10"
                                                                stroke="currentColor"
                                                                strokeWidth="4"
                                                            ></circle>
                                                            <path
                                                                className="opacity-75"
                                                                fill="currentColor"
                                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                            ></path>
                                                        </svg>
                                                        Processing...
                                                    </div>
                                                ) : (
                                                    'Process Repository & Analyze'
                                                )}
                                            </button>

                                            {/* Processing Progress Indicator */}
                                            {isProcessingRepository && processingProgress && (
                                                <div className="mt-4 rounded-md border border-blue-200 bg-blue-50 p-3">
                                                    <div className="flex items-center">
                                                        <svg
                                                            className="mr-3 -ml-1 h-4 w-4 animate-spin text-blue-600"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <circle
                                                                className="opacity-25"
                                                                cx="12"
                                                                cy="12"
                                                                r="10"
                                                                stroke="currentColor"
                                                                strokeWidth="4"
                                                            ></circle>
                                                            <path
                                                                className="opacity-75"
                                                                fill="currentColor"
                                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                            ></path>
                                                        </svg>
                                                        <div>
                                                            <p className="text-sm font-medium text-blue-800">{processingProgress}</p>
                                                            <p className="mt-1 text-xs text-blue-600">
                                                                This may take a few moments depending on repository size...
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Analysis Results */}
                            {uploadResult && (
                                <div className="mb-8 rounded-md border border-green-200 bg-green-50 p-4">
                                    <h4 className="mb-2 text-lg font-medium text-green-800">Analysis Complete</h4>
                                    <p className="mb-4 text-green-700">
                                        Plugin analyzed successfully. Found {uploadResult.analysis.functions?.length || 0} functions,{' '}
                                        {uploadResult.analysis.classes?.length || 0} classes, and{' '}
                                        {uploadResult.analysis.wordpress_patterns?.length || 0} WordPress patterns.
                                    </p>

                                    <div className="flex space-x-4">
                                        <button
                                            onClick={handleDetectTestInfrastructure}
                                            className="inline-flex justify-center rounded-md border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-medium text-orange-700 shadow-sm hover:bg-orange-100 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:outline-none"
                                        >
                                            🔧 Setup Test Environment
                                        </button>

                                        <button
                                            onClick={handleGenerateTests}
                                            disabled={isGenerating}
                                            className="inline-flex justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                                        >
                                            {isGenerating ? 'Generating Tests...' : 'Generate Tests with AI'}
                                        </button>
                                    </div>
                                </div>
                            )}

                            {/* Generated Tests */}
                            {generatedTests && (
                                <div className="mb-8 rounded-md border border-blue-200 bg-blue-50 p-4">
                                    <h4 className="mb-2 text-lg font-medium text-blue-800">Tests Generated Successfully</h4>
                                    <p className="mb-4 text-blue-700">AI has generated comprehensive tests for your WordPress plugin.</p>

                                    <div className="flex space-x-4">
                                        <button
                                            onClick={handleDownloadTests}
                                            className="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                                        >
                                            Download Tests
                                        </button>
                                    </div>

                                    <details className="mt-4">
                                        <summary className="cursor-pointer font-medium text-blue-800">Preview Generated Tests</summary>
                                        <pre className="mt-2 overflow-x-auto rounded bg-muted p-4 text-sm">{generatedTests.tests}</pre>
                                    </details>
                                </div>
                            )}

                            {/* Recent Activity */}
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <h4 className="mb-4 text-lg font-medium text-foreground">Recent Conversations</h4>
                                    {recentConversations?.length > 0 ? (
                                        <div className="space-y-2">
                                            {recentConversations.map((conversation) => (
                                                <div key={conversation.id} className="rounded bg-muted p-3">
                                                    <div className="text-sm font-medium">{conversation.context?.filename || 'Unknown file'}</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {conversation.status} • {conversation.provider}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-muted-foreground">No recent conversations</p>
                                    )}
                                </div>

                                <div>
                                    <h4 className="mb-4 text-lg font-medium text-foreground">Recent Analyses</h4>
                                    {recentAnalyses?.length > 0 ? (
                                        <div className="space-y-2">
                                            {recentAnalyses.map((analysis) => (
                                                <div key={analysis.id} className="rounded bg-muted p-3">
                                                    <div className="text-sm font-medium">{analysis.filename}</div>
                                                    <div className="text-xs text-muted-foreground">Complexity: {analysis.complexity_score || 'N/A'}</div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-muted-foreground">No recent analyses</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Test Setup Wizard Modal */}
            {showTestSetupWizard && testInfrastructureDetection && testSetupInstructions && (
                <TestSetupWizard
                    detection={testInfrastructureDetection}
                    instructions={testSetupInstructions}
                    onDownloadTemplate={handleDownloadTemplate}
                    onClose={() => setShowTestSetupWizard(false)}
                />
            )}
        </AppLayout>
    );
}
