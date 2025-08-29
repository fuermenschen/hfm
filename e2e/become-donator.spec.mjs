import { test, expect } from "@playwright/test";

test.describe("Become Donator Form", () => {
    test("can submit become donator form", async ({ page }) => {
        await page.goto("/spenderin-werden", { waitUntil: "domcontentloaded" });
        await page.waitForLoadState("networkidle");
        await expect(page.locator("h1")).toContainText("Spender:in werden");
        await expect(page.locator("h2")).toContainText("Anmeldeformular");
        await page.getByRole("textbox", { name: "Vorname" }).click();
        await page.getByRole("textbox", { name: "Vorname" }).fill("Kristoffer");
        await page.getByRole("textbox", { name: "Nachname" }).fill("Jast");
        await page.getByRole("textbox", { name: "Adresse" }).fill("Goldener Brooks 82");
        // New PLZ group: country combobox + zipcode input with placeholder depending on country (default CH -> 8406)
        await page.getByRole("combobox", { name: "PLZ" }).click();
        // Keep default CH; now fill zip code via its placeholder-based name
        await page.getByRole("textbox", { name: "8406" }).fill("3044");
        await page.getByRole("textbox", { name: "Ort" }).fill("Bern City");
        const nonce = Date.now();
        const worker = process.env.TEST_WORKER_INDEX ?? "0";
        const uniqueEmail = `e2e+donator-w${worker}-${nonce}@example.test`;
        await page.getByRole("textbox", { name: "E-Mail", exact: true }).fill(uniqueEmail);
        await page.getByRole("textbox", { name: "E-Mail bestätigen" }).fill("fake@email.ch");
        // Trigger validation by blurring the confirmation field: focus phone input (placeholder varies by country)
        await page.getByRole("textbox", { name: "79 123 45 67" }).click();
        await expect(page.getByRole("alert")).toContainText("Die E-Mail-Adressen stimmen nicht überein.");
        await page.getByRole("textbox", { name: "79 123 45 67" }).fill("079 123 45 67");
        await page.getByLabel("Meine Unterstützung geht an").selectOption("3");
        await expect(page.locator("form")).toContainText("Mit deiner Unterstützung für");
        await page.getByRole("spinbutton", { name: "Dein Beitrag pro Runde" }).click();
        await page.getByRole("spinbutton", { name: "Dein Beitrag pro Runde" }).fill("8.27");
        await page.getByRole("spinbutton", { name: /Dein minimaler Beitrag/i }).fill("50");
        await page.getByRole("spinbutton", { name: /Dein maximaler Beitrag/i }).fill("500");
        await page.getByRole("textbox", { name: "Kommentar" }).click();
        await page.getByRole("textbox", { name: "Kommentar" }).fill("Testkommentar");
        await page.getByRole("button", { name: "Senden" }).click();
        // No aggregate modal anymore; rely on alert for remaining validation message(s)
        // Fix email confirmation and assert consent error shows up on next submit
        await page.getByRole("textbox", { name: "E-Mail bestätigen" }).click();
        await page.getByRole("textbox", { name: "E-Mail bestätigen" }).fill(uniqueEmail);
        await page.getByRole("button", { name: "Senden" }).click();
        await expect(page.getByText("Ich bin damit einverstanden,")).toHaveClass(/text-negative-600/);
        // Toggle privacy consent using its label
        await page.getByLabel(/Ich bin damit einverstanden/).click();
        await page.getByRole("button", { name: "Senden" }).click();
        await expect(page.locator("h3")).toContainText("Prüfe deine E-Mails");
        await expect(page.getByRole("paragraph")).toContainText(
            "Vielen Dank für deine Anmeldung zur Spende. Wir haben dir eine E-Mail mit weiteren Informationen gesendet. Deine Anmeldung ist erst nach Bestätigung der E-Mail gültig.",
        );
        await page.getByRole("button", { name: "OK" }).click();

        // assert that back one home page
        // TODO
    });
});
