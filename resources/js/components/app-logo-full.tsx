import { SVGAttributes } from 'react';

interface AppLogoFullProps extends SVGAttributes<SVGElement> {
    variant?: 'default' | 'white' | 'dark';
    showText?: boolean;
}

export default function AppLogoFull({ 
    variant = 'default', 
    showText = true, 
    className = '', 
    ...props 
}: AppLogoFullProps) {
    const logoColors = {
        default: '#FF2D20',
        white: '#FFFFFF',
        dark: '#000000'
    };

    const textColor = variant === 'white' ? '#FFFFFF' : '#000000';
    const logoColor = logoColors[variant];

    return (
        <svg 
            viewBox="0 0 400 100" 
            fill="none" 
            xmlns="http://www.w3.org/2000/svg"
            className={className}
            {...props}
        >
            {/* Code/Testing Icon */}
            <g transform="translate(10, 20)">
                {/* Code brackets */}
                <path
                    d="M20 15L8 30L20 45"
                    stroke={logoColor}
                    strokeWidth="4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    fill="none"
                />
                <path
                    d="M40 15L52 30L40 45"
                    stroke={logoColor}
                    strokeWidth="4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    fill="none"
                />

                {/* Test checkmark in center */}
                <circle
                    cx="30"
                    cy="30"
                    r="8"
                    fill="#F59E0B"
                    opacity="0.9"
                />
                <path
                    d="M26 30L29 33L34 28"
                    stroke="white"
                    strokeWidth="3"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    fill="none"
                />

                {/* AI spark elements */}
                <circle cx="15" cy="20" r="2" fill="#F59E0B" opacity="0.6" />
                <circle cx="45" cy="20" r="2" fill="#F59E0B" opacity="0.6" />
                <circle cx="15" cy="40" r="2" fill="#F59E0B" opacity="0.4" />
                <circle cx="45" cy="40" r="2" fill="#F59E0B" opacity="0.4" />
            </g>
            
            {/* Text */}
            {showText && (
                <g transform="translate(80, 30)">
                    <text 
                        x="0" 
                        y="25" 
                        fontSize="24" 
                        fontWeight="600" 
                        fill={textColor}
                        fontFamily="system-ui, -apple-system, sans-serif"
                    >
                        ThinkTest AI
                    </text>
                </g>
            )}
        </svg>
    );
}
