import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import { ViteImageOptimizer } from "vite-plugin-image-optimizer";

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        ViteImageOptimizer({
            jpeg: {
                quality: 60,
                width: 3000,
                height: 3000,
            },
        }),
    ],
    assetsInclude: ["**/*.gpx"],
});
