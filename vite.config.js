import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],

    // server: {
    //     host: '0.0.0.0', // Exposes Vite on your local network
    //     hmr: {
    //         host: '111.111.1.111' // REPLACE THIS with your computer's local IPv4 address
    //     }
    // }
});
