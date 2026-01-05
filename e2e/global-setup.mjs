import { execSync } from "node:child_process";
import { readFileSync, writeFileSync } from "node:fs";
import { join } from "node:path";
import "dotenv/config";

const statePath = join(process.cwd(), "e2e", ".pw-debugbar-state.json");
const envPath = join(process.cwd(), ".env");

const original = process.env.DEBUGBAR_ENABLED ?? null;
const wasEnabled = original === "true";

function run(command) {
    execSync(command, { stdio: "inherit" });
}

export default async function globalSetup() {
    const envContent = readFileSync(envPath, "utf8");

    // Persist original state and .env contents for teardown
    writeFileSync(statePath, JSON.stringify({ wasEnabled, original, envContent }), "utf8");

    // Overwrite DEBUGBAR_ENABLED in .env to ensure Laravel picks it up even when served by Herd
    const updated = envContent.match(/^(DEBUGBAR_ENABLED\s*=)/m)
        ? envContent.replace(/^(DEBUGBAR_ENABLED\s*=\s*).*/m, "$1false")
        : `${envContent.trimEnd()}\nDEBUGBAR_ENABLED=false\n`;
    writeFileSync(envPath, updated, "utf8");

    // Disable debugbar for the current process and clear cached config
    process.env.DEBUGBAR_ENABLED = "false";
    run("php artisan config:clear");
}
