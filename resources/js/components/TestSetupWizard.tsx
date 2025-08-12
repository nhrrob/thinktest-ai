import { CheckCircleIcon, ClipboardDocumentIcon, DocumentArrowDownIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';

interface TestSetupWizardProps {
    detection: {
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
    };
    instructions: {
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
            details: string;
        }>;
        next_steps: string[];
        resources: Array<{
            title: string;
            url: string;
            description: string;
        }>;
    };
    onDownloadTemplate: (template: string, filename: string) => void;
    onClose: () => void;
}

export default function TestSetupWizard({ detection, instructions, onDownloadTemplate, onClose }: TestSetupWizardProps) {
    const [activeTab, setActiveTab] = useState<'overview' | 'steps' | 'files' | 'troubleshooting'>('overview');
    const [completedSteps, setCompletedSteps] = useState<number[]>([]);

    const toggleStepCompletion = (stepNumber: number) => {
        setCompletedSteps((prev) => (prev.includes(stepNumber) ? prev.filter((n) => n !== stepNumber) : [...prev, stepNumber]));
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
    };

    const getPriorityColor = (priority: string) => {
        switch (priority) {
            case 'high':
                return 'text-red-600 bg-red-50';
            case 'medium':
                return 'text-yellow-600 bg-yellow-50';
            case 'low':
                return 'text-green-600 bg-green-50';
            default:
                return 'text-gray-600 bg-gray-50';
        }
    };

    const getDifficultyColor = (difficulty: string) => {
        switch (difficulty) {
            case 'beginner':
                return 'text-blue-600 bg-blue-50';
            case 'intermediate':
                return 'text-yellow-600 bg-yellow-50';
            case 'advanced':
                return 'text-green-600 bg-green-50';
            default:
                return 'text-gray-600 bg-gray-50';
        }
    };

    return (
        <div className="bg-opacity-50 fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600">
            <div className="relative top-20 mx-auto w-11/12 max-w-6xl rounded-md border bg-white p-5 shadow-lg">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h3 className="text-2xl font-bold text-gray-900">Test Environment Setup Wizard</h3>
                        <p className="mt-1 text-gray-600">Get your WordPress plugin ready for testing</p>
                    </div>
                    <button onClick={onClose} className="text-2xl font-bold text-gray-400 hover:text-gray-600">
                        ×
                    </button>
                </div>

                {/* Status Overview */}
                <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div className={`rounded-lg p-4 ${getPriorityColor(detection.setup_priority)}`}>
                        <div className="flex items-center">
                            <ExclamationTriangleIcon className="mr-2 h-5 w-5" />
                            <span className="font-medium">Priority: {detection.setup_priority.toUpperCase()}</span>
                        </div>
                        <p className="mt-1 text-sm">{detection.missing_components.length} components missing</p>
                    </div>

                    <div className={`rounded-lg p-4 ${getDifficultyColor(instructions.difficulty)}`}>
                        <div className="flex items-center">
                            <span className="font-medium">Difficulty: {instructions.difficulty}</span>
                        </div>
                        <p className="mt-1 text-sm">Estimated time: {instructions.estimated_time}</p>
                    </div>

                    <div className="rounded-lg bg-indigo-50 p-4 text-indigo-600">
                        <div className="flex items-center">
                            <span className="font-medium">Framework: {instructions.framework.toUpperCase()}</span>
                        </div>
                        <p className="mt-1 text-sm">Plugin: {instructions.plugin_name}</p>
                    </div>
                </div>

                {/* Navigation Tabs */}
                <div className="mb-6 border-b border-gray-200">
                    <nav className="-mb-px flex space-x-8">
                        {[
                            { id: 'overview', label: 'Overview' },
                            { id: 'steps', label: 'Setup Steps' },
                            { id: 'files', label: 'Configuration Files' },
                            { id: 'troubleshooting', label: 'Troubleshooting' },
                        ].map((tab) => (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id as 'overview' | 'steps' | 'files' | 'troubleshooting')}
                                className={`border-b-2 px-1 py-2 text-sm font-medium ${
                                    activeTab === tab.id
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                                }`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </nav>
                </div>

                {/* Tab Content */}
                <div className="max-h-96 overflow-y-auto">
                    {activeTab === 'overview' && (
                        <div className="space-y-6">
                            {/* Missing Components */}
                            <div>
                                <h4 className="mb-3 text-lg font-medium text-gray-900">Missing Components</h4>
                                <div className="space-y-3">
                                    {detection.recommendations.map((rec, index) => (
                                        <div key={index} className="border-l-4 border-red-400 bg-red-50 p-4">
                                            <div className="flex">
                                                <ExclamationTriangleIcon className="mt-0.5 mr-3 h-5 w-5 text-red-400" />
                                                <div>
                                                    <h5 className="font-medium text-red-800">{rec.title}</h5>
                                                    <p className="mt-1 text-sm text-red-700">{rec.description}</p>
                                                    <p className="mt-2 text-sm font-medium text-red-600">Action: {rec.action}</p>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Prerequisites */}
                            <div>
                                <h4 className="mb-3 text-lg font-medium text-gray-900">Prerequisites</h4>
                                <div className="space-y-3">
                                    {instructions.prerequisites.map((prereq, index) => (
                                        <div key={index} className="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                            <h5 className="font-medium text-blue-900">{prereq.title}</h5>
                                            <p className="mt-1 text-sm text-blue-800">{prereq.description}</p>
                                            {prereq.check_command && (
                                                <div className="mt-2">
                                                    <code className="rounded bg-blue-100 px-2 py-1 text-sm text-blue-800">
                                                        {prereq.check_command}
                                                    </code>
                                                </div>
                                            )}
                                            {prereq.install_url && (
                                                <a
                                                    href={prereq.install_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="mt-2 inline-block text-sm text-blue-600 hover:text-blue-800"
                                                >
                                                    Download & Install →
                                                </a>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'steps' && (
                        <div className="space-y-4">
                            {instructions.steps.map((step) => (
                                <div key={step.number} className="rounded-lg border border-gray-200 p-4">
                                    <div className="flex items-start">
                                        <button
                                            onClick={() => toggleStepCompletion(step.number)}
                                            className={`mt-1 mr-3 ${
                                                completedSteps.includes(step.number) ? 'text-green-600' : 'text-gray-400 hover:text-gray-600'
                                            }`}
                                        >
                                            <CheckCircleIcon className="h-6 w-6" />
                                        </button>
                                        <div className="flex-1">
                                            <h5 className="font-medium text-gray-900">
                                                Step {step.number}: {step.title}
                                            </h5>
                                            <p className="mt-1 text-sm text-gray-600">{step.description}</p>

                                            {step.commands && (
                                                <div className="mt-3">
                                                    <p className="mb-2 text-sm font-medium text-gray-700">Commands:</p>
                                                    {step.commands.map((command, cmdIndex) => (
                                                        <div key={cmdIndex} className="mb-1 flex items-center rounded bg-gray-100 p-2">
                                                            <code className="flex-1 text-sm">{command}</code>
                                                            <button
                                                                onClick={() => copyToClipboard(command)}
                                                                className="ml-2 text-gray-500 hover:text-gray-700"
                                                            >
                                                                <ClipboardDocumentIcon className="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}

                                            <p className="mt-2 text-sm text-gray-600 italic">{step.explanation}</p>

                                            {step.files_created.length > 0 && (
                                                <div className="mt-2">
                                                    <p className="text-sm font-medium text-gray-700">Files created:</p>
                                                    <div className="mt-1 flex flex-wrap gap-1">
                                                        {step.files_created.map((file, fileIndex) => (
                                                            <span key={fileIndex} className="rounded bg-green-100 px-2 py-1 text-xs text-green-800">
                                                                {file}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {activeTab === 'files' && (
                        <div className="space-y-4">
                            <p className="text-gray-600">Download these configuration files to get started quickly:</p>
                            {instructions.files_to_create.map((file, index) => (
                                <div key={index} className="rounded-lg border border-gray-200 p-4">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <h5 className="font-medium text-gray-900">{file.name}</h5>
                                            <p className="mt-1 text-sm text-gray-600">{file.description}</p>
                                        </div>
                                        <button
                                            onClick={() => onDownloadTemplate(file.template, file.name)}
                                            className="flex items-center rounded bg-indigo-600 px-3 py-1 text-sm text-white hover:bg-indigo-700"
                                        >
                                            <DocumentArrowDownIcon className="mr-1 h-4 w-4" />
                                            Download
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {activeTab === 'troubleshooting' && (
                        <div className="space-y-4">
                            <h4 className="text-lg font-medium text-gray-900">Common Issues & Solutions</h4>
                            {instructions.troubleshooting.map((item, index) => (
                                <div key={index} className="rounded-lg border border-gray-200 p-4">
                                    <h5 className="font-medium text-gray-900">{item.issue}</h5>
                                    <p className="mt-1 text-gray-700">{item.solution}</p>
                                    <p className="mt-2 text-sm text-gray-600">{item.details}</p>
                                </div>
                            ))}

                            <div className="mt-6">
                                <h5 className="mb-3 font-medium text-gray-900">Next Steps</h5>
                                <ul className="list-inside list-disc space-y-1 text-gray-600">
                                    {instructions.next_steps.map((step, index) => (
                                        <li key={index} className="text-sm">
                                            {step}
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div className="mt-6">
                                <h5 className="mb-3 font-medium text-gray-900">Helpful Resources</h5>
                                <div className="space-y-2">
                                    {instructions.resources.map((resource, index) => (
                                        <a
                                            key={index}
                                            href={resource.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="block rounded border border-gray-200 p-3 hover:bg-gray-50"
                                        >
                                            <h6 className="font-medium text-indigo-600">{resource.title}</h6>
                                            <p className="text-sm text-gray-600">{resource.description}</p>
                                        </a>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
                    <div className="text-sm text-gray-600">
                        Progress: {completedSteps.length} of {instructions.steps.length} steps completed
                    </div>
                    <div className="space-x-3">
                        <button onClick={onClose} className="rounded-md border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">
                            Close
                        </button>
                        <button
                            onClick={() => {
                                // Download all templates as a ZIP file
                                instructions.files_to_create.forEach((file) => {
                                    onDownloadTemplate(file.template, file.name);
                                });
                            }}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                        >
                            Download All Templates
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
