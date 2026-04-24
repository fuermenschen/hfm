# Upgrade Lab Runbook

This runbook describes how to execute the reusable upgrade rehearsal gate.

## 1) Prerequisites

- Docker engine running
- App dependencies installed (`composer install`, `npm install`)
- Frontend assets built (`npm run build`)
- Flux credentials configured when required by this repository

## 2) Quick start (dump-only)

```bash
scripts/upgrade-lab \
  --baseline=main \
  --target=<target-sha-or-branch> \
  --dump-path=storage/upgrade-lab/dumps/260424-live_data.sql \
  --baseline-checklist-file=storage/upgrade-lab/checklists/baseline.pass.env \
  --target-checklist-file=storage/upgrade-lab/checklists/target.pass.env
```

## 3) Operator flow

1. Run the command with baseline, target, and dump path.
2. Complete manual baseline checks and mark them in checklist file.
3. Let deploy automation run (or disable via `--auto-deploy-steps=false`).
4. Optionally execute extra commands with `--manual-commands-file`.
5. Complete manual target checks and mark checklist.
6. Review `storage/upgrade-lab/reports/<run-id>/report.md` and gate status.

## 4) Checklist format

Checklist keys are documented in `docs/upgrade-lab-checklist.md`.

Each checklist value must be `pass` or `fail`:

```text
home=pass
faq=pass
registration_guards=pass
admin=pass
logs=pass
mailpit_email=pass
notes=
```

## 5) Artifacts generated

For each run id, artifacts are stored under `storage/upgrade-lab/reports/<run-id>/`:

- `report.md`
- baseline and target checklist templates
- migrate/backfill logs (when deploy automation enabled)
- manual command logs (when provided)
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

## 7) CI usage

Use the `upgrade-lab-ci` workflow to run dump-mode rehearsal smoke and upload report artifacts.
Provide a sanitized dump URL through the workflow `dump_url` input.
Optionally set `baseline_ref` (defaults to `main`).

Important:

- CI uses placeholder checklist files for non-interactive smoke runs.
- Manual checklist verification is still required in human-operated release rehearsals.
