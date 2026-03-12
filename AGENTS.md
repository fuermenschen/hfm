# AGENTS.md

Guide for coding agents operating in this repository.
This file captures build/lint/test commands and coding conventions.

## 1) Project Snapshot

- Stack: Laravel 11, PHP 8.4, Livewire 3, Flux UI.
- Frontend: Vite 5, Tailwind CSS v3, Prettier.
- Testing: Pest (`php artisan test`) + Playwright (`npm run e2e`).
- Static analysis: PHPStan/Larastan (level 5).
- Key paths: `app/`, `resources/`, `routes/`, `tests/`, `e2e/`.

## 2) Rule Files

- Cursor rules: none found.
- `.cursorrules` not present.
- `.cursor/rules/` not present.
- Copilot instructions exist: `.github/copilot-instructions.md`.

Copilot rules to apply in practice:

- Follow existing conventions and sibling-file patterns.
- Reuse existing components/services before creating new ones.
- Prefer Laravel-native patterns (`artisan make:*`, Form Requests, Eloquent relations).
- Use explicit parameter types and return types.
- Use braces for all control structures.
- Use Pest tests; do not remove tests without explicit approval.
- Run targeted tests first, then broaden as needed.
- Use Flux/Tailwind patterns already in this codebase.
- Use `config()`, not `env()`, outside config files.
- Do not add dependencies or top-level folders without approval.

## 3) Setup Commands

Initial local setup:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
```

Install Playwright browsers when needed:

```bash
npm run e2e:install
```

## 4) Build, Lint, Test Commands

Development:

- Start dev assets: `npm run dev`
- Build assets: `npm run build`

Formatting / linting:

- Format PHP: `vendor/bin/pint`
- Format changed PHP only: `vendor/bin/pint --dirty`
- Static analysis: `vendor/bin/phpstan analyse`
- Run Prettier: `npm run prettier -- .`

PHP tests (Pest via Artisan):

- Run all tests: `php artisan test`
- Run in parallel: `php artisan test --parallel`
- Run one file: `php artisan test tests/Feature/Livewire/BecomeAthleteFormTest.php`
- Run one test name: `php artisan test --filter="allows requests with a valid API key"`
- Run class/pattern: `php artisan test --filter=ApiKeyTest`

Playwright E2E:

- Run all E2E: `npm run e2e`
- Run headed: `npm run e2e:headed`
- Run one spec: `npx playwright test e2e/homepage.spec.mjs`
- Run by title: `npx playwright test -g "hero section"`
- Run one project: `npx playwright test --project="Desktop"`

Recommended pre-handoff checks:

```bash
vendor/bin/pint --dirty
npm run build
vendor/bin/phpstan analyse
php artisan test --parallel
npm run e2e
```

## 5) Formatting Rules

Source of truth: `.editorconfig`, Pint, and Prettier.

- Charset: UTF-8.
- Line endings: CRLF (do not normalize to LF).
- Indentation: 4 spaces by default.
- YAML indentation: 2 spaces.
- Max line length target: 120.
- Keep final newline at EOF.

## 6) PHP Style Guidelines

Imports and namespaces:

- Declare namespace first, then `use` imports.
- Prefer imports over inline fully-qualified names.
- Remove unused imports.

Types and signatures:

- Add explicit param and return types.
- Use nullable types only when null is valid domain behavior.
- Prefer constructor property promotion.
- Avoid empty constructors.

Naming conventions:

- Classes: PascalCase (`DonorService`, `InvoiceCreateData`).
- Methods/variables: camelCase (`collectInvoiceData`, `$partnerId`).
- Persistence fields may stay snake_case where schema-aligned.
- Branch naming: `<prefix>/<kebab-case-description>` (`CONVENTIONS.md`).

Control flow and structure:

- Always use braces.
- Keep methods focused and reasonably small.
- Extract reusable logic to services/actions.

Validation, data access, error handling:

- Prefer Form Request classes for HTTP validation.
- In Livewire, follow existing validation attribute patterns.
- Keep validation/UI text clear and consistent with German-first tone.
- Prefer Eloquent relationships/query builder over raw SQL.
- Prevent N+1 issues with eager loading (`with`, `load`).
- Add relationship return types (`HasMany`, `BelongsTo`, etc.).
- Catch specific exceptions when practical.
- Log failures with context (IDs/key fields), never secrets.
- Return appropriate HTTP status codes for API/middleware responses.

## 7) Livewire / Blade / Frontend

- Prefer Flux components first; fallback to Blade components only as needed.
- Keep Livewire state server-driven and validate all actions.
- Use `wire:key` inside loops.
- Use Livewire v3 event style (`$this->dispatch()`).
- Follow existing Tailwind utility/class composition patterns.

## 8) Testing Conventions

- Write tests in Pest style (`it`, `test`).
- Keep tests under `tests/Feature` and `tests/Unit`.
- Run the smallest relevant test scope first.
- Cover happy, failure, and edge cases for changed behavior.
- Do not remove unrelated tests.

## 9) Agent Handoff Checklist

Before finishing:

1. Run targeted tests for changed behavior.
2. Run `vendor/bin/pint --dirty` for PHP edits.
3. Run `vendor/bin/phpstan analyse` for non-trivial PHP logic changes.
4. Run `npm run build` for frontend-impacting changes.
5. Run selective Playwright tests for changed UI flows.
6. Keep edits minimal and avoid unrelated refactors.

## 10) Quick References

- `.github/copilot-instructions.md`
- `CONVENTIONS.md`
- `DEVELOPMENT_SETUP.md`
- `tests/Pest.php`, `tests/TestCase.php`
- `.editorconfig`, `.prettierrc`, `phpstan.neon`, `phpunit.xml`, `playwright.config.mjs`
