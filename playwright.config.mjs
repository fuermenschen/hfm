import { defineConfig, devices } from "@playwright/test";
import "dotenv/config";

const baseURL = process.env.APP_URL || process.env.BASE_URL || "http://localhost:8000";

// Create a Playwright project for every built-in device descriptor.
const allDeviceNames = Object.keys(devices);

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
    projects: allDeviceNames.map((name) => ({
        name,
        use: { ...devices[name] },
    })),
});
