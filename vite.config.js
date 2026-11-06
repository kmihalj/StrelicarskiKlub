import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel([
            'resources/js/app.js',
        ]),
    ],
    build: {
        // Zadržava podržane preglednike iz Vitea 5.
        target: ['es2020', 'edge88', 'firefox78', 'chrome87', 'safari14'],
    },
    css: {
        preprocessorOptions: {
            scss: {
                // Sakriva deprecations iz node_modules; Vite koristi moderni Sass API.
                quietDeps: true,
            },
        },
    },
    resolve: {
        alias: {
            '~bootstrap': path.resolve(import.meta.dirname, 'node_modules/bootstrap'),
        }
    },
});
