import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Eye, EyeOff, ExternalLink, Plus, Settings, Trash2 } from 'lucide-react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'API Tokens',
        href: '/settings/api-tokens',
    },
];

type ApiToken = {
    id: number;
    provider: string;
    provider_display_name: string;
    display_name: string;
    masked_token: string;
    is_active: boolean;
    last_used_at: string | null;
    created_at: string;
};

type Provider = {
    name: string;
    description: string;
    website: string;
};

type ProviderInstructions = {
    title: string;
    steps: string[];
    notes: string[];
};

type ApiTokenForm = {
    provider: string;
    token: string;
    display_name: string;
};

interface ApiTokensProps {
    tokens: ApiToken[];
    availableProviders: Record<string, Provider>;
    instructions: Record<string, ProviderInstructions>;
}

export default function ApiTokens({ tokens, availableProviders, instructions }: ApiTokensProps) {
    const [showAddForm, setShowAddForm] = useState(false);
    const [selectedProvider, setSelectedProvider] = useState<string>('');
    const [showToken, setShowToken] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm<ApiTokenForm>({
        provider: '',
        token: '',
        display_name: '',
    });

    const { delete: deleteToken } = useForm();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        
        post(route('api-tokens.store'), {
            onSuccess: () => {
                reset();
                setShowAddForm(false);
                setSelectedProvider('');
            },
        });
    };

    const handleDelete = (tokenId: number) => {
        if (confirm('Are you sure you want to delete this API token?')) {
            deleteToken(route('api-tokens.destroy', tokenId));
        }
    };

    const toggleToken = (tokenId: number) => {
        useForm().patch(route('api-tokens.toggle', tokenId));
    };

    const getProviderInstructions = (provider: string) => {
        return instructions[provider] || null;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="API Tokens" />

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <HeadingSmall 
                            title="API Token Management" 
                            description="Manage your AI provider API tokens for personalized access to OpenAI and Anthropic services" 
                        />
                        <Button onClick={() => setShowAddForm(!showAddForm)} className="flex items-center gap-2">
                            <Plus className="h-4 w-4" />
                            Add Token
                        </Button>
                    </div>

                    {/* Existing Tokens */}
                    {tokens.length > 0 && (
                        <div className="space-y-4">
                            <h3 className="text-lg font-medium">Your API Tokens</h3>
                            <div className="grid gap-4">
                                {tokens.map((token) => (
                                    <Card key={token.id}>
                                        <CardHeader className="pb-3">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <CardTitle className="text-base">{token.display_name}</CardTitle>
                                                    <Badge variant={token.is_active ? "default" : "secondary"}>
                                                        {token.is_active ? 'Active' : 'Inactive'}
                                                    </Badge>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Switch
                                                        checked={token.is_active}
                                                        onCheckedChange={() => toggleToken(token.id)}
                                                    />
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleDelete(token.id)}
                                                        className="text-red-600 hover:text-red-700"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                            <CardDescription>
                                                {token.provider_display_name} • Created {token.created_at}
                                                {token.last_used_at && ` • Last used ${token.last_used_at}`}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="font-mono text-sm bg-gray-50 dark:bg-gray-800 p-2 rounded">
                                                {token.masked_token}
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Add New Token Form */}
                    {showAddForm && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Add New API Token</CardTitle>
                                <CardDescription>
                                    Add your personal API token to access AI services with your own account and usage limits.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submit} className="space-y-4">
                                    <div>
                                        <Label htmlFor="provider">AI Provider</Label>
                                        <Select
                                            value={data.provider}
                                            onValueChange={(value) => {
                                                setData('provider', value);
                                                setSelectedProvider(value);
                                            }}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select an AI provider" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {Object.entries(availableProviders).map(([key, provider]) => (
                                                    <SelectItem key={key} value={key}>
                                                        {provider.name} - {provider.description}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.provider} />
                                    </div>

                                    <div>
                                        <Label htmlFor="display_name">Display Name (Optional)</Label>
                                        <Input
                                            id="display_name"
                                            type="text"
                                            value={data.display_name}
                                            onChange={(e) => setData('display_name', e.target.value)}
                                            placeholder="e.g., My OpenAI Key"
                                        />
                                        <InputError message={errors.display_name} />
                                    </div>

                                    <div>
                                        <Label htmlFor="token">API Token</Label>
                                        <div className="relative">
                                            <Input
                                                id="token"
                                                type={showToken ? "text" : "password"}
                                                value={data.token}
                                                onChange={(e) => setData('token', e.target.value)}
                                                placeholder="Paste your API token here"
                                                className="pr-10"
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="absolute right-0 top-0 h-full px-3"
                                                onClick={() => setShowToken(!showToken)}
                                            >
                                                {showToken ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                            </Button>
                                        </div>
                                        <InputError message={errors.token} />
                                    </div>

                                    <div className="flex gap-2">
                                        <Button type="submit" disabled={processing}>
                                            Add Token
                                        </Button>
                                        <Button 
                                            type="button" 
                                            variant="outline" 
                                            onClick={() => {
                                                setShowAddForm(false);
                                                reset();
                                                setSelectedProvider('');
                                            }}
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    {/* Instructions */}
                    {selectedProvider && getProviderInstructions(selectedProvider) && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Settings className="h-5 w-5" />
                                    {getProviderInstructions(selectedProvider)!.title}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <h4 className="font-medium mb-2">Steps:</h4>
                                    <ol className="list-decimal list-inside space-y-1 text-sm">
                                        {getProviderInstructions(selectedProvider)!.steps.map((step, index) => (
                                            <li key={index} dangerouslySetInnerHTML={{ __html: step }} />
                                        ))}
                                    </ol>
                                </div>
                                
                                <div>
                                    <h4 className="font-medium mb-2">Important Notes:</h4>
                                    <ul className="list-disc list-inside space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                        {getProviderInstructions(selectedProvider)!.notes.map((note, index) => (
                                            <li key={index}>{note}</li>
                                        ))}
                                    </ul>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
