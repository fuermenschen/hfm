import { expect, test } from "@playwright/test";

test.describe("Results page", () => {
    test("renders live totals, rankings, and partner totals on a standalone page", async ({ page }) => {
        await page.goto("/resultate", { waitUntil: "domcontentloaded" });

        // Ensure network idle (robust for screenshots); the page polls every
        // 15 seconds, so idle happens between polls.
        await page.waitForLoadState("networkidle");

        // Standalone TV page: the event title is the heading, no public chrome.
        await expect(page.getByRole("heading", { level: 1 })).toContainText("Höhenmeter für Menschen");
        await expect(page.getByText("Live", { exact: true })).toBeVisible();

        // Screenshot
        const startScreenshot = await page.screenshot({ fullPage: true });
        await test.info().attach("results-start", { body: startScreenshot, contentType: "image/png" });

        // Key totals
        await expect(page.getByText("Total Spenden", { exact: true })).toBeVisible();
        await expect(page.getByText("Absolvierte Runden", { exact: true })).toBeVisible();
        await expect(page.getByText("Höhenmeter", { exact: true }).first()).toBeVisible();

        // Desktop shows both rankings for one metric; mobile cycles one ranking
        // at a time through all athlete/group and metric combinations.
        await expect(page.getByText("Rangliste Sportler:innen").filter({ visible: true }).first()).toBeVisible();

        await expect(page.getByText("Rangliste Gruppen").filter({ visible: true }).first()).toBeVisible({ timeout: 15_000 });
        await expect(page.getByText("Spenden", { exact: true }).filter({ visible: true }).first()).toBeVisible();

        // Partner totals have no section heading; disclaimer is pinned to bottom.
        await expect(page.getByText("Spendenangaben basieren auf der Annahme")).toBeVisible();

        // The old public chrome must stay out of the standalone page.
        await expect(page.getByText("Hier siehst du alle Resultate der diesjährigen Durchführung.")).toHaveCount(0);
    });
});
