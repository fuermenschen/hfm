import { execFileSync } from "node:child_process";
import { expect, test } from "@playwright/test";

const adminRoutes = [
    "/admin",
    "/admin/sportlerinnen",
    "/admin/spenderinnen",
    "/admin/spenden",
    "/admin/tools",
    "/admin/einstellungen",
];

function runArtisan(args) {
    return execFileSync("php", ["artisan", ...args], {
        stdio: ["ignore", "pipe", "inherit"],
        encoding: "utf8",
    }).trim();
}

function ensureSeededDatabase() {
    const userCount = Number.parseInt(runArtisan(["tinker", "--execute=echo App\\Models\\User::query()->count();"]), 10);

    if (Number.isNaN(userCount) || userCount === 0) {
        runArtisan(["db:seed", "--no-interaction"]);
    }
}

function signedAdminLoginUrl() {
    return runArtisan([
        "tinker",
        '--execute=$user = App\\Models\\User::query()->firstOrFail(); echo Illuminate\\Support\\Facades\\URL::temporarySignedRoute("login-uuid", now()->addMinutes(30), ["uuid" => $user->uuid]);',
    ]);
}

async function waitForImagesAndIdle(page) {
    await page.waitForLoadState("networkidle");
    await page.waitForFunction(() => Array.from(document.images).filter((img) => img.hasAttribute("src") && !img.src.startsWith("data:")).every((img) => img.complete && img.naturalWidth > 0));
}

async function slowScroll(page) {
    await page.evaluate(async () => {
        const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
        const start = performance.now();
        const maxMs = 1500;
        const step = Math.max(1, Math.floor(window.innerHeight));
        let lastScrollTop = -1;

        while (document.scrollingElement) {
            const element = document.scrollingElement;
            const atBottom = Math.ceil(element.scrollTop + element.clientHeight) >= element.scrollHeight;

            if (atBottom || performance.now() - start > maxMs) {
                break;
            }

            element.scrollBy(0, step);

            if (element.scrollTop === lastScrollTop) {
                break;
            }

            lastScrollTop = element.scrollTop;
            await delay(16);
        }

        window.scrollTo(0, document.body.scrollHeight);
        await delay(50);
    });
}

test("smoke: admin pages", async ({ page }, testInfo) => {
    test.setTimeout(120_000);

    ensureSeededDatabase();

    const loginUrl = signedAdminLoginUrl();
    const loginResponse = await page.goto(loginUrl, { waitUntil: "domcontentloaded" });

    expect(loginResponse, "Admin login navigation failed").toBeTruthy();
    expect(loginResponse?.ok(), `Admin login returned non-OK response: ${loginResponse?.status()}`).toBeTruthy();

    await page.waitForURL(/\/admin/);

    const consoleErrors = [];

    page.on("console", (message) => {
        if (message.type() === "error") {
            consoleErrors.push({
                kind: "console",
                text: message.text(),
                location: message.location?.url || "",
            });
        }
    });

    page.on("pageerror", (error) => {
        consoleErrors.push({ kind: "pageerror", text: String(error?.message || error), location: page.url() });
    });

    for (const path of adminRoutes) {
        const response = await page.goto(path, { waitUntil: "domcontentloaded" });

        expect(response, `Navigation failed for ${path}`).toBeTruthy();
        expect(
            response?.ok(),
            `Non-OK status for ${path}: ${response?.status()} ${response?.statusText()}`,
        ).toBeTruthy();

        await waitForImagesAndIdle(page);
        await slowScroll(page);

        const screenshot = await page.screenshot({ fullPage: true });
        await testInfo.attach(`screenshot ${path}`, { body: screenshot, contentType: "image/png" });
    }

    expect(
        consoleErrors,
        consoleErrors.length
            ? `Errors encountered (count=${consoleErrors.length}):\n` +
                  consoleErrors
                      .map(
                          (error) =>
                              `- [${error.kind}] ${error.text}${error.location ? `\n  at: ${error.location}` : ""}`,
                      )
                      .join("\n")
            : undefined,
    ).toEqual([]);
});
