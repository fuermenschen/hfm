# Reusable Upgrade Simulation Environment Plan

This document expands step 6 of `docs/plans/event-content-pr-implementation-plan.md` into an implementation-ready plan.

## 1) Goal and gate definition

Create a reusable, scripted "upgrade lab" that is mandatory before production deployments involving migrations.

The gate passes only when all of the following are true:

1. Baseline checks pass on the live-equivalent revision.
2. Upgrade checks pass on the target revision after deploy.
3. Runtime versions and core config are validated against production.
4. A run report is generated and archived.

The gate is intentionally semi-automated:

- Automation handles environment setup, deploy execution, and report scaffolding.
- Humans perform structured manual checks before and after upgrade via interactive prompts.
- Release-specific commands (backfills, data migrations) are run manually during the post-deploy pause.

## 2) Tooling decision (use Sail, with production-parity containers)

Use Laravel Sail as the command wrapper and orchestration entrypoint, but provide a dedicated Compose stack for this
lab so we can pin production-like versions.

Why this is sensible:

- Sail keeps Laravel command ergonomics (`./vendor/bin/sail ...`) and team familiarity.
- We can still run a custom app container that matches production constraints (Apache + PHP 8.4).
- We avoid coupling the upgrade gate to local Herd-only setups.

## 3) Production parity targets

Pin and verify these runtime components:

- PHP: `8.4.x`
- Apache: `2.4.x`
- MariaDB: `10.11.16`

Also capture and verify:

- PHP extensions required by app/runtime (`composer` requirements + production `php -m` diff check).
- MariaDB settings that can affect behavior (`sql_mode`, collation/charset, timezone).
- Apache modules needed by Laravel routing and headers (minimum: `rewrite`, `headers`).
- Queue/cache/mail dependencies used in production release flow.

## 4) Artifacts

1. `docker-compose.upgrade-lab.yml`
    - Services: `app`, `db`, and `mailpit`.
    - Named volumes for DB persistence per rehearsal run.
    - Health checks for `app` and `db`.
    - Stable local endpoints for app, Mailpit UI, Mailpit SMTP, DB.

2. `docker/upgrade-lab/app/Dockerfile`
    - Base image with PHP 8.4 + Apache 2.4.
    - Installs required PHP extensions and Composer.
    - Enables Apache modules required by app routing.

3. `docker/upgrade-lab/app/vhost.conf`
    - Document root to `public/`.
    - Laravel-friendly rewrite behavior.

4. `scripts/upgrade-lab`
    - Main orchestration script.
    - Required: `--target=<sha-or-branch>`, `--dump-path=<path>`
    - Optional: `--baseline=<ref>` (default: `main`), `--run-id=<id>`, `--no-interaction`, `--keep-running`

5. `docs/upgrade-lab-runbook.md`
    - Operational runbook for engineers and release managers.

6. `docs/upgrade-lab-checklist.md`
    - Documents the checklist items prompted during baseline and target phases.

## 5) Environment lifecycle model

Each rehearsal run gets an isolated run id:

- Project name: `hfm-upgrade-<run-id>`
- DB volume: `hfm-upgrade-db-<run-id>`
- Report folder: `storage/upgrade-lab/reports/<run-id>/`

This prevents cross-run data leakage and supports parallel rehearsals.

Stale worktrees are pruned automatically at the start of each run via `git worktree prune`.

## 5.1) Mail capture for login links and notifications

The lab must provide an inbox for testing magic-link/login and notification emails.

Required behavior:

1. Route mail to Mailpit inside the rehearsal stack (`MAIL_MAILER=smtp`, host `mailpit`, port `1025`).
2. Expose Mailpit UI to the developer (`http://localhost:18025` by default).
3. Include a report section that references where reviewers can find sent emails.

Validation minimum:

- During baseline and upgrade checks, trigger at least one login-link or equivalent app email and verify arrival in Mailpit.

## 6) Data source policy (dump-only)

All rehearsals use dump imports. Seed mode is intentionally disabled.

For production-representative rehearsal:

1. Validate dump path exists and is readable.
2. Import dump into lab DB.
3. Run post-import normalization (if needed):
    - ensure app env keys/config are lab-safe,
    - disable real outbound integrations,
    - ensure storage symlink and writable paths.
4. Continue baseline checks.

Security policy for dump mode:

- Prefer sanitized dumps for local/shared environments.
- Never write dump credentials into repo files.
- Keep dump handling outside committed config.

