import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                page: 'var(--bg-page)',
                surface: {
                    DEFAULT: 'var(--surface-card)',
                    nested: 'var(--surface-nested)',
                    hover: 'var(--surface-hover)',
                },
                hairline: 'var(--border-hairline)',
                console: {
                    active: 'var(--nav-active)',
                    hover: 'var(--nav-hover)',
                    card: 'var(--surface-card)',
                    nested: 'var(--surface-nested)',
                    border: 'var(--border-hairline)',
                },
                tx: {
                    primary: 'var(--text-primary)',
                    secondary: 'var(--text-secondary)',
                    muted: 'var(--text-muted)',
                },
                accent: {
                    DEFAULT: '#0093dd',
                    hover: '#0082c4',
                    active: '#006fa7',
                    muted: '#0a3d5e',
                    pink: '#0093dd',
                },
                'accent-pink': '#0093dd',
            },
            borderColor: {
                hairline: 'var(--border-hairline)',
            },
            borderRadius: {
                card: '14px',
            },
        },
    },

    plugins: [forms],
};

