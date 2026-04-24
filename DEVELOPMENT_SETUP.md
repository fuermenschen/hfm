# Development Setup

This project uses Laravel 12, Livewire 4, Flux UI, Tailwind CSS 4, Pest 4, and Playwright.

## Prerequisites

- PHP 8.4+
- Node.js 22+
- Composer
- npm
- SQLite
- Laravel Herd (recommended local web server)

## Local Setup

```bash
git clone https://github.com/fuermenschen/hfm.git
cd hfm

composer install
npm install

# Generate local Boost config/guideline files
php artisan boost:install

cp .env.example .env
php artisan key:generate

php artisan migrate --seed

# Start the frontend asset watcher
npm run dev
```

The app is served by Herd at `https://hfm.test`.

## Optional Local Services

### Mailpit

Mailpit is recommended for local email testing.

- Install via the official docs: [mailpit.axllent.org/docs/install](https://mailpit.axllent.org/docs/install/)
- Start it in a separate terminal: `mailpit`
- Open the inbox UI: `http://localhost:8025`

### Upgrade lab (dump-only gate)

Upgrade rehearsals are dump-only. Provide a SQL dump for every run:

```bash
scripts/upgrade-lab --baseline=main --target=<feature-sha-or-branch> --dump-path=storage/upgrade-lab/dumps/260424-live_data.sql
```

WP4 options for upgrade execution:

- Auto-run target deploy workflow (migrate + event-content backfill + idempotency rerun): `--auto-deploy-steps=true`
- Inject additional manual upgrade commands from file: `--manual-commands-file=storage/upgrade-lab/manual-commands.txt`
- Pause before target checks: `--pause-before-upgrade-checks`

WP5 manual gate checklist:

- Provide checklist result files with:
    - `--baseline-checklist-file=<path>`
    - `--target-checklist-file=<path>`
- Checklist format is documented in `docs/upgrade-lab-checklist.md`.
- Full operations/troubleshooting runbook: `docs/upgrade-lab-runbook.md`.

CI-friendly rehearsal:

- Workflow: `.github/workflows/upgrade-lab-ci.yml`
- It runs a dump-mode **smoke** rehearsal and uploads artifacts from `storage/upgrade-lab/reports/`.
- Start it with `dump_url` pointing to a sanitized SQL dump.
- Optionally pass `baseline_ref` (defaults to `main`).
- CI uses placeholder checklist files; required manual gate verification remains a human release step.

This command boots a production-parity runtime baseline for rehearsal work (PHP 8.4, Apache 2.4, MariaDB 10.11.16)
and writes a run report to `storage/upgrade-lab/reports/<run-id>/report.md`.

Upgrade-lab mail is routed to Mailpit so you can inspect login-link emails during checks:

- Mailpit UI: `http://localhost:18025`
- Mailpit SMTP: `localhost:11025`

## Recommended Workflow

Before opening a pull request, run:

```bash
composer precommit
```

`composer precommit` runs:

- `vendor/bin/pint --dirty`
- `npm run build`
- `vendor/bin/phpstan`
- `php artisan test --parallel`
- `npm run e2e`

Note: Playwright may require one-time browser installation on a new machine:

```bash
npm run e2e:install
```

## Editor Setup (VS Code)

Recommended extensions:

- Laravel
- Better Pest
- Pest Snippets
- PHP Intelephense
- Prettier
- Tailwind CSS IntelliSense
- DotENV
