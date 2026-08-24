import { execSync } from "node:child_process";
import { existsSync, readFileSync, unlinkSync, writeFileSync } from "node:fs";
import { join } from "node:path";
import "dotenv/config";

const statePath = join(process.cwd(), "e2e", ".pw-debugbar-state.json");
const envPath = join(process.cwd(), ".env");
const hotPath = join(process.cwd(), "public", "hot");

const original = process.env.DEBUGBAR_ENABLED ?? null;
const wasEnabled = original === "true";

function run(command) {
    execSync(command, { stdio: "inherit" });
}

export default async function globalSetup() {
    const envContent = readFileSync(envPath, "utf8");
    const hotContent = existsSync(hotPath) ? readFileSync(hotPath, "utf8") : null;

    // Persist original state, .env contents, and Vite hot marker for teardown
    writeFileSync(statePath, JSON.stringify({ wasEnabled, original, envContent, hotContent }), "utf8");

    // Use built assets during e2e; a stale hot marker can point at a stopped dev server.
    if (hotContent !== null) {
        unlinkSync(hotPath);
    }

    // Overwrite DEBUGBAR_ENABLED in .env to ensure Laravel picks it up even when served by Herd
    const updated = envContent.match(/^(DEBUGBAR_ENABLED\s*=)/m)
        ? envContent.replace(/^(DEBUGBAR_ENABLED\s*=\s*).*/m, "$1false")
        : `${envContent.trimEnd()}\nDEBUGBAR_ENABLED=false\n`;
    writeFileSync(envPath, updated, "utf8");

    // Reset shared local database once so every worker uses same browser fixtures.
    process.env.DEBUGBAR_ENABLED = "false";
    run("php artisan migrate:fresh --seed --no-interaction");
    run("php artisan optimize:clear");
}
