import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                bucha: {
                    /** Official logo palette */
                    primary: '#A11D1E',
                    charcoal: '#3C3C3B',
                    burgundy: '#7a1516',
                    sidebar: '#2d2d2c',
                    canvas: '#F7FAFC',
                    card: '#FFFFFF',
                    success: '#38A169',
                    warning: '#D69E2E',
                    muted: '#718096',
                },
            },
            boxShadow: {
                bucha: '0 1px 3px 0 rgb(0 0 0 / 0.06), 0 1px 2px -1px rgb(0 0 0 / 0.06)',
                'bucha-md': '0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.05)',
            },
            borderRadius: {
                bucha: '12px',
            },
        },
    },

    plugins: [forms],
};
