import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/css/app.css',
                'resources/css/docs.css',
                'resources/js/docs.js',
            ],
            refresh: true,
        }),
        vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
        tailwindcss(),
    ],
    resolve: {
        alias: { '@': '/resources/js', '@ta': '/resources/js/tailadmin' }
    },
});
