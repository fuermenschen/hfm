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

The script is an interactive wizard. It always pauses after baseline checks and after target deploy so you can verify and run release-specific commands (backfills, data migrations) directly on the container.

Key options:

- `--no-interaction` — skip all pauses and auto-pass checklists (for automated testing)
- `--keep-running` — keep containers and worktree after completion
- `--run-id=<id>` — custom run identifier

Checklist items (prompted interactively for pass/fail): public_pages, faq, registration_guards, mailpit_email, admin_pages, logs. Full list documented in `docs/upgrade-lab-runbook.md`.

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

- `vendor/bin/pint --dirty --blade`
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
