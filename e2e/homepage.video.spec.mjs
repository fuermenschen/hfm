import { test, expect } from "@playwright/test";

// Enable video for all tests in this file (only contains the video walkthrough test)
test.use({ video: "on" });

// Video test: load front page, wait 5 seconds, then slowly scroll to the bottom.
// It will run once per project (viewport) defined in playwright.config.mjs.
test("front page video: wait and slow-scroll", async ({ page }) => {
    await page.goto("/", { waitUntil: "domcontentloaded" });

    // Wait 5 seconds
    await page.waitForTimeout(5000);

    // Slowly scroll to the bottom
    await page.evaluate(async () => {
        const delay = (ms) => new Promise((res) => setTimeout(res, ms));
        const totalHeight = document.documentElement.scrollHeight || document.body.scrollHeight;
        const viewportHeight = window.innerHeight;
        let current = window.scrollY || window.pageYOffset || 0;
        const step = Math.max(10, Math.floor(viewportHeight * 0.08)); // 8% of viewport or at least 10px

        while (current + viewportHeight < totalHeight) {
            window.scrollTo({ top: current + step, behavior: "smooth" });
            await delay(120); // adjust for smoothness/speed
            current = Math.min(current + step, totalHeight - viewportHeight);
        }

        // Ensure we reach absolute bottom
        window.scrollTo({ top: totalHeight, behavior: "smooth" });
        await delay(500);
    });

    // Small assertion to ensure the page actually loaded something meaningful
    await expect(page).toHaveURL(/\//);
});
