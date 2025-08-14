import { defineConfig } from "@playwright/test";
import "dotenv/config";

const baseURL = process.env.APP_URL || process.env.BASE_URL || "http://localhost:8000";

// Randomly select a color scheme per run (can be overridden with PW_COLOR_SCHEME)
const selectedColorScheme =
    process.env.PW_COLOR_SCHEME === "light" || process.env.PW_COLOR_SCHEME === "dark"
        ? process.env.PW_COLOR_SCHEME
        : Math.random() < 0.5
          ? "light"
          : "dark";

console.log(`[Playwright] Using color scheme: ${selectedColorScheme}`);

// Curated list of top screen sizes/aspect ratios (max 8–10) + a less-popular landscape smartphone
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
    // Less-popular: landscape smartphone size
    { name: "Mobile Landscape 800x360", use: { viewport: { width: 800, height: 360 }, isMobile: true } },
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
        colorScheme: selectedColorScheme,
    },
    projects,
});
