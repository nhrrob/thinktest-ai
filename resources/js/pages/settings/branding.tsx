import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import AppLogo from '@/components/app-logo';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Branding settings',
        href: '/settings/branding',
    },
];

interface Logo {
    id: string;
    name: string;
    description: string;
    preview: string;
}

interface CurrentLogo {
    type: 'default' | 'custom' | 'uploaded';
    path?: string;
    url?: string;
}

interface BrandingProps {
    currentLogo: CurrentLogo;
    availableLogos: Logo[];
}

export default function Branding({ currentLogo, availableLogos }: BrandingProps) {
    const [selectedLogoType, setSelectedLogoType] = useState(currentLogo.type);
    const [selectedCustomLogo, setSelectedCustomLogo] = useState(currentLogo.path || '');
    
    const { data, setData, post, processing, errors, reset } = useForm({
        logo_type: currentLogo.type,
        logo_file: null as File | null,
        custom_logo_id: currentLogo.path || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('logo_type', selectedLogoType);
        
        if (selectedLogoType === 'uploaded' && data.logo_file) {
            formData.append('logo_file', data.logo_file);
        } else if (selectedLogoType === 'custom') {
            formData.append('custom_logo_id', selectedCustomLogo);
        }

        post(route('branding.update'), {
            data: formData,
            onSuccess: () => {
                reset();
            },
        });
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('logo_file', file);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Branding settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall 
                        title="Branding settings" 
                        description="Customize your application's logo and branding" 
                    />

                    <form onSubmit={submit} className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Application Logo</CardTitle>
                                <CardDescription>
                                    Choose how your application logo appears throughout the interface
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Current Logo Display */}
                                <div className="space-y-4">
                                    <Label className="text-sm font-medium">Current Logo</Label>
                                    <div className="p-6 border rounded-lg bg-muted/50 flex items-center justify-center">
                                        <AppLogo variant="auth" iconSize="lg" showText={true} />
                                    </div>
                                </div>

                                <Separator />

                                {/* Logo Options */}
                                <div className="space-y-4">
                                    <Label className="text-sm font-medium">Logo Options</Label>

                                    <div className="grid gap-4">
                                        {/* Default Logo */}
                                        <div
                                            className={`p-4 border rounded-lg cursor-pointer transition-colors ${
                                                selectedLogoType === 'default'
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-border hover:border-primary/50'
                                            }`}
                                            onClick={() => {
                                                setSelectedLogoType('default');
                                                setData('logo_type', 'default');
                                            }}
                                        >
                                            <div className="flex items-start space-x-3">
                                                <div className="flex-1">
                                                    <h4 className="text-sm font-medium">ThinkTest AI Code Logo</h4>
                                                    <p className="text-sm text-muted-foreground">
                                                        Code/testing themed logo with brackets and checkmark
                                                    </p>
                                                </div>
                                                {selectedLogoType === 'default' && (
                                                    <div className="w-4 h-4 rounded-full bg-primary flex items-center justify-center">
                                                        <div className="w-2 h-2 rounded-full bg-white"></div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        {/* Upload Custom Logo */}
                                        <div
                                            className={`p-4 border rounded-lg cursor-pointer transition-colors ${
                                                selectedLogoType === 'uploaded'
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-border hover:border-primary/50'
                                            }`}
                                            onClick={() => {
                                                setSelectedLogoType('uploaded');
                                                setData('logo_type', 'uploaded');
                                            }}
                                        >
                                            <div className="flex items-start space-x-3">
                                                <div className="flex-1">
                                                    <h4 className="text-sm font-medium">Upload Custom Logo</h4>
                                                    <p className="text-sm text-muted-foreground">
                                                        Upload your own logo file (PNG, JPG, or SVG, max 2MB)
                                                    </p>

                                                    {selectedLogoType === 'uploaded' && (
                                                        <div className="mt-3 space-y-2">
                                                            <Input
                                                                type="file"
                                                                accept=".png,.jpg,.jpeg,.svg"
                                                                onChange={handleFileChange}
                                                                className="file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-primary-foreground hover:file:bg-primary/90"
                                                            />
                                                            <InputError message={errors.logo_file} />

                                                            {currentLogo.type === 'uploaded' && currentLogo.url && (
                                                                <div className="p-3 border rounded bg-background">
                                                                    <p className="text-xs text-muted-foreground mb-2">Current uploaded logo:</p>
                                                                    <img
                                                                        src={currentLogo.url}
                                                                        alt="Current logo"
                                                                        className="h-8 w-auto"
                                                                    />
                                                                </div>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                                {selectedLogoType === 'uploaded' && (
                                                    <div className="w-4 h-4 rounded-full bg-primary flex items-center justify-center">
                                                        <div className="w-2 h-2 rounded-full bg-white"></div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <InputError message={errors.logo_type} />
                            </CardContent>
                        </Card>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
