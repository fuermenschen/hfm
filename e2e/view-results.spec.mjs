import { test, expect } from "@playwright/test";

test.describe("Results page", () => {
    test("renders, shows info notices, and lists individual results", async ({ page }) => {
        await page.goto("/resultate", { waitUntil: "domcontentloaded" });

        // Ensure network idle and images are loaded (for robust screenshots)
        await page.waitForLoadState("networkidle");
        await page.waitForFunction(() =>
            Array.from(document.images).every((img) => img.complete && img.naturalWidth > 0),
        );

        await expect(page.getByRole("heading")).toContainText("Resultate");

        // Screenshot 1: initial page
        const startScreenshot = await page.screenshot({ fullPage: true });
        await test.info().attach("results-start", { body: startScreenshot, contentType: "image/png" });

        // Toggle the two info notices (Hinweis ...)
        const totalNoticeBtn = page.getByRole("button", { name: "Hinweis zu der" });
        const singlesNoticeBtn = page.getByRole("button", { name: "Hinweis zu Einzelresultaten" });
        await expect(totalNoticeBtn).toBeVisible();
        await expect(singlesNoticeBtn).toBeVisible();

        await totalNoticeBtn.click();
        await expect(page.getByText("Die Angaben der Spenden")).toBeVisible();

        await singlesNoticeBtn.click();
        await expect(page.getByText("Zu den Einzelresultaten mö")).toBeVisible();

        // Some key summary tiles should be visible on the default tab
        await expect(page.getByText("Sportler:innen")).toBeVisible();
        await expect(page.getByText("Spender:innen", { exact: true })).toBeVisible();
        await expect(page.getByText("Absolvierte Runden")).toBeVisible();
        await expect(page.getByText("Höhenmeter überwunden")).toBeVisible();
        await expect(page.getByText("Total Spenden")).toBeVisible();
        await expect(page.getByText("Spenden pro Benefizpartner:in")).toBeVisible();

        // Screenshot 2: after both info notices are expanded
        await page.waitForLoadState("networkidle");
        const afterHinweise = await page.screenshot({ fullPage: true });
        await test.info().attach("results-after-hinweise", { body: afterHinweise, contentType: "image/png" });

        // Go to the Einzelresultate tab and verify expected columns
        await page.getByRole("tab", { name: "Einzelresultate" }).click();
        await expect(page.getByText("Name", { exact: true })).toBeVisible();
        await expect(page.getByText("Sportart")).toBeVisible();
        await expect(page.getByText("Partner:in", { exact: true })).toBeVisible();
        await expect(page.getByText("Runden", { exact: true })).toBeVisible();
        await expect(page.getByText("Spenden", { exact: true })).toBeVisible();

        // Screenshot 3: after switching to the Einzelresultate tab
        await page.waitForLoadState("networkidle");
        const afterEinzel = await page.screenshot({ fullPage: true });
        await test.info().attach("results-after-einzelresultate", { body: afterEinzel, contentType: "image/png" });
    });
});
