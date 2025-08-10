import { cn } from '@/lib/utils';
import AppLogoIconNew from './app-logo-icon-new';

interface AppLogoProps {
    className?: string;
    variant?: 'sidebar' | 'auth' | 'header';
    showText?: boolean;
    iconSize?: 'sm' | 'md' | 'lg';
    colorScheme?: 'default' | 'white' | 'dark';
}

export default function AppLogo({
    className = '',
    variant = 'sidebar',
    showText = true,
    iconSize = 'md',
    colorScheme = 'default'
}: AppLogoProps) {
    const iconSizes = {
        sm: 'size-4',
        md: 'size-5',
        lg: 'size-6'
    };

    const containerSizes = {
        sm: 'size-6',
        md: 'size-8',
        lg: 'size-10'
    };

    const textSizes = {
        sm: 'text-xs',
        md: 'text-sm',
        lg: 'text-base'
    };

    if (variant === 'sidebar') {
        return (
            <>
                <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                    <AppLogoIconNew className="size-5 text-white dark:text-black" />
                </div>
                {showText && (
                    <div className="ml-1 grid flex-1 text-left text-sm">
                        <span className="mb-0.5 truncate leading-tight font-semibold">ThinkTest AI</span>
                    </div>
                )}
            </>
        );
    }

    // For auth and header variants, use a more flexible layout
    const getColorClasses = () => {
        switch (colorScheme) {
            case 'white':
                return {
                    container: "bg-white/20 text-white",
                    icon: "text-white",
                    text: "text-white"
                };
            case 'dark':
                return {
                    container: "bg-gray-900 text-white",
                    icon: "text-white",
                    text: "text-gray-900"
                };
            default:
                return {
                    container: "bg-primary text-primary-foreground",
                    icon: "text-white dark:text-black",
                    text: "text-foreground dark:text-foreground"
                };
        }
    };

    const colors = getColorClasses();

    return (
        <div className={cn("flex items-center gap-3", className)}>
            <div className={cn(
                "flex items-center justify-center rounded-md",
                colors.container,
                containerSizes[iconSize]
            )}>
                <AppLogoIconNew className={cn(colors.icon, iconSizes[iconSize])} />
            </div>
            {showText && (
                <span className={cn("font-semibold", colors.text, textSizes[iconSize])}>
                    ThinkTest AI
                </span>
            )}
        </div>
    );
}
