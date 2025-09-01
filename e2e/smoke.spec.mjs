// filepath: /Users/simonmoser/Code/hfm/e2e/smoke.spec.mjs
import { expect, test } from "@playwright/test";

// Public routes to verify (no tokens, no admin)
const publicRoutes = [
  "/",
  "/fragen-und-antworten",
  "/sportlerin-werden",
  "/spenderin-werden",
  "/verein",
  "/verein/mv",
  "/login",
  "/kontakt",
  "/impressum",
  "/datenschutz",
];

async function waitForImagesAndIdle(page) {
  await page.waitForLoadState("networkidle");
  // Wait until all images on the page are fully loaded
  await page.waitForFunction(() => Array.from(document.images).every((img) => img.complete && img.naturalWidth > 0));
  // If a hero blur-up is present, wait for it to finish
  const hasHero = await page.$(".hfm-hero");
  if (hasHero) {
    await page.waitForSelector(".hfm-hero.is-loaded", { state: "visible", timeout: 10_000 }).catch(() => {});
  }
}

async function slowScroll(page) {
  // Gradual scroll to trigger lazy loaded content but cap total time
  await page.evaluate(async () => {
    const delay = (ms) => new Promise((r) => setTimeout(r, ms));
    const start = performance.now();
    const maxMs = 1500; // cap per page scroll work
    const step = Math.max(1, Math.floor(window.innerHeight));
    let lastScrollTop = -1;

    while (document.scrollingElement) {
      const el = document.scrollingElement;
      const atBottom = Math.ceil(el.scrollTop + el.clientHeight) >= el.scrollHeight;
      if (atBottom || performance.now() - start > maxMs) {
        break;
      }
      el.scrollBy(0, step);
      if (el.scrollTop === lastScrollTop) {
        break;
      }
      lastScrollTop = el.scrollTop;
      await delay(16);
    }

    window.scrollTo(0, document.body.scrollHeight);
    await delay(50);
  });
}

// Capture console and page errors during the whole test
test("smoke: public pages", async ({ page }, testInfo) => {
  test.setTimeout(120_000);

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

  for (const path of publicRoutes) {
    const response = await page.goto(path, { waitUntil: "domcontentloaded" });
    expect(response, `Navigation failed for ${path}`).toBeTruthy();
    expect(response?.ok(), `Non-OK status for ${path}: ${response?.status()} ${response?.statusText()}`).toBeTruthy();

    await waitForImagesAndIdle(page);
    await slowScroll(page);

    // Full-page screenshot per page once
    const image = await page.screenshot({ fullPage: true });
    await testInfo.attach(`screenshot ${path}`, { body: image, contentType: "image/png" });
  }

  // Assert no console or page errors were logged across all pages
  expect(
    consoleErrors,
    consoleErrors.length
      ? `Errors encountered (count=${consoleErrors.length}):\n` +
          consoleErrors
            .map((e) => `- [${e.kind}] ${e.text}${e.location ? `\n  at: ${e.location}` : ""}`)
            .join("\n")
      : undefined
  ).toEqual([]);
});
