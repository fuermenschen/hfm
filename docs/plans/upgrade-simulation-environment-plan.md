# Reusable Upgrade Simulation Environment Plan

This document expands step 6 of `docs/plans/event-content-pr-implementation-plan.md` into an implementation-ready plan.

## 1) Goal and gate definition

Create a reusable, scripted "upgrade lab" that is mandatory before production deployments involving migrations and
backfills.

The gate passes only when all of the following are true:

1. Baseline checks pass on the live-equivalent revision.
2. Upgrade checks pass on the target revision after migrations/backfills.
3. Idempotency checks pass (backfill can run twice without duplication side effects).
4. Runtime versions and core config are validated against production.
5. A run report is generated and archived.

The gate is intentionally semi-automated:

- Automation handles environment setup, baseline/upgrade command execution, and report scaffolding.
- Humans perform structured manual checks before and after upgrade.
- Optional manual command hooks are available for release-specific actions.

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

## 4) Artifacts to add

1. `docker-compose.upgrade-lab.yml`
    - Services: `app`, `db`, optional `redis`, and `mailpit` (enabled by default for rehearsal usability).
    - Named volumes for DB persistence per rehearsal run.
    - Health checks for `app` and `db`.
    - Stable local endpoints:
        - app: `http://localhost:<app-port>`
        - Mailpit UI: `http://localhost:18025` (or configured port)

2. `docker/upgrade-lab/app/Dockerfile`
    - Base image with PHP 8.4 + Apache 2.4.
    - Installs required PHP extensions and Composer.
    - Enables Apache modules required by app routing.

3. `docker/upgrade-lab/app/vhost.conf`
    - Document root to `public/`.
    - Laravel-friendly rewrite behavior.

4. `scripts/upgrade-lab`
    - Main orchestration script with required parameters:
        - `--target=<sha-or-branch>`
        - `--dump-path=<path>`
    - Optional:
        - `--baseline=<sha-or-branch>` (default: current live/main)
        - `--run-id=<id>`
        - `--auto-deploy-steps=true|false`
        - `--pause-before-upgrade-checks`
        - `--manual-commands-file=<path>`
        - `--keep-running`

5. `scripts/upgrade-lab-checks`
    - Executes smoke checks, key command checks, and log scans.
    - Writes machine-readable and human-readable results.

6. `docs/upgrade-lab-runbook.md`
    - Operational runbook for engineers and release managers.

7. `docs/upgrade-lab-checklist.md`
    - Operator checklist used during manual baseline and post-upgrade verification.

## 5) Environment lifecycle model

Each rehearsal run gets an isolated run id:

- Project name: `hfm-upgrade-<run-id>`
- DB volume: `hfm-upgrade-db-<run-id>`
- Report folder: `storage/upgrade-lab/reports/<run-id>/`

This prevents cross-run data leakage and supports parallel rehearsals.

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

## 7) Scripted rehearsal flow (required)

This flow is designed around two manual check windows and one automation window.

### Phase 1: Baseline

1. Start lab stack on baseline revision (`main` or live SHA).
2. Load SQL dump into baseline revision.
3. Record runtime parity snapshot:
    - `php -v`
    - `apache2 -v`
    - MariaDB `SELECT VERSION();`
    - key `php -m` output
4. Execute baseline checks:
    - `/` renders expected event content blocks.
    - `/fragen-und-antworten` renders grouped/event-correct FAQ content.
    - `/admin` loads for authenticated user.
    - login-link or equivalent user email arrives in Mailpit and is readable.
    - logs have no critical errors.
5. Operator confirms baseline checklist in report (`pass` or `fail` per item).

### Phase 2: Upgrade

1. Switch to target revision (`--target`).
2. Execute deploy workflow steps automatically when `--auto-deploy-steps=true`:
    - `php artisan migrate --no-interaction`
    - explicit backfill parts in production order
    - same backfill command a second time (idempotency)
3. If `--manual-commands-file` is provided, execute those commands in order and log outcomes.
4. If `--pause-before-upgrade-checks` is set, pause and print next-step instructions so operator can run additional manual
   commands.
5. Re-run smoke/manual checks and compare against baseline.

### Phase 3: Gate output

Generate `storage/upgrade-lab/reports/<run-id>/report.md` containing:

- baseline commit and target commit,
- dump reference,
- runtime version snapshot,
- pass/fail status per check,
- unresolved-athlete export path (if present),
- Mailpit verification result (including email subject/time),
- blockers and required follow-up actions.

Exit code rules:

- `0` only when all mandatory checks pass.
- non-zero when any gate check fails.

## 8) Suggested command contract

Primary entrypoint:

