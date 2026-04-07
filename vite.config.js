import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path'

export default defineConfig({
    plugins: [
        laravel([
            'resources/js/app.js',
        ]),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Koristi moderni Sass API i sakriva deprecations iz node_modules.
                api: 'modern-compiler',
                quietDeps: true,
                silenceDeprecations: ['legacy-js-api'],
            },
        },
    },
    resolve: {
        alias: {
            '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
        }
    },
});
