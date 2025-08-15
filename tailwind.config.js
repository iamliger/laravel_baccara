const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Http/Livewire/**/*.php',
        './resources/views/livewire/**/*.blade.php',
    ],

    safelist: [
        'bg-blue-600',
        'bg-red-600',
        'bg-green-600',
        'bg-yellow-400',
        'bg-indigo-600',
        'text-yellow-400',
        'text-blue-400',
        'text-red-400',
        'text-green-400',
        'text-gray-400',
        'text-white',
        {
            pattern: /grid-(cols|rows)-.+/, // grid-cols-40, grid-rows-6 같은 동적 클래스
        },
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            backgroundImage: theme => ({
                'grid-gray-700/50': `linear-gradient(rgba(55, 65, 81, 0.5) 1px, transparent 1px), linear-gradient(to right, rgba(55, 65, 81, 0.5) 1px, transparent 1px)`,
            }),
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
