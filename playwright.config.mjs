import { defineConfig } from "@playwright/test";
import "dotenv/config";

const baseURL = process.env.APP_URL || process.env.BASE_URL || "http://localhost:8000";

// Curated list of top screen sizes/aspect ratios (max 8–10)
const projects = [
    { name: "Desktop 1920x1080", use: { viewport: { width: 1920, height: 1080 } } },
    { name: "Desktop 1366x768", use: { viewport: { width: 1366, height: 768 } } },
    { name: "Laptop 1440x900", use: { viewport: { width: 1440, height: 900 } } },
    { name: "Laptop 1536x864", use: { viewport: { width: 1536, height: 864 } } },
    { name: "Tablet Portrait 768x1024", use: { viewport: { width: 768, height: 1024 } } },
    { name: "Tablet Landscape 1024x768", use: { viewport: { width: 1024, height: 768 } } },
    { name: "Mobile Small 360x800", use: { viewport: { width: 360, height: 800 }, isMobile: true } },
    { name: "Mobile Medium 390x844", use: { viewport: { width: 390, height: 844 }, isMobile: true } },
    { name: "Mobile Large 414x896", use: { viewport: { width: 414, height: 896 }, isMobile: true } },
];

export default defineConfig({
    testDir: "e2e",
    timeout: 30_000,
    expect: { timeout: 5_000 },
    reporter: [["list"], ["html", { open: "on-failure" }]],
    outputDir: "e2e-results",
    retries: 0,
    use: {
        baseURL,
        video: "off",
        trace: "off",
        screenshot: "off",
    },
    projects,
});
