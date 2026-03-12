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
