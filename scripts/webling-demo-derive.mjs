// Derives Webling API settings for the demo from the public Webling demo instance.
//
// Browser is only used for what requires a session: login + reading/creating the
// API key. Everything else (accounting period, debit/credit accounts) comes from
// the documented REST API with that key — stable, no UI scraping.
//
// Screenshots for debugging go to e2e-results/webling-demo-derive/ (gitignored).
//
// Usage: node scripts/webling-demo-derive.mjs [--dry-run] [--recreate] [--no-interaction]
//   --recreate: delete existing key(s) first so the create path runs.
//   --no-interaction: for agents — no progress output, no step screenshots, JSON result only.

import { execFileSync } from "node:child_process";
import { mkdirSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { chromium } from "playwright";

const BASE = (process.env.WEBLING_BASE_URL || "https://demo1.webling.ch").replace(/\/+$/, "");
// Public demo credentials, published by Webling — not a real credential.
const EMAIL = process.env.WEBLING_DEMO_EMAIL || "demo@webling.ch";
const PASSWORD = process.env.WEBLING_DEMO_PASSWORD || "webling";
const KEY_NAME = "hfm-test-api-key";
// Repo-local, gitignored via /e2e-results/**.
const SHOTS = fileURLToPath(new URL("../e2e-results/webling-demo-derive/", import.meta.url));
const DRY_RUN = process.argv.includes("--dry-run");
const RECREATE = process.argv.includes("--recreate");
const QUIET = process.argv.includes("--no-interaction");

mkdirSync(SHOTS, { recursive: true });

const start = Date.now();
const log = (msg) => {
    if (QUIET) {
        return;
    }
    const secs = String(Math.round((Date.now() - start) / 1000)).padStart(3, " ");
    console.log(`[${secs}s] ${msg}`);
};

const shot = (page, name, force = false) => {
    if (QUIET && !force) {
        return Promise.resolve();
    }

    return page.screenshot({ path: `${SHOTS}/${name}.png`, fullPage: true }).catch(() => {});
};

async function fail(page, name, message) {
    await shot(page, name, true);
    throw new Error(`${message} (screenshot: ${SHOTS}/${name}.png)`);
}

// Webling REST helpers. Full list responses may or may not carry object ids;
// fall back to id list + per-object GETs.
async function api(key, path) {
    const sep = path.includes("?") ? "&" : "?";
    const res = await fetch(`${BASE}/api/1/${path}${sep}apikey=${key}`);
    if (!res.ok) {
        throw new Error(`Webling API ${path} failed: ${res.status} ${await res.text()}`);
    }
    return res.json();
}

const list = (json) => json.objects ?? json;

async function fullObjects(key, type) {
    const res = await api(key, `${type}?format=full`);
    const objs = Array.isArray(res) ? res : (res.objects ?? []);
    if (objs.length && objs.every((o) => o?.id != null)) {
        return objs;
    }
    return Promise.all(list(await api(key, type)).map(async (id) => ({ id, ...(await api(key, `${type}/${id}`)) })));
}

const browser = await chromium.launch();
const page = await browser.newPage();

// --- 1. Login -----------------------------------------------------------------

log(`Opening ${BASE} and logging in …`);
await page.goto(BASE);
await page.waitForLoadState("networkidle").catch(() => {});

const emailInput = page.locator("input[type=email], input[name=email]").first();
if ((await emailInput.count()) && !(await emailInput.inputValue())) {
    await emailInput.fill(EMAIL);
}
const pwInput = page.locator("input[type=password]").first();
if ((await pwInput.count()) && !(await pwInput.inputValue())) {
    await pwInput.fill(PASSWORD);
}

const submit = page.getByRole("button", { name: /anmelden|login/i }).first();
if (await submit.count()) {
    await submit.click();
} else {
    await page.locator("input[type=submit], button[type=submit]").first().click();
}
await page.waitForLoadState("networkidle").catch(() => {});
log("Logged in");
await shot(page, "01-after-login");

// --- 2. API key page ------------------------------------------------------------

const waitForApiKeyPage = async () => {
    log("Opening API key admin page …");
    await page.goto(`${BASE}/admin#/admin/api`);
    await page.waitForLoadState("networkidle").catch(() => {});
    await Promise.race([
        page.locator(`text=${KEY_NAME}`).first().waitFor({ timeout: 20000 }),
        page.getByRole("button", { name: "Neuer Apikey" }).waitFor({ timeout: 20000 }),
    ]).catch(() =>
        fail(
            page,
            "02-admin-api-page",
            'API key page did not render (neither key row nor "Neuer Apikey" button found)',
        ),
    );
};

await waitForApiKeyPage();
// The "Neuer Apikey" button can win the wait race before the table renders —
// always give the row a chance to appear before counting.
await page
    .locator(`tr:has-text("${KEY_NAME}")`)
    .first()
    .waitFor({ timeout: 15000 })
    .catch(() => {});
const textBeforeModal = await page.locator("body").innerText();

// Opens the key modal via the key symbol in the table row.
const openKeyModal = async () => {
    const row = page.locator("tr", { hasText: KEY_NAME }).first();
    if (!(await row.count())) {
        await fail(page, "04-key-row-missing", `Row for "${KEY_NAME}" not found`);
    }

    // The key symbol sits next to the name; click the first icon-ish control in the row.
    await row.locator('svg, [class*="key"], button, a').first().click();
    // After creation the key fills in asynchronously — wait for a populated field.
    await page
        .waitForFunction(
            () => /^[A-Za-z0-9]{32}$/.test(document.querySelector("textarea.copy-text__textbox")?.value ?? ""),
            { timeout: 15000 },
        )
        .catch(() => fail(page, "05-key-modal", "Key modal did not open or key never appeared"));
    await shot(page, "05-key-modal");
};

// Extracts the 32-char key: exact field first, then generic modal scan, then clipboard.
const extractKey = async () => {
    const textbox = await page
        .locator("textarea.copy-text__textbox")
        .first()
        .inputValue()
        .catch(() => "");
    if (/^[A-Za-z0-9]{32}$/.test(textbox)) {
        return textbox;
    }

    let key = [
        await page.locator("body").textContent(),
        ...(await Promise.all(
            (await page.locator("input, textarea").all()).map((el) => el.inputValue().catch(() => "")),
        )),
    ]
        .flatMap((text) => text?.match(/[A-Za-z0-9]{32}/g) ?? [])
        .find((t) => !textBeforeModal.includes(t));
    if (!key) {
        await page
            .getByText("Kopieren")
            .first()
            .click()
            .catch(() => {});
        key = await page
            .evaluate(() => navigator.clipboard.readText().catch(() => ""))
            .then((t) => (/^[A-Za-z0-9]{32}$/.test(t ?? "") ? t : null))
            .catch(() => null);
    }
    if (!key) {
        await fail(page, "06-key-not-found", "Could not extract a 32-char API key from the modal");
    }

    return key;
};

// Optional: delete existing key(s) first so the create path gets exercised.
// Keys cannot delete themselves via the API (400), so this goes through the UI.
if (RECREATE) {
    log("Deleting existing key(s) via UI …");
    for (let i = 0; (await page.locator(`tr:has-text("${KEY_NAME}")`).count()) && i < 5; i++) {
        await openKeyModal();
        await page.locator("button.dialog-more").click();
        await page.getByText("API-Key löschen").first().click();
        page.once("dialog", (d) => d.accept());
        await page.waitForTimeout(1000);
        const confirmBtn = page.locator(".dialog-modal button", { hasText: /^löschen$/i }).last();
        if (await confirmBtn.count()) {
            await confirmBtn.click();
        }
        await waitForApiKeyPage();
        await page
            .locator(`tr:has-text("${KEY_NAME}")`)
            .first()
            .waitFor({ timeout: 15000 })
            .catch(() => {});
    }
    if (await page.locator(`tr:has-text("${KEY_NAME}")`).count()) {
        await fail(page, "07-delete-failed", "Key still exists after delete");
    }
    log("Deleted existing key(s) for recreation");
}

const keyExists = (await page.locator(`tr:has-text("${KEY_NAME}")`).count()) > 0;
log(`API key "${KEY_NAME}" ${keyExists ? "exists" : "does not exist yet"}`);

if (!keyExists) {
    log("Creating API key …");
    await page.getByRole("button", { name: "Neuer Apikey" }).first().click();
    await page.waitForLoadState("networkidle").catch(() => {});

    // Description input inside the modal.
    const desc = page.getByLabel(/beschreibung/i).first();
    if (await desc.count()) {
        await desc.fill(KEY_NAME);
    } else {
        const modal = page.locator(".modal, [role=dialog], .fixed.inset-0").last();
        await modal.locator("input[type=text], input:not([type])").first().fill(KEY_NAME);
    }

    // Administrator rights checkbox.
    const adminCheckbox = page
        .locator("label", { hasText: "Administrator-Rechte" })
        .locator("input[type=checkbox]")
        .first();
    if (!(await adminCheckbox.count())) {
        await page.getByRole("checkbox").first().check();
    } else if (!(await adminCheckbox.isChecked())) {
        await adminCheckbox.check();
    }

    await shot(page, "03-new-apikey-modal");
    await page
        .getByRole("button", { name: /speichern|save/i })
        .first()
        .click();
    await page.waitForLoadState("networkidle").catch(() => {});
    await page
        .locator(`tr:has-text("${KEY_NAME}")`)
        .first()
        .waitFor({ timeout: 10000 })
        .catch(() => {});
}

// --- 3. Read the key ------------------------------------------------------------

log("Reading API key from modal …");
await openKeyModal();
const apiKey = await extractKey();
log(`API key read (${apiKey.slice(0, 4)}…${apiKey.slice(-4)})`);
await page.keyboard.press("Escape");

// --- 4. Derive period + accounts via the documented REST API ----------------------

log("Deriving accounting period + accounts via Webling API …");
const periods = await fullObjects(apiKey, "period");
// Demo has several periodgroups (e.g. "KMU-Kontenplan" playground). Our integration
// books debitors, so use the periodgroup that owns the debitorcategories.
const periodGroups = await fullObjects(apiKey, "periodgroup");
const realGroup = periodGroups.find((pg) => (pg.children?.debitorcategory ?? []).length > 0) ?? periodGroups[0];
const realPeriodIds = new Set(realGroup?.children?.period ?? []);
const today = new Date().toISOString().slice(0, 10);
const usable = periods.filter((p) => realPeriodIds.has(p.id));
const period =
    usable.find((p) => p.properties.state === "open" && p.properties.from <= today && today <= p.properties.to) ??
    usable.find((p) => p.properties.state === "open");
if (!period) throw new Error("No open accounting period found");

const groupById = new Map((await fullObjects(apiKey, "accountgroup")).map((g) => [g.id, g]));
const accounts = await fullObjects(apiKey, "account");
// Accounts exist per period; scope to the selected period via its accountgroup.
const byType = (title, type) =>
    accounts.filter(
        (a) =>
            a.properties.title === title &&
            (a.parents ?? []).some(
                (gid) =>
                    groupById.get(gid)?.properties.type === type && groupById.get(gid)?.parents?.includes(period.id),
            ),
    );

const pick = (candidates, label) => {
    if (candidates.length !== 1) {
        throw new Error(
            `Expected exactly one account for ${label}, found ${candidates.length}: ${JSON.stringify(candidates.map((a) => ({ id: a.id, title: a.properties.title })))}`,
        );
    }
    return candidates[0].id;
};

const debitId = pick(byType("Debitoren", "assets"), 'debit account "Debitoren" (assets)');
const creditId = pick(
    byType("Einnahmen aus Veranstaltungen", "income"),
    'credit account "Einnahmen aus Veranstaltungen" (income)',
);

// --- 5. Persist into WeblingApiSettings -------------------------------------------

// Always printed — the machine-readable result.
console.log(
    JSON.stringify(
        {
            api_url: BASE,
            api_key: `${apiKey.slice(0, 4)}…${apiKey.slice(-4)}`,
            accounting_period_id: period.id,
            debit_account_id: debitId,
            credit_account_id: creditId,
        },
        null,
        2,
    ),
);

if (!DRY_RUN) {
    log("Persisting WeblingApiSettings …");
    // Values via env so they never land in argv / error output.
    const php =
        "$s = app(App\\Settings\\WeblingApiSettings::class); " +
        '$s->api_url = getenv("WEBLING_URL"); $s->api_key = getenv("WEBLING_KEY"); ' +
        '$s->accounting_period_id = (int) getenv("WEBLING_PERIOD"); ' +
        '$s->debit_account_id = (int) getenv("WEBLING_DEBIT"); $s->credit_account_id = (int) getenv("WEBLING_CREDIT"); ' +
        "$s->save();";
    execFileSync("php", ["artisan", "tinker", "--execute", php], {
        stdio: "inherit",
        env: {
            ...process.env,
            WEBLING_URL: BASE,
            WEBLING_KEY: apiKey,
            WEBLING_PERIOD: String(period.id),
            WEBLING_DEBIT: String(debitId),
            WEBLING_CREDIT: String(creditId),
        },
    });
    log("WeblingApiSettings updated.");
} else {
    log("Dry run: settings not written.");
}

await browser.close();
log("Done");
