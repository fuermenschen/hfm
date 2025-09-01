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

// Determine if we should boot a local Laravel server (only for localhost/127.0.0.1 baseURLs)
const needsLocalServer = /^(https?:\/\/)(localhost|127\.0\.0\.1)(:\d+)?\/?$/i.test(baseURL);

// Curated list of top screen sizes/aspect ratios plus additional orientations and rare cases
const genericProjects = [
    {
        name: "Desktop",
        use: { viewport: { width: 1280, height: 800 } },
        testMatch: /.*\.spec\.mjs/,
        testIgnore: /smoke\.spec\.mjs/,
    },
    {
        name: "Mobile Portrait",
        use: { viewport: { width: 390, height: 844 }, isMobile: true },
        testMatch: /.*\.spec\.mjs/,
        testIgnore: /smoke\.spec\.mjs/,
    },
];

const homepageProjects = [
    // Desktops/Laptops
    { name: "Desktop 1920x1080", use: { viewport: { width: 1920, height: 1080 } }, testMatch: /homepage\.spec\.mjs/ },
    {
        name: "Desktop UltraWide 3440x1440",
        use: { viewport: { width: 3440, height: 1440 } },
        testMatch: /homepage\.spec\.mjs/,
    },
    { name: "Desktop 1366x768", use: { viewport: { width: 1366, height: 768 } }, testMatch: /homepage\.spec\.mjs/ },
    { name: "Laptop 1536x864", use: { viewport: { width: 1536, height: 864 } }, testMatch: /homepage\.spec\.mjs/ },
    { name: "Laptop 1440x900", use: { viewport: { width: 1440, height: 900 } }, testMatch: /homepage\.spec\.mjs/ },
    { name: "Netbook 1024x600", use: { viewport: { width: 1024, height: 600 } }, testMatch: /homepage\.spec\.mjs/ },

    // Tablets
    {
        name: "iPad Portrait 768x1024",
        use: { viewport: { width: 768, height: 1024 } },
        testMatch: /homepage\.spec\.mjs/,
    },
    {
        name: "iPad Landscape 1024x768",
        use: { viewport: { width: 1024, height: 768 } },
        testMatch: /homepage\.spec\.mjs/,
    },
    {
        name: "iPad Pro Portrait 1024x1366",
        use: { viewport: { width: 1024, height: 1366 } },
        testMatch: /homepage\.spec\.mjs/,
    },
    {
        name: "iPad Pro Landscape 1366x1024",
        use: { viewport: { width: 1366, height: 1024 } },
        testMatch: /homepage\.spec\.mjs/,
    },

    // Phones (common)
    {
        name: "Mobile Small 360x800",
        use: { viewport: { width: 360, height: 800 }, isMobile: true },
        testMatch: /homepage\.spec\.mjs/,
    },
    {
        name: "iPhone 14 Portrait 390x844",
        use: { viewport: { width: 390, height: 844 }, isMobile: true },
        testMatch: /homepage\.spec\.mjs/,
    },
    {
        name: "iPhone 14 Landscape 844x390",
        use: { viewport: { width: 844, height: 390 }, isMobile: true },
        testMatch: /homepage\.spec\.mjs/,
    },
    {
        name: "Mobile Large 414x896",
        use: { viewport: { width: 414, height: 896 }, isMobile: true },
        testMatch: /homepage\.spec\.mjs/,
    },
    {
        name: "Mobile Landscape 800x360",
        use: { viewport: { width: 800, height: 360 }, isMobile: true },
        testMatch: /homepage\.spec\.mjs/,
    },

    // Phones (rare/edge)
    {
        name: "Legacy Small 320x480",
        use: { viewport: { width: 320, height: 480 }, isMobile: true },
        testMatch: /homepage\.spec\.mjs/,
    },
    {
        name: "Galaxy Fold Portrait 280x653",
        use: { viewport: { width: 280, height: 653 }, isMobile: true },
        testMatch: /homepage\.spec\.mjs/,
    },
    {
        name: "Galaxy Fold Landscape 653x280",
        use: { viewport: { width: 653, height: 280 }, isMobile: true },
        testMatch: /homepage\.spec\.mjs/,
    },
    {
        name: "Surface Duo Portrait 540x720",
        use: { viewport: { width: 540, height: 720 }, isMobile: true },
        testMatch: /homepage\.spec\.mjs/,
    },
    {
        name: "Surface Duo Landscape 720x540",
        use: { viewport: { width: 720, height: 540 }, isMobile: true },
        testMatch: /homepage\.spec\.mjs/,
    },
];

// Dedicated projects for the smoke test: Desktop/Mobile in Light and Dark
const smokeProjects = [
    {
        name: "Smoke Desktop Light",
        testMatch: /smoke\.spec\.mjs/,
        use: { viewport: { width: 1280, height: 800 }, colorScheme: "light" },
    },
    {
        name: "Smoke Desktop Dark",
        testMatch: /smoke\.spec\.mjs/,
        use: { viewport: { width: 1280, height: 800 }, colorScheme: "dark" },
    },
    {
        name: "Smoke Mobile Light",
        testMatch: /smoke\.spec\.mjs/,
        use: { viewport: { width: 390, height: 844 }, isMobile: true, colorScheme: "light" },
    },
    {
        name: "Smoke Mobile Dark",
        testMatch: /smoke\.spec\.mjs/,
        use: { viewport: { width: 390, height: 844 }, isMobile: true, colorScheme: "dark" },
    },
];

export default defineConfig({
    testDir: "e2e",
    timeout: 30_000,
    expect: { timeout: 5_000 },
    reporter: [["list"], ["html", { open: "on-failure" }]],
    outputDir: "e2e-results",
    retries: 0,
    use: {
        baseURL, // Ensure relative navigations like page.goto('/') work
        video: "off",
        trace: "off",
        screenshot: "only-on-failure",
        colorScheme: selectedColorScheme,
    },
    // Start a local Laravel server when targeting localhost; otherwise assume external server (e.g. Herd)
    webServer: needsLocalServer
        ? {
              command: "php artisan serve --host=127.0.0.1 --port=8000",
              url: baseURL,
              reuseExistingServer: true,
              timeout: 120_000,
              stdout: "pipe",
              stderr: "pipe",
          }
        : undefined,
    projects: [...genericProjects, ...homepageProjects, ...smokeProjects],
});
