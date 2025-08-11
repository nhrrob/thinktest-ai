import React, { useState } from 'react';
import { CheckCircleIcon, ExclamationTriangleIcon, DocumentArrowDownIcon, ClipboardDocumentIcon } from '@heroicons/react/24/outline';

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
        setCompletedSteps(prev => 
            prev.includes(stepNumber) 
                ? prev.filter(n => n !== stepNumber)
                : [...prev, stepNumber]
        );
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
    };

    const getPriorityColor = (priority: string) => {
        switch (priority) {
            case 'high': return 'text-red-600 bg-red-50';
            case 'medium': return 'text-yellow-600 bg-yellow-50';
            case 'low': return 'text-green-600 bg-green-50';
            default: return 'text-gray-600 bg-gray-50';
        }
    };

    const getDifficultyColor = (difficulty: string) => {
        switch (difficulty) {
            case 'beginner': return 'text-blue-600 bg-blue-50';
            case 'intermediate': return 'text-yellow-600 bg-yellow-50';
            case 'advanced': return 'text-green-600 bg-green-50';
            default: return 'text-gray-600 bg-gray-50';
        }
    };

    return (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div className="relative top-20 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
                {/* Header */}
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h3 className="text-2xl font-bold text-gray-900">Test Environment Setup Wizard</h3>
                        <p className="text-gray-600 mt-1">Get your WordPress plugin ready for testing</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                    >
                        ×
                    </button>
                </div>

                {/* Status Overview */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div className={`p-4 rounded-lg ${getPriorityColor(detection.setup_priority)}`}>
                        <div className="flex items-center">
                            <ExclamationTriangleIcon className="h-5 w-5 mr-2" />
                            <span className="font-medium">Priority: {detection.setup_priority.toUpperCase()}</span>
                        </div>
                        <p className="text-sm mt-1">{detection.missing_components.length} components missing</p>
                    </div>
                    
                    <div className={`p-4 rounded-lg ${getDifficultyColor(instructions.difficulty)}`}>
                        <div className="flex items-center">
                            <span className="font-medium">Difficulty: {instructions.difficulty}</span>
                        </div>
                        <p className="text-sm mt-1">Estimated time: {instructions.estimated_time}</p>
                    </div>
                    
                    <div className="p-4 rounded-lg bg-indigo-50 text-indigo-600">
                        <div className="flex items-center">
                            <span className="font-medium">Framework: {instructions.framework.toUpperCase()}</span>
                        </div>
                        <p className="text-sm mt-1">Plugin: {instructions.plugin_name}</p>
                    </div>
                </div>

                {/* Navigation Tabs */}
                <div className="border-b border-gray-200 mb-6">
                    <nav className="-mb-px flex space-x-8">
                        {[
                            { id: 'overview', label: 'Overview' },
                            { id: 'steps', label: 'Setup Steps' },
                            { id: 'files', label: 'Configuration Files' },
                            { id: 'troubleshooting', label: 'Troubleshooting' }
                        ].map((tab) => (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id as any)}
                                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                    activeTab === tab.id
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
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
                                <h4 className="text-lg font-medium text-gray-900 mb-3">Missing Components</h4>
                                <div className="space-y-3">
                                    {detection.recommendations.map((rec, index) => (
                                        <div key={index} className="border-l-4 border-red-400 bg-red-50 p-4">
                                            <div className="flex">
                                                <ExclamationTriangleIcon className="h-5 w-5 text-red-400 mr-3 mt-0.5" />
                                                <div>
                                                    <h5 className="text-red-800 font-medium">{rec.title}</h5>
                                                    <p className="text-red-700 text-sm mt-1">{rec.description}</p>
                                                    <p className="text-red-600 text-sm font-medium mt-2">Action: {rec.action}</p>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Prerequisites */}
                            <div>
                                <h4 className="text-lg font-medium text-gray-900 mb-3">Prerequisites</h4>
                                <div className="space-y-3">
                                    {instructions.prerequisites.map((prereq, index) => (
                                        <div key={index} className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                            <h5 className="font-medium text-blue-900">{prereq.title}</h5>
                                            <p className="text-blue-800 text-sm mt-1">{prereq.description}</p>
                                            {prereq.check_command && (
                                                <div className="mt-2">
                                                    <code className="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">
                                                        {prereq.check_command}
                                                    </code>
                                                </div>
                                            )}
                                            {prereq.install_url && (
                                                <a 
                                                    href={prereq.install_url} 
                                                    target="_blank" 
                                                    rel="noopener noreferrer"
                                                    className="text-blue-600 hover:text-blue-800 text-sm mt-2 inline-block"
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
                                <div key={step.number} className="border border-gray-200 rounded-lg p-4">
                                    <div className="flex items-start">
                                        <button
                                            onClick={() => toggleStepCompletion(step.number)}
                                            className={`mr-3 mt-1 ${
                                                completedSteps.includes(step.number)
                                                    ? 'text-green-600'
                                                    : 'text-gray-400 hover:text-gray-600'
                                            }`}
                                        >
                                            <CheckCircleIcon className="h-6 w-6" />
                                        </button>
                                        <div className="flex-1">
                                            <h5 className="font-medium text-gray-900">
                                                Step {step.number}: {step.title}
                                            </h5>
                                            <p className="text-gray-600 text-sm mt-1">{step.description}</p>
                                            
                                            {step.commands && (
                                                <div className="mt-3">
                                                    <p className="text-sm font-medium text-gray-700 mb-2">Commands:</p>
                                                    {step.commands.map((command, cmdIndex) => (
                                                        <div key={cmdIndex} className="flex items-center bg-gray-100 rounded p-2 mb-1">
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
                                            
                                            <p className="text-gray-600 text-sm mt-2 italic">{step.explanation}</p>
                                            
                                            {step.files_created.length > 0 && (
                                                <div className="mt-2">
                                                    <p className="text-sm font-medium text-gray-700">Files created:</p>
                                                    <div className="flex flex-wrap gap-1 mt-1">
                                                        {step.files_created.map((file, fileIndex) => (
                                                            <span key={fileIndex} className="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
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
                                <div key={index} className="border border-gray-200 rounded-lg p-4">
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <h5 className="font-medium text-gray-900">{file.name}</h5>
                                            <p className="text-gray-600 text-sm mt-1">{file.description}</p>
                                        </div>
                                        <button
                                            onClick={() => onDownloadTemplate(file.template, file.name)}
                                            className="bg-indigo-600 text-white px-3 py-1 rounded text-sm hover:bg-indigo-700 flex items-center"
                                        >
                                            <DocumentArrowDownIcon className="h-4 w-4 mr-1" />
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
                                <div key={index} className="border border-gray-200 rounded-lg p-4">
                                    <h5 className="font-medium text-gray-900">{item.issue}</h5>
                                    <p className="text-gray-700 mt-1">{item.solution}</p>
                                    <p className="text-gray-600 text-sm mt-2">{item.details}</p>
                                </div>
                            ))}
                            
                            <div className="mt-6">
                                <h5 className="font-medium text-gray-900 mb-3">Next Steps</h5>
                                <ul className="list-disc list-inside space-y-1 text-gray-600">
                                    {instructions.next_steps.map((step, index) => (
                                        <li key={index} className="text-sm">{step}</li>
                                    ))}
                                </ul>
                            </div>

                            <div className="mt-6">
                                <h5 className="font-medium text-gray-900 mb-3">Helpful Resources</h5>
                                <div className="space-y-2">
                                    {instructions.resources.map((resource, index) => (
                                        <a
                                            key={index}
                                            href={resource.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="block p-3 border border-gray-200 rounded hover:bg-gray-50"
                                        >
                                            <h6 className="font-medium text-indigo-600">{resource.title}</h6>
                                            <p className="text-gray-600 text-sm">{resource.description}</p>
                                        </a>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="mt-6 flex justify-between items-center pt-4 border-t border-gray-200">
                    <div className="text-sm text-gray-600">
                        Progress: {completedSteps.length} of {instructions.steps.length} steps completed
                    </div>
                    <div className="space-x-3">
                        <button
                            onClick={onClose}
                            className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                        >
                            Close
                        </button>
                        <button
                            onClick={() => {
                                // Download all templates as a ZIP file
                                instructions.files_to_create.forEach(file => {
                                    onDownloadTemplate(file.template, file.name);
                                });
                            }}
                            className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                        >
                            Download All Templates
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
