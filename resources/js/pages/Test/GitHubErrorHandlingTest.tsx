import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import GitHubFileBrowser from '@/components/github/GitHubFileBrowser';
import GitHubFileSelector from '@/components/github/GitHubFileSelector';
import GitHubDebugPanel from '@/components/github/GitHubDebugPanel';
import { useToast } from '@/hooks/use-toast';
import { 
    TestTube, 
    AlertTriangle, 
    CheckCircle, 
    XCircle,
    Clock,
    Bug
} from 'lucide-react';

interface Repository {
    owner: string;
    repo: string;
    full_name: string;
    default_branch: string;
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

interface TestScenario {
    id: string;
    name: string;
    description: string;
    repository: Repository;
    branch: string;
    expectedBehavior: string;
    status: 'pending' | 'running' | 'passed' | 'failed';
    error?: string;
}

export default function GitHubErrorHandlingTest({ auth }: { auth: any }) {
    const [customRepo, setCustomRepo] = useState({
        owner: '',
        repo: '',
        branch: 'main'
    });
    
    const [selectedFile, setSelectedFile] = useState<FileItem | null>(null);
    const [fileContent, setFileContent] = useState<FileContent | null>(null);
    const [testResults, setTestResults] = useState<Record<string, any>>({});
    
    const { error: showError, success: showSuccess, info: showInfo } = useToast();

    // Test scenarios to validate error handling
    const testScenarios: TestScenario[] = [
        {
            id: 'valid-repo',
            name: 'Valid Repository',
            description: 'Test with a known public repository',
            repository: {
                owner: 'octocat',
                repo: 'Hello-World',
                full_name: 'octocat/Hello-World',
                default_branch: 'master'
            },
            branch: 'master',
            expectedBehavior: 'Should load files successfully with proper logging',
            status: 'pending'
        },
        {
            id: 'invalid-repo',
            name: 'Invalid Repository',
            description: 'Test with a non-existent repository',
            repository: {
                owner: 'nonexistent-user-12345',
                repo: 'nonexistent-repo-12345',
                full_name: 'nonexistent-user-12345/nonexistent-repo-12345',
                default_branch: 'main'
            },
            branch: 'main',
            expectedBehavior: 'Should show proper 404 error message with actionable guidance',
            status: 'pending'
        },
        {
            id: 'invalid-branch',
            name: 'Invalid Branch',
            description: 'Test with valid repo but invalid branch',
            repository: {
                owner: 'octocat',
                repo: 'Hello-World',
                full_name: 'octocat/Hello-World',
                default_branch: 'master'
            },
            branch: 'nonexistent-branch-12345',
            expectedBehavior: 'Should show branch-specific error message',
            status: 'pending'
        },
        {
            id: 'empty-repo',
            name: 'Empty Repository',
            description: 'Test with an empty repository',
            repository: {
                owner: 'octocat',
                repo: 'test-repo1',
                full_name: 'octocat/test-repo1',
                default_branch: 'main'
            },
            branch: 'main',
            expectedBehavior: 'Should show empty repository message with helpful guidance',
            status: 'pending'
        }
    ];

    const [scenarios, setScenarios] = useState<TestScenario[]>(testScenarios);
    const [currentRepository, setCurrentRepository] = useState<Repository>(testScenarios[0].repository);
    const [currentBranch, setCurrentBranch] = useState<string>(testScenarios[0].branch);

    const handleFileSelected = (file: FileItem) => {
        console.log('Test: File selected', file);
        setSelectedFile(file);
        showInfo(`File selected: ${file.name}`);
    };

    const handleFileContentLoaded = (content: FileContent) => {
        console.log('Test: File content loaded', content);
        setFileContent(content);
        showSuccess(`File content loaded: ${content.name} (${content.content.length} characters)`);
    };

    const handleError = (error: string) => {
        console.log('Test: Error occurred', error);
        showError(error);
    };

    const runTestScenario = (scenario: TestScenario) => {
        console.log(`Test: Running scenario "${scenario.name}"`);
        setCurrentRepository(scenario.repository);
        setCurrentBranch(scenario.branch);
        setSelectedFile(null);
        setFileContent(null);
        
        // Update scenario status
        setScenarios(prev => prev.map(s => 
            s.id === scenario.id 
                ? { ...s, status: 'running' }
                : s
        ));
        
        showInfo(`Running test: ${scenario.name}`);
    };

    const loadCustomRepository = () => {
        if (!customRepo.owner || !customRepo.repo) {
            showError('Please enter both owner and repository name');
            return;
        }

        const repository: Repository = {
            owner: customRepo.owner,
            repo: customRepo.repo,
            full_name: `${customRepo.owner}/${customRepo.repo}`,
            default_branch: customRepo.branch || 'main'
        };

        console.log('Test: Loading custom repository', repository);
        setCurrentRepository(repository);
        setCurrentBranch(customRepo.branch || 'main');
        setSelectedFile(null);
        setFileContent(null);
        
        showInfo(`Loading custom repository: ${repository.full_name}`);
    };

    const getStatusIcon = (status: TestScenario['status']) => {
        switch (status) {
            case 'running':
                return <Clock className="h-4 w-4 text-blue-500 animate-pulse" />;
            case 'passed':
                return <CheckCircle className="h-4 w-4 text-green-500" />;
            case 'failed':
                return <XCircle className="h-4 w-4 text-red-500" />;
            default:
                return <TestTube className="h-4 w-4 text-gray-400" />;
        }
    };

    const getStatusBadge = (status: TestScenario['status']) => {
        const variants = {
            pending: 'secondary',
            running: 'default',
            passed: 'default',
            failed: 'destructive'
        } as const;

        const colors = {
            pending: 'bg-gray-100 text-gray-800',
            running: 'bg-blue-100 text-blue-800',
            passed: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800'
        };

        return (
            <Badge className={colors[status]}>
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </Badge>
        );
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">GitHub Error Handling Test</h2>}
        >
            <Head title="GitHub Error Handling Test" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Test Scenarios */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <TestTube className="h-5 w-5" />
                                Test Scenarios
                            </CardTitle>
                            <CardDescription>
                                Predefined test cases to validate error handling and logging improvements
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4">
                                {scenarios.map((scenario) => (
                                    <div key={scenario.id} className="flex items-center justify-between p-4 border rounded-lg">
                                        <div className="flex items-center gap-3">
                                            {getStatusIcon(scenario.status)}
                                            <div>
                                                <h4 className="font-medium">{scenario.name}</h4>
                                                <p className="text-sm text-gray-600">{scenario.description}</p>
                                                <p className="text-xs text-gray-500 mt-1">
                                                    Expected: {scenario.expectedBehavior}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {getStatusBadge(scenario.status)}
                                            <Button
                                                size="sm"
                                                onClick={() => runTestScenario(scenario)}
                                                disabled={scenario.status === 'running'}
                                            >
                                                Run Test
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Custom Repository Test */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Custom Repository Test</CardTitle>
                            <CardDescription>
                                Test with your own repository to validate error handling
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="owner">Repository Owner</Label>
                                    <Input
                                        id="owner"
                                        placeholder="e.g., octocat"
                                        value={customRepo.owner}
                                        onChange={(e) => setCustomRepo(prev => ({ ...prev, owner: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="repo">Repository Name</Label>
                                    <Input
                                        id="repo"
                                        placeholder="e.g., Hello-World"
                                        value={customRepo.repo}
                                        onChange={(e) => setCustomRepo(prev => ({ ...prev, repo: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="branch">Branch</Label>
                                    <Input
                                        id="branch"
                                        placeholder="e.g., main"
                                        value={customRepo.branch}
                                        onChange={(e) => setCustomRepo(prev => ({ ...prev, branch: e.target.value }))}
                                    />
                                </div>
                            </div>
                            <Button onClick={loadCustomRepository}>
                                Load Custom Repository
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Debug Panel */}
                    <GitHubDebugPanel
                        repository={currentRepository}
                        branch={currentBranch}
                        onDebugComplete={(debugInfo) => {
                            console.log('Debug completed:', debugInfo);
                            setTestResults(prev => ({
                                ...prev,
                                [currentRepository.full_name]: debugInfo
                            }));
                        }}
                    />

                    {/* File Browser and Selector */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>File Browser</CardTitle>
                                <CardDescription>
                                    Current: {currentRepository.full_name} ({currentBranch})
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <GitHubFileBrowser
                                    repository={currentRepository}
                                    branch={currentBranch}
                                    onFileSelected={handleFileSelected}
                                    onError={handleError}
                                    selectedFilePath={selectedFile?.path}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>File Selector</CardTitle>
                                <CardDescription>
                                    Selected file details and content loading
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <GitHubFileSelector
                                    repository={currentRepository}
                                    branch={currentBranch}
                                    selectedFile={selectedFile}
                                    onFileContentLoaded={handleFileContentLoaded}
                                    onError={handleError}
                                    onGenerateTests={() => showInfo('Generate tests clicked')}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    {/* Instructions */}
                    <Card className="border-blue-200 bg-blue-50">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-blue-800">
                                <AlertTriangle className="h-5 w-5" />
                                Testing Instructions
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-blue-700 space-y-2">
                            <p>1. <strong>Run test scenarios</strong> to validate different error conditions</p>
                            <p>2. <strong>Check browser console</strong> for detailed logging output</p>
                            <p>3. <strong>Observe toast notifications</strong> for user-friendly error messages</p>
                            <p>4. <strong>Use debug panel</strong> to investigate API responses and data structures</p>
                            <p>5. <strong>Test custom repositories</strong> to validate real-world scenarios</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
