/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.php',
        './includes/**/*.php',
        './assets/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                'athena-primary': '#4f46e5',
                'athena-secondary': '#0ea5e9',
                'athena-accent': '#10b981',
                'athena-light': '#f3f4f6',
                'athena-dark': '#1f2937',
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [require('@tailwindcss/forms')],
    corePlugins: {
        preflight: false, // Verhindern von Konflikten mit WordPress-Styles
    },
    important: '.athena-ai-admin', // Tailwind-Styles nur auf Plugin-Admin-Bereich anwenden
};
