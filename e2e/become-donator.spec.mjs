import { test, expect } from "@playwright/test";

test.describe("Become Donator Form", () => {
    test("can submit become donator form", async ({ page }) => {
        await page.goto("/spenderin-werden", { waitUntil: "domcontentloaded" });
        await page.waitForLoadState("networkidle");
        await expect(page.locator("h1")).toContainText("Spender:in werden");
        await expect(page.locator("h2")).toContainText("Anmeldeformular");
        await page.getByRole("textbox", { name: "Vorname" }).click();
        await page.getByRole("textbox", { name: "Vorname" }).fill("Kristoffer");
        await page.getByRole("textbox", { name: "Vorname" }).press("Tab");
        await page.getByRole("textbox", { name: "Nachname" }).fill("Jast");
        await page.getByRole("textbox", { name: "Nachname" }).press("Tab");
        await page.getByRole("textbox", { name: "Adresse" }).fill("Goldener Brooks 82");
        await page.getByRole("textbox", { name: "Adresse" }).press("Tab");
        await page.getByRole("textbox", { name: "PLZ" }).fill("3044");
        await page.getByRole("textbox", { name: "PLZ" }).press("Tab");
        await page.getByRole("textbox", { name: "Ort" }).fill("Bern City");
        await page.getByRole("textbox", { name: "Ort" }).press("Tab");
        const nonce = Date.now();
        const worker = process.env.TEST_WORKER_INDEX ?? "0";
        const uniqueEmail = `e2e+donator-w${worker}-${nonce}@example.test`;
        await page.getByRole("textbox", { name: "E-Mail", exact: true }).fill(uniqueEmail);
        await page.getByRole("textbox", { name: "E-Mail", exact: true }).press("Tab");
        await page.getByRole("textbox", { name: "E-Mail bestätigen" }).fill("fake@email.ch");
        await page.getByRole("textbox", { name: "E-Mail bestätigen" }).press("Tab");
        await expect(page.getByRole("alert")).toContainText("Die E-Mail-Adressen stimmen nicht überein.");
        await page.getByRole("textbox", { name: "Telefon" }).click();
        await page.getByRole("textbox", { name: "Telefon" }).fill("079 123 45 67");
        await page.getByRole("textbox", { name: "Telefon" }).press("Tab");
        await page.getByLabel("Meine Unterstützung geht an").selectOption("3");
        await expect(page.locator("form")).toContainText("Mit deiner Unterstützung für");
        await page.getByRole("spinbutton", { name: "Dein Beitrag pro Runde" }).click();
        await page.getByRole("spinbutton", { name: "Dein Beitrag pro Runde" }).fill("8.27");
        await page.getByRole("spinbutton", { name: "Dein Beitrag pro Runde" }).press("Tab");
        await page.getByRole("spinbutton", { name: "Dein minimaler Beitrag (" }).fill("50");
        await page.getByRole("spinbutton", { name: "Dein minimaler Beitrag (" }).press("Tab");
        await page.locator("span").filter({ hasText: "Dein minimaler Beitrag (" }).getByRole("button").press("Tab");
        await page.getByRole("spinbutton", { name: "Dein maximaler Beitrag (" }).fill("500");
        await page.getByRole("textbox", { name: "Kommentar" }).click();
        await page.getByRole("textbox", { name: "Kommentar" }).fill("Testkommentar");
        await page.getByRole("button", { name: "Senden" }).click();
        await expect(page.locator("h3")).toContainText("Es sind 2 Fehler aufgetreten.");
        await page.getByRole("button", { name: "OK" }).click();
        await page.getByRole("textbox", { name: "E-Mail bestätigen" }).click();
        await page.getByRole("textbox", { name: "E-Mail bestätigen" }).fill(uniqueEmail);
        await page.getByRole("button", { name: "Senden" }).click();
        await expect(page.locator("h3")).toContainText("Das muss akzeptiert werden.");
        await page.getByRole("button", { name: "OK" }).click();
        await page.locator("span").filter({ hasText: "Ich bin damit einverstanden," }).locator("div").nth(2).click();
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
