import { test } from "@playwright/test";

// This test runs once per device project defined in playwright.config.mjs
// It visits the front page and, after a successful load, attaches screenshots to the report.

test("front page renders", async ({ page }) => {
    await page.goto("/", { waitUntil: "domcontentloaded" });

    // Ensure network is idle and all images are loaded before taking screenshots
    await page.waitForLoadState("networkidle");
    await page.waitForFunction(() => Array.from(document.images).every((img) => img.complete && img.naturalWidth > 0));
    // Ensure hero blur-up finished
    await page.waitForSelector(".hfm-hero.is-loaded", { state: "visible", timeout: 10000 });

    // 1) Visible viewport only (no scrolling)
    const viewportScreenshot = await page.screenshot({ fullPage: false });
    await test.info().attach("frontpage-viewport", {
        body: viewportScreenshot,
        contentType: "image/png",
    });

    // 2) Entire page - scroll through to trigger lazy-loaded images
    await page.evaluate(async () => {
        const delay = (ms) => new Promise((r) => setTimeout(r, ms));
        let lastScrollTop = -1;
        while (document.scrollingElement && document.scrollingElement.scrollTop !== lastScrollTop) {
            lastScrollTop = document.scrollingElement.scrollTop;
            document.scrollingElement.scrollBy(0, window.innerHeight);
            await delay(50);
        }
        window.scrollTo(0, document.body.scrollHeight);
        await delay(100);
    });

    await page.waitForLoadState("networkidle");
    await page.waitForFunction(() => Array.from(document.images).every((img) => img.complete && img.naturalWidth > 0));

    const fullPageScreenshot = await page.screenshot({ fullPage: true });
    await test.info().attach("frontpage-full", {
        body: fullPageScreenshot,
        contentType: "image/png",
    });
});
