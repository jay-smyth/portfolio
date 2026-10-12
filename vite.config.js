import {
    defineConfig,
    loadEnv
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: [`resources/views/**/*`],
            }),
            tailwindcss(),
        ],
        server: {
            cors: true,
            // Must match the port Sail publishes (VITE_PORT in .env).
            port: Number(env.VITE_PORT || 5173),
            strictPort: true,
        },
    };
});
