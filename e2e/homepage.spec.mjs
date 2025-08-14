import { test } from "@playwright/test";

// This test runs once per device project defined in playwright.config.mjs
// It visits the front page and, after a successful load, takes a screenshot.

test("front page renders", async ({ page }) => {
    await page.goto("/", { waitUntil: "domcontentloaded" });
    // After the page has loaded successfully, take a screenshot
    const screenshotPath = test.info().outputPath("frontpage.png");
    await page.screenshot({ path: screenshotPath, fullPage: true });
});
