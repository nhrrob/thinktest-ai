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
            {/* Brain Icon */}
            <g transform="translate(10, 20)">
                {/* Brain outline */}
                <path 
                    d="M30 8C25 8 20 13 20 20C20 21 20.2 22 20.4 23C19 22.4 17.4 22 16 22C11.6 22 8 25.6 8 30C8 33 9.6 35.6 12 37C12 37.4 12 37.6 12 38C12 44.6 17.4 50 24 50C25 50 26 49.8 27 49.6C28.6 53 32 55 36 55C41 55 45 51 45 46C45 45 44.8 44 44.4 43C47.6 41.6 50 38.4 50 35C50 31 47 27.6 43 26.4C43.6 25 44 23.6 44 22C44 14.2 37.8 8 30 8Z" 
                    stroke={logoColor} 
                    strokeWidth="2" 
                    fill="none"
                />
                
                {/* Neural connections */}
                <circle cx="24" cy="24" r="2" fill={logoColor}/>
                <circle cx="40" cy="28" r="2" fill={logoColor}/>
                <circle cx="28" cy="36" r="2" fill={logoColor}/>
                <circle cx="36" cy="40" r="2" fill={logoColor}/>
                
                {/* Connection lines */}
                <line x1="24" y1="24" x2="40" y2="28" stroke={logoColor} strokeWidth="1" opacity="0.6"/>
                <line x1="24" y1="24" x2="28" y2="36" stroke={logoColor} strokeWidth="1" opacity="0.6"/>
                <line x1="40" y1="28" x2="36" y2="40" stroke={logoColor} strokeWidth="1" opacity="0.6"/>
                <line x1="28" y1="36" x2="36" y2="40" stroke={logoColor} strokeWidth="1" opacity="0.6"/>
                
                {/* AI spark */}
                <path d="M48 16L52 12L48 8L44 12L48 16Z" fill="#F59E0B" opacity="0.8"/>
                <path d="M52 20L56 16L52 12L48 16L52 20Z" fill="#F59E0B" opacity="0.6"/>
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
