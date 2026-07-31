import { execFileSync } from "node:child_process";
import { expect, test } from "@playwright/test";

function runArtisan(args) {
    return execFileSync("php", ["artisan", ...args], {
        stdio: ["ignore", "pipe", "inherit"],
        encoding: "utf8",
    }).trim();
}

function portalLoginUrl() {
    return runArtisan([
        "tinker",
        '--execute=$user = App\\Models\\ExternalUser::query()->where("email", "portal-smoke@example.test")->firstOrFail(); echo Illuminate\\Support\\Facades\\URL::temporarySignedRoute("portal.login.uuid", now()->addMinutes(30), ["uuid" => $user->uuid]);',
    ]);
}

async function screenshotViewport(page, testInfo, name) {
    const screenshot = await page.screenshot({ fullPage: false });

    await testInfo.attach(name, { body: screenshot, contentType: "image/png" });
}

async function navigateToPortalPage(page, heading) {
    const sidebarLink = page.locator("[data-flux-sidebar]").getByRole("link", { name: heading, exact: true });
    const mobileLink = page.getByLabel("Portal-Navigation").getByRole("link", { name: heading, exact: true });
    const link = page.viewportSize().width < 640 ? mobileLink : sidebarLink;

    await link.click();
    await page.waitForLoadState("networkidle");
}

async function assertHealthy(page) {
    await page.waitForLoadState("networkidle");
}

test("smoke: external user portal navigation", async ({ page }, testInfo) => {
    test.setTimeout(120_000);

    const loginUrl = portalLoginUrl();
    const consoleErrors = [];

    page.on("console", (message) => {
        if (message.type() === "error") consoleErrors.push(message.text());
    });
    page.on("pageerror", (error) => consoleErrors.push(String(error?.message || error)));

    const loginResponse = await page.goto(loginUrl, { waitUntil: "domcontentloaded" });

    expect(loginResponse, "Portal login navigation failed").toBeTruthy();
    expect(loginResponse?.ok(), `Portal login returned non-OK response: ${loginResponse?.status()}`).toBeTruthy();
    await page.waitForURL(/\/portal/);

    const pages = [
        { name: "portal-overview", heading: "Übersicht" },
        { name: "portal-participations", heading: "Teilnahmen" },
        { name: "portal-donations", heading: "Spenden" },
    ];

    for (const [index, portalPage] of pages.entries()) {
        if (index > 0) {
            await navigateToPortalPage(page, portalPage.heading);
        }

        await assertHealthy(page);
        await screenshotViewport(page, testInfo, portalPage.name);
    }

    expect(consoleErrors, consoleErrors.join("\n")).toEqual([]);
});