```bash
scripts/upgrade-lab \
  --target=<target-sha-or-branch> \
  --dump-path=/absolute/path/to/dump.sql \
  [--baseline=<main-or-live-sha>] \
  [--auto-deploy-steps=true|false] \
  [--pause-before-upgrade-checks] \
  [--manual-commands-file=./scripts/upgrade-lab-manual.txt] \
  [--run-id=<timestamp-or-ticket>]
```

Alternative compatibility mode (recommended while migrating from WP1 script naming):

- `--commit` can remain supported as alias for `--target` until old docs/scripts are updated.

Expected operator experience:

1. Start one command with baseline, target, and dump-path inputs.
2. Perform baseline manual checks in browser + Mailpit.
3. Let scripted deploy workflow run (or disable and run manually).
4. Optionally run extra manual upgrade commands.
5. Perform post-upgrade manual checks in browser + Mailpit.
6. Review generated pass/fail report for go/no-go.

Internally, commands should run through Sail equivalents (for example, `./vendor/bin/sail artisan ...`) to keep
execution consistent.

## 9) Reusability and maintenance policy

1. Keep this lab as a persistent release-engineering asset (not a one-off migration helper).
2. Reuse the same script contract for future schema/data upgrades.
3. Store past reports for auditability and release retrospectives.
4. Review pinned image versions quarterly and after production runtime changes.

## 10) Implementation checklist

1. Add Sail dependency and publish baseline Sail assets if not already present.
2. Introduce dedicated upgrade-lab Compose and Docker artifacts.
3. Add Mailpit service wiring and lab mail environment defaults.
4. Implement `scripts/upgrade-lab` with baseline/target orchestration and structured exit codes.
5. Implement `scripts/upgrade-lab-checks` and report generation (including manual check capture fields).
6. Implement optional automated deploy-step execution + optional manual command hooks.
7. Validate with dump mode using a sanitized production-like dump.
8. Dry-run against live-equivalent commit + current feature commit.
9. Require an attached report before production release approval.

## 11) Work packages (explicit)

### WP1 - Runtime scaffold + Mailpit (implemented)

Scope:

- Provision isolated lab stack with pinned runtime targets (PHP 8.4, Apache 2.4, MariaDB 10.11.16).
- Provide scripted bootstrap entrypoint (`scripts/upgrade-lab`) for dump-based rehearsal.
- Route app mail to Mailpit and expose inbox UI for login-link verification.
- Emit a basic run report with runtime snapshot and Mailpit endpoint details.

Acceptance:

- `scripts/upgrade-lab --commit=<sha> --dump-path=<path>` completes successfully.
- `/` smoke status is successful.
- Report includes PHP/Apache/MariaDB versions and Mailpit connectivity details.

### WP2 - Baseline -> upgrade two-phase orchestration (implemented)

Scope:

- Run baseline revision and target revision in one orchestrated rehearsal.
- Standardize primary flags around `--baseline` + `--target` (keep `--commit` alias temporarily).
- Add explicit pause point between baseline checks and upgrade execution.

Acceptance:

- One command produces baseline and post-upgrade sections in the same report.
- Baseline and target commits are both recorded and compared.

### WP3 - Data source mode (`dump`) (implemented)

Scope:

- Implement dump mode (`--dump-path=...`) with validation and import.
- Add post-import normalization for safe local rehearsal (no real outbound side effects).

Acceptance:

- Dump mode fails fast when `--dump-path` is missing/invalid.
- Dump mode imports successfully and proceeds to checks.

### WP4 - Deploy workflow automation + manual hooks (implemented)

Scope:

- Add `--auto-deploy-steps=true|false` for migration/backfill execution.
- Execute production-order backfill and mandatory idempotency second run when enabled.
- Add optional `--manual-commands-file` and `--pause-before-upgrade-checks` hooks.

Acceptance:

- Automated mode executes workflow steps with logged pass/fail.
- Operator can inject additional manual commands before final checks.

### WP5 - Structured manual checks + gate report (implemented)

Scope:

- Add reusable checklist template for baseline and post-upgrade manual checks.
- Capture required matrix items (`/`, `/fragen-und-antworten`, registration guards, `/admin`, logs, Mailpit email).
- Produce explicit go/no-go report with blockers and unresolved export references.

Acceptance:

- Final report contains per-check pass/fail and reviewer notes.
- Exit code is `0` only when all required gate checks pass.

### WP6 - Hardening and team adoption (implemented)

Scope:

- Add runtime parity details (PHP extension snapshot, selected DB settings, Apache module snapshot).
- Add runbook refinements and troubleshooting playbook.
- Add CI-friendly dump-mode rehearsal path and artifact retention of reports.

Acceptance:

- Team can run repeatable rehearsals with documented recovery steps.
- CI can run a non-interactive dump rehearsal smoke and persist report artifacts.
