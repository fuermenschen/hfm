import { execSync } from "node:child_process";
import { existsSync, readFileSync, unlinkSync, writeFileSync } from "node:fs";
import { join } from "node:path";

const statePath = join(process.cwd(), "e2e", ".pw-debugbar-state.json");

function run(command) {
    execSync(command, { stdio: "inherit" });
}

export default async function globalTeardown() {
    if (!existsSync(statePath)) {
        return;
    }

    const { envContent } = JSON.parse(readFileSync(statePath, "utf8"));

    try {
        // Restore .env and env var
        writeFileSync(join(process.cwd(), ".env"), envContent, "utf8");
        delete process.env.DEBUGBAR_ENABLED;
        run("php artisan config:clear");
    } finally {
        unlinkSync(statePath);
    }
}
