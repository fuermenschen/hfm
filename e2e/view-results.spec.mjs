import { expect, test } from "@playwright/test";

test.describe("Results page", () => {
    test("renders, shows info notices, and lists individual results", async ({ page }) => {
        await page.goto("/resultate", { waitUntil: "domcontentloaded" });

        // Ensure network idle and images are loaded (for robust screenshots)
        await page.waitForLoadState("networkidle");

        await expect(page.getByRole("heading")).toContainText("Resultate");

        // Screenshot
        const startScreenshot = await page.screenshot({ fullPage: true });
        await test.info().attach("results-start", { body: startScreenshot, contentType: "image/png" });

        await expect(page.getByText("Die Angaben der Spenden")).toBeVisible();

        // Some key summary tiles should be visible on the default tab
        await expect(page.getByText("Sportler:innen", { exact: true })).toBeVisible();
        await expect(page.getByText("Spender:innen", { exact: true })).toBeVisible();
        await expect(page.getByText("Absolvierte Runden")).toBeVisible();
        await expect(page.getByText("Höhenmeter überwunden")).toBeVisible();
        await expect(page.getByText("Total Spenden")).toBeVisible();
        await expect(page.getByText("Spenden pro Benefizpartner:in")).toBeVisible();
    });
});
