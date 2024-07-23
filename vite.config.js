import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { ViteImageOptimizer } from "vite-plugin-image-optimizer";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/sass/app.scss",
                "resources/js/app.js",
                "resources/css/darkmode_on.css"
            ],
            refresh: true
        }),
        ViteImageOptimizer({
            jpeg: {
                quality: 60,
                width: 3000,
                height: 3000
            }
        })
    ],
    assetsInclude: [
        "**/*.gpx"
    ]
});