## 7) Rehearsal flow

The script is an interactive wizard. It always pauses after baseline checks and after target deploy so the operator can verify and run manual commands.

### Phase 1: Baseline

1. Start lab stack on baseline revision (`main` or live SHA).
2. Load SQL dump into baseline revision.
3. Record runtime parity snapshot.
4. Execute baseline checks (HTTP smoke).
5. Prompt operator for checklist items (interactive pass/fail).
6. Pause — operator verifies baseline in browser and Mailpit.

### Phase 2: Upgrade

1. Switch to target revision (`--target`).
2. Execute deploy steps automatically, mirroring production:
     - `php artisan down`
     - `php artisan migrate --no-interaction`
     - `php artisan storage:link --no-interaction`
     - `php artisan optimize`
     - `php artisan up --no-interaction`
3. Pause — operator runs any release-specific commands (backfills, data migrations) on the container.
4. Re-run checks and compare against baseline.

### Phase 3: Gate output

Generate `storage/upgrade-lab/reports/<run-id>/report.md` containing:

- baseline commit and target commit,
- dump reference,
- runtime version snapshot,
- pass/fail status per check,
- Mailpit verification result,
- blockers and required follow-up actions.

Exit code rules:

- `0` only when all mandatory checks pass.
- non-zero when any gate check fails.

### Non-interactive mode

Use `--no-interaction` to skip all pauses and auto-pass all checklist items. This is intended for automated testing only — the checklist gate is meaningless in this mode since no real verification occurs.

## 8) Command contract

```bash
scripts/upgrade-lab \
  --target=<target-sha-or-branch> \
  --dump-path=/absolute/path/to/dump.sql \
  [--baseline=<main-or-live-sha>] \
  [--run-id=<timestamp-or-ticket>] \
  [--no-interaction] \
  [--keep-running]
```

- `--commit` is supported as an alias for `--target`.

Expected operator experience:

1. Start one command with baseline, target, and dump-path inputs.
2. Complete baseline checklist (interactive pass/fail prompts).
3. Verify baseline in browser + Mailpit, press Enter.
4. Deploy runs automatically (mirrors production).
5. Run any release-specific commands on the container, press Enter.
6. Complete target checklist (interactive pass/fail prompts).
7. Review generated pass/fail report for go/no-go.

Release-specific commands (backfills, data migrations) are run by the operator directly on the container during the post-deploy pause — they must not be hardcoded into the script.

## 9) Reusability and maintenance policy

1. Keep this lab as a persistent release-engineering asset (not a one-off migration helper).
2. Reuse the same script contract for future schema/data upgrades.
3. Store past reports for auditability and release retrospectives.
4. Review pinned image versions quarterly and after production runtime changes.

## 10) Work packages (all implemented)

### WP1 - Runtime scaffold + Mailpit

- Provision isolated lab stack with pinned runtime targets (PHP 8.4, Apache 2.4, MariaDB 10.11.16).
- Provide scripted bootstrap entrypoint (`scripts/upgrade-lab`) for dump-based rehearsal.
- Route app mail to Mailpit and expose inbox UI for login-link verification.
- Emit a basic run report with runtime snapshot and Mailpit endpoint details.

### WP2 - Baseline -> upgrade two-phase orchestration

- Run baseline revision and target revision in one orchestrated rehearsal.
- Interactive pauses after baseline checks and after target deploy.
- Baseline and target commits are both recorded and compared.

### WP3 - Data source mode (`dump`)

- Implement dump mode (`--dump-path=...`) with validation and import.
- Add post-import normalization for safe local rehearsal (no real outbound side effects).

### WP4 - Deploy workflow mirroring

- Target deploy mirrors production deploy sequence: down, migrate, storage:link, optimize, up.
- Release-specific commands are run manually by the operator during the interactive pause.
- No hardcoded backfill or data migration commands in the script.

### WP5 - Structured checks + gate report

- Interactive pass/fail checklist for baseline and target phases.
- Capture required items (public pages, FAQ, registration guards, Mailpit email, admin pages, logs).
- `--no-interaction` auto-passes all checks for automated testing.
- Produce explicit go/no-go report with blockers.

### WP6 - Hardening and team adoption

- Runtime parity details (PHP extension snapshot, DB settings, Apache module snapshot).
- Runbook and troubleshooting playbook.
- Automatic stale worktree cleanup via `git worktree prune`.