import { useState, useRef } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

interface ThinkTestProps {
    recentConversations: any[];
    recentAnalyses: any[];
    availableProviders: string[];
}

export default function Index({ recentConversations, recentAnalyses, availableProviders }: ThinkTestProps) {
    const [isUploading, setIsUploading] = useState<boolean>(false);
    const [isGenerating, setIsGenerating] = useState<boolean>(false);
    const [uploadResult, setUploadResult] = useState<any>(null);
    const [generatedTests, setGeneratedTests] = useState<any>(null);
    const [currentConversationId, setCurrentConversationId] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        plugin_file: null,
        provider: 'openai',
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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const result = await response.json();

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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    conversation_id: currentConversationId,
                    provider: data.provider,
                    framework: data.framework,
                }),
            });

            const result = await response.json();

            if (result.success) {
                setGeneratedTests(result.tests);
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
        if (!currentConversationId) {
            alert('No tests available for download');
            return;
        }

        try {
            const response = await fetch(`/thinktest/download?conversation_id=${currentConversationId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

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
                const result = await response.json();
                alert('Download failed: ' + result.message);
            }
        } catch (error) {
            console.error('Download error:', error);
            alert('Download failed: ' + (error instanceof Error ? error.message : 'Unknown error'));
        }
    };

    const breadcrumbs = [
        { title: 'ThinkTest AI', href: '/thinktest' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="ThinkTest AI - WordPress Plugin Test Generator" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            
                            {/* Upload Section */}
                            <div className="mb-8">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">
                                    Upload WordPress Plugin
                                </h3>
                                
                                <form onSubmit={handleFileUpload} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Plugin File (.php or .zip)
                                        </label>
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            accept=".php,.zip"
                                            onChange={(e) => setData('plugin_file', e.target.files?.[0] || null)}
                                            className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                AI Provider
                                            </label>
                                            <select
                                                value={data.provider}
                                                onChange={(e) => setData('provider', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="openai">OpenAI (GPT-4)</option>
                                                <option value="anthropic">Anthropic (Claude)</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Test Framework
                                            </label>
                                            <select
                                                value={data.framework}
                                                onChange={(e) => setData('framework', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="phpunit">PHPUnit</option>
                                                <option value="pest">Pest</option>
                                            </select>
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={isUploading || !data.plugin_file}
                                        className="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                    >
                                        {isUploading ? 'Uploading...' : 'Upload & Analyze'}
                                    </button>
                                </form>
                            </div>

                            {/* Analysis Results */}
                            {uploadResult && (
                                <div className="mb-8 p-4 bg-green-50 border border-green-200 rounded-md">
                                    <h4 className="text-lg font-medium text-green-800 mb-2">
                                        Analysis Complete
                                    </h4>
                                    <p className="text-green-700 mb-4">
                                        Plugin analyzed successfully. Found {uploadResult.analysis.functions?.length || 0} functions, 
                                        {' '}{uploadResult.analysis.classes?.length || 0} classes, and 
                                        {' '}{uploadResult.analysis.wordpress_patterns?.length || 0} WordPress patterns.
                                    </p>
                                    
                                    <button
                                        onClick={handleGenerateTests}
                                        disabled={isGenerating}
                                        className="inline-flex justify-center rounded-md border border-transparent bg-green-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50"
                                    >
                                        {isGenerating ? 'Generating Tests...' : 'Generate Tests with AI'}
                                    </button>
                                </div>
                            )}

                            {/* Generated Tests */}
                            {generatedTests && (
                                <div className="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-md">
                                    <h4 className="text-lg font-medium text-blue-800 mb-2">
                                        Tests Generated Successfully
                                    </h4>
                                    <p className="text-blue-700 mb-4">
                                        AI has generated comprehensive tests for your WordPress plugin.
                                    </p>
                                    
                                    <div className="flex space-x-4">
                                        <button
                                            onClick={handleDownloadTests}
                                            className="inline-flex justify-center rounded-md border border-transparent bg-blue-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                        >
                                            Download Tests
                                        </button>
                                    </div>

                                    <details className="mt-4">
                                        <summary className="cursor-pointer text-blue-800 font-medium">
                                            Preview Generated Tests
                                        </summary>
                                        <pre className="mt-2 p-4 bg-gray-100 rounded text-sm overflow-x-auto">
                                            {generatedTests}
                                        </pre>
                                    </details>
                                </div>
                            )}

                            {/* Recent Activity */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 className="text-lg font-medium text-gray-900 mb-4">
                                        Recent Conversations
                                    </h4>
                                    {recentConversations?.length > 0 ? (
                                        <div className="space-y-2">
                                            {recentConversations.map((conversation) => (
                                                <div key={conversation.id} className="p-3 bg-gray-50 rounded">
                                                    <div className="text-sm font-medium">
                                                        {conversation.context?.filename || 'Unknown file'}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {conversation.status} • {conversation.provider}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">No recent conversations</p>
                                    )}
                                </div>

                                <div>
                                    <h4 className="text-lg font-medium text-gray-900 mb-4">
                                        Recent Analyses
                                    </h4>
                                    {recentAnalyses?.length > 0 ? (
                                        <div className="space-y-2">
                                            {recentAnalyses.map((analysis) => (
                                                <div key={analysis.id} className="p-3 bg-gray-50 rounded">
                                                    <div className="text-sm font-medium">
                                                        {analysis.filename}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        Complexity: {analysis.complexity_score || 'N/A'}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">No recent analyses</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
