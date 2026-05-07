# Upgrade Lab Runbook

This runbook describes how to execute the reusable upgrade rehearsal gate.

## 1) Prerequisites

- Docker engine running
- App dependencies installed (`composer install`, `npm install`)
- Frontend assets built (`npm run build`)
- Flux credentials configured when required by this repository

## 2) Quick start

```bash
scripts/upgrade-lab \
  --baseline=main \
  --target=<target-sha-or-branch> \
  --dump-path=storage/upgrade-lab/dumps/260424-live_data.sql
```

The script runs interactively by default — it pauses after baseline checks and after deploy so you can visually verify
and run any release-specific commands on the container.

## 3) Operator flow

1. Run the command with baseline, target, and dump path.
2. Complete baseline checklist (interactive pass/fail prompts).
3. Press Enter to continue to target phase.
4. Target deploy runs automatically (mirrors production: down, migrate, storage:link, optimize, up).
5. Run any release-specific commands (backfills, data migrations) on the container during the pause.
6. Press Enter to proceed with upgrade checks.
7. Complete target checklist (interactive pass/fail prompts).
8. Review `storage/upgrade-lab/reports/<run-id>/report.md` and gate status.

## 4) Options

- `--baseline=<ref>` — Baseline revision (default: `main`)
- `--run-id=<id>` — Custom run identifier
- `--no-interaction` — Skip all pauses and auto-pass all checklists (for automated testing)
- `--keep-running` — Keep containers and worktree after completion
- `--commit=<ref>` — Alias for `--target`

## 5) Artifacts generated

For each run id, artifacts are stored under `storage/upgrade-lab/reports/<run-id>/`:

- `report.md`
- `target-migrate.log`
- runtime parity snapshots:
    - `*-php-extensions.txt`
    - `*-apache-modules.txt`
    - `*-db-settings.txt`

## 6) Troubleshooting playbook

### Port already in use

- Override exposed ports via env vars:
    - `UPGRADE_LAB_APP_PORT`
    - `UPGRADE_LAB_DB_PORT`
    - `UPGRADE_LAB_MAILPIT_SMTP_PORT`
    - `UPGRADE_LAB_MAILPIT_UI_PORT`

### Vite manifest missing

- Build assets first: `npm run build`

### Flux package auth failures

- Ensure Flux auth is configured for Composer in the environment where you run the lab.

### Dump import fails

- Confirm dump path exists and is readable.
- Confirm dump file is valid SQL and not truncated.
- Re-run with `--keep-running` and inspect DB container logs.

### Gate failed

- Read `report.md` blockers section.
- Inspect target logs in the same report directory.
- Fix issue and rerun with a new `--run-id`.
