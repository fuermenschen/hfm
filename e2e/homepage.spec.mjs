import { test, expect } from "@playwright/test";

// This test runs once per device project defined in playwright.config.mjs
// It visits the front page and, after a successful load, attaches screenshots to the report.

test("front page renders", async ({ page }) => {
    await page.goto("/", { waitUntil: "domcontentloaded" });

    // 1) Visible viewport only (no scrolling)
    const viewportScreenshot = await page.screenshot({ fullPage: false });
    await test.info().attach("frontpage-viewport", {
        body: viewportScreenshot,
        contentType: "image/png",
    });

    // 2) Entire page
    const fullPageScreenshot = await page.screenshot({ fullPage: true });
    await test.info().attach("frontpage-full", {
        body: fullPageScreenshot,
        contentType: "image/png",
    });
});

