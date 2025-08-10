import { SVGAttributes } from 'react';

interface AppLogoIconNewProps extends SVGAttributes<SVGElement> {
    variant?: 'default' | 'white' | 'dark';
}

export default function AppLogoIconNew({ 
    variant = 'default', 
    className = '', 
    ...props 
}: AppLogoIconNewProps) {
    const getColors = () => {
        switch (variant) {
            case 'white':
                return {
                    primary: '#FFFFFF',
                    secondary: '#E5E7EB',
                    accent: '#F59E0B'
                };
            case 'dark':
                return {
                    primary: '#000000',
                    secondary: '#374151',
                    accent: '#F59E0B'
                };
            default:
                return {
                    primary: 'currentColor',
                    secondary: 'currentColor',
                    accent: '#F59E0B'
                };
        }
    };

    const colors = getColors();

    return (
        <svg 
            viewBox="0 0 24 24" 
            fill="none" 
            xmlns="http://www.w3.org/2000/svg"
            className={className}
            {...props}
        >
            {/* Code brackets */}
            <path 
                d="M8 6L2 12L8 18" 
                stroke={colors.primary} 
                strokeWidth="2" 
                strokeLinecap="round" 
                strokeLinejoin="round"
            />
            <path 
                d="M16 6L22 12L16 18" 
                stroke={colors.primary} 
                strokeWidth="2" 
                strokeLinecap="round" 
                strokeLinejoin="round"
            />
            
            {/* Test checkmark in center */}
            <circle 
                cx="12" 
                cy="12" 
                r="3" 
                fill={colors.accent} 
                opacity="0.9"
            />
            <path 
                d="M10.5 12L11.5 13L13.5 11" 
                stroke="white" 
                strokeWidth="1.5" 
                strokeLinecap="round" 
                strokeLinejoin="round"
            />
            
            {/* AI spark elements */}
            <circle cx="6" cy="8" r="1" fill={colors.accent} opacity="0.6" />
            <circle cx="18" cy="8" r="1" fill={colors.accent} opacity="0.6" />
            <circle cx="6" cy="16" r="1" fill={colors.accent} opacity="0.4" />
            <circle cx="18" cy="16" r="1" fill={colors.accent} opacity="0.4" />
        </svg>
    );
}
