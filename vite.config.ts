import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts','resources/css/app.css'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),

        // Dinonaktifkan saat Docker build karena wayfinder butuh
        // koneksi ke app/routes yang belum tersedia saat build time.
        // Set DISABLE_WAYFINDER=true di Dockerfile untuk skip ini.
        ...(process.env.DISABLE_WAYFINDER
            ? []
            : [wayfinder({ formVariants: true })]
        ),

        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});