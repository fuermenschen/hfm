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

// Run functional specs on one representative desktop and mobile viewport.
const genericProjects = [
    {
        name: "Desktop",
        use: { viewport: { width: 1280, height: 800 } },
        testMatch: /.*\.spec\.mjs/,
        testIgnore: /(?:smoke|portal)\.spec\.mjs/,
    },
    {
        name: "Mobile Portrait",
        use: { viewport: { width: 390, height: 844 }, isMobile: true },
        testMatch: /.*\.spec\.mjs/,
        testIgnore: /(?:smoke|portal)\.spec\.mjs/,
    },
];

// Smoke all public pages in both themes, using desktop and smallest supported mobile viewport.
const smokeProjects = [
    {
        name: "Smoke Desktop Light",
        testMatch: /smoke\.spec\.mjs/,
        use: { viewport: { width: 1280, height: 800 }, colorScheme: "light" },
    },
    {
        name: "Smoke iPhone SE Dark",
        testMatch: /smoke\.spec\.mjs/,
        use: { viewport: { width: 320, height: 568 }, isMobile: true, colorScheme: "dark" },
    },
];

const portalProjects = [
    {
        name: "Portal Desktop Light",
        use: { viewport: { width: 1280, height: 800 }, colorScheme: "light" },
        testMatch: /portal\.spec\.mjs/,
    },
    {
        name: "Portal Desktop Dark",
        use: { viewport: { width: 1280, height: 800 }, colorScheme: "dark" },
        testMatch: /portal\.spec\.mjs/,
    },
    {
        name: "Portal Mobile Light",
        use: { viewport: { width: 390, height: 844 }, isMobile: true, colorScheme: "light" },
        testMatch: /portal\.spec\.mjs/,
    },
    {
        name: "Portal Mobile Dark",
        use: { viewport: { width: 390, height: 844 }, isMobile: true, colorScheme: "dark" },
        testMatch: /portal\.spec\.mjs/,
    },
];

export default defineConfig({
    testDir: "e2e",
    timeout: 30_000,
    expect: { timeout: 5_000 },
    globalSetup: "./e2e/global-setup.mjs",
    globalTeardown: "./e2e/global-teardown.mjs",
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
              env: {
                  ...process.env,
                  // Force debugbar off during tests; global setup/teardown toggles back if needed.
                  DEBUGBAR_ENABLED: "false",
              },
          }
        : undefined,
    projects: [...genericProjects, ...smokeProjects, ...portalProjects],
});
