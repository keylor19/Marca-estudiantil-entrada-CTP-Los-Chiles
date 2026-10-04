import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Colores tomados del escudo del CTP Los Chiles.
                ctp: {
                    50: '#effaf2',
                    100: '#d8f1df',
                    200: '#b3e2c0',
                    300: '#84cc99',
                    400: '#55ad6a', // verde del campo
                    500: '#3e9454',
                    600: '#2f7a43',
                    700: '#276238',
                    800: '#214e2f',
                    900: '#1b4028',
                    950: '#0e2415',
                },
                celeste: {
                    100: '#e0f2fb',
                    400: '#5bb8e3', // cielo
                    600: '#2b8fc0',
                },
                estrella: {
                    300: '#f5ec8a', // estrellas
                    400: '#eedc4f',
                },
            },
        },
    },

    plugins: [forms],
};
