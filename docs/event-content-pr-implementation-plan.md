# Event Content PR Implementation Plan

This plan covers the event-content slice of `docs/multi-event-restructure-plan.md`.

The goal remains split into two concerns:

1. Refactor hardcoded event-facing content into DB-driven models and relations.
2. Perform a one-time production backfill so the public site keeps the same behavior after code cutover.

---

## 1) Scope Snapshot (Current Branch State)

### 1.1 Implemented already

- Schema rollout for global catalog models and pivots:
    - `sponsors`, `faqs`, event-content pivots, partner event fields, equal-split support.
- Migration cleanup:
    - `2026_03_24_151421_add_donation_event_id_to_athletes_table.php` is now schema-only.
- Event-content backfill command with parts:
    - `php artisan hfm:backfill:event-content` in `app/Console/Commands/BackfillAthleteEventAssignmentsCommand.php`.
- Backfill seeders created for partners/sponsors/faqs/sport types.
- Public rendering switch completed for DB-driven event content:
    - home hero partners, home beneficiary content, sponsors section, FAQ page.
- FAQ fallback semantics fixed:
    - no cross-event leakage,
    - global fallback FAQs are only truly unassigned FAQs.
- Asset rollout safety implemented:
    - `content-assets` is now recommended in non-interactive mode when target files are missing.
- FAQ backfill idempotency hardened with stable event/group/sort slot mapping.
- FAQ extractor cleanup completed:
    - one-off extractor removed from runtime codebase.
- Tests added/updated for integration, migration behavior, command behavior, and rendering parity.

### 1.2 Still open in this PR

- Implement admin list-only datatables/pages for partners/sponsors/faqs (if kept in this PR scope).
- Build reusable upgrade simulation environment (commit-selectable + dump-only data mode).
- Verify and document runtime parity for rehearsal environment:
    - PHP version,
    - MariaDB version,
    - enabled PHP extensions,
    - supporting service versions.
- Execute full rehearsal gate and record go/no-go output.

### 1.3 Out of scope (unchanged)

- No admin create/edit/delete UI.
- No upload UI.
- No activity log integration.
- No event scope dropdown in admin dashboard.
- No full registration-form architecture rewrite.
- No external user identity refactor in this PR.

---

## 2) Locked Decisions

### 2.1 Data model

- `partners`, `sponsors`, `faqs`, `sport_types` are global catalogs.
- Event scoping is via pivots (not direct `donation_event_id` on catalog tables):
    - `donation_event_partner`
    - `donation_event_sponsor`
    - `donation_event_faq`
    - `donation_event_sport_type`

Pivot-owned event presentation fields:

- partner: `sort_order`, `is_published`
- sponsor: `size`, `contribution_text`, `sort_order`, `is_published`
- faq: `group`, `sort_order`, `is_published`
- sport type: `sort_order`, `is_enabled`

### 2.2 Asset storage

- DB stores filenames only:
    - `partners.logo_light_filename`
    - `partners.logo_dark_filename`
    - `sponsors.logo_filename`
- Runtime URL resolution:
    - `storage/partners/{filename}`
    - `storage/sponsors/{filename}`

### 2.3 Migration vs data logic

- Keep migrations schema-only.
- Keep content and one-time data movement in backfill command/seeders.
- All backfill operations must be idempotent and safe to run multiple times.

---

## 3) Backfill Strategy (One Command, Selectable Parts)

Command:

- `php artisan hfm:backfill:event-content`

Parts:

- `events`
- `athlete-assignments`
- `content-assets`
- `content-partners`
- `content-sponsors`
- `content-faqs`
- `content-sport-types`

Current behavior target:

- Interactive: allow explicit part selection, preselect recommendations.
- Non-interactive: run recommendations by default unless explicit `--part` or `--all`.

### 3.1 Operational requirement for production

For production rollout, run explicit parts in order (not implicit recommendations), so execution is deterministic:

1. `events`
2. `athlete-assignments`
3. `content-assets`
4. `content-partners`
5. `content-sponsors`
6. `content-faqs`
7. `content-sport-types`

Example:

- `php artisan hfm:backfill:event-content --part=events --part=athlete-assignments --part=content-assets --part=content-partners --part=content-sponsors --part=content-faqs --part=content-sport-types --no-interaction`

---

## 4) Remaining Work Checklist (Blocking vs Nice-to-Have)

### 4.1 Blocking before merge/deploy

1. Upgrade simulation environment
    - Create reusable isolated environment with:
        - selectable app revision (`--commit=<sha-or-branch>`),
        - mandatory dump input (`--dump-path=...`),
        - optional dump path (`--dump-path=...`).

2. Runtime parity validation
    - Check and document exact production-aligned versions:
        - PHP,
        - MariaDB,
        - PHP extensions,
        - queue/cache/mail dependencies used in production.

3. Full rehearsal execution + sign-off
    - Baseline on live/main commit.
    - Upgrade to target branch commit.
    - Migrate + run explicit backfill parts + rerun for idempotency.
    - Complete manual matrix and store a concise go/no-go report.

### 4.2 Strongly recommended in this PR

1. Add admin list-only pages for:
    - partners
    - sponsors
    - faqs

2. Add admin access tests + rendering tests for these pages.

3. Keep these as a separate commit set so they can be deferred without blocking release rehearsal.

### 4.3 Can be follow-up if needed

- Additional UX improvements for content management.

---

## 5) Deployment Strategy

### 5.1 Decision

Use one coordinated deployment for this slice.

Reason:

- Public read paths now depend on event-content relations.
- Splitting schema and backfill across separate releases increases risk of empty sections or broken assets.

### 5.2 Production rollout order

1. Deploy code.
2. Run migrations.
3. Run explicit backfill command with all required parts.
4. Run post-deploy smoke checks.
5. Keep export/report artifact from athlete-assignment backfill for audit.

### 5.3 Rollback posture

- Code rollback remains possible if needed.
- Backfill is additive/idempotent; avoid destructive backfill flags by default in production (`--delete-unresolved` off).
- Keep unresolved-athlete export for manual follow-up.

### 5.4 Release runbook (copy/paste)

Use this sequence during the production release window.

#### Pre-flight

1. Confirm app is on the intended release commit.
2. Confirm database backup completed.
3. Confirm `storage/app/public` is writable.
4. Confirm `public/storage` symlink exists (`php artisan storage:link` if missing).

#### Deploy + migrate

```bash
php artisan migrate --force
```

Expected signal:

- Migration output completes with no failed migration.

#### Run one-time event-content backfill (explicit parts)

```bash
php artisan hfm:backfill:event-content \
  --part=events \
  --part=athlete-assignments \
  --part=content-assets \
  --part=content-partners \
  --part=content-sponsors \
  --part=content-faqs \
  --part=content-sport-types \
  --no-interaction
```

Expected signals:

- Command exits successfully.
- Output includes per-part completion.
- If unresolved athletes exist, export path is printed (under `storage/app/exports/...`).

#### Immediate smoke checks

```bash
php artisan tinker --execute='echo \App\Models\DonationEvent::query()->whereIn("slug", ["2025", "2026"])->count();'
php artisan tinker --execute='echo \Illuminate\Support\Facades\DB::table("donation_event_partner")->count();'
php artisan tinker --execute='echo \Illuminate\Support\Facades\DB::table("donation_event_sponsor")->count();'
php artisan tinker --execute='echo \Illuminate\Support\Facades\DB::table("donation_event_faq")->count();'
```

Expected minimums:

- canonical events exist (`2025`, `2026`).
- pivot tables are non-empty for deployed content.

#### HTTP/UI smoke checks

1. Open `/` and verify partner/sponsor sections render as expected for the configured current event.
2. Open `/fragen-und-antworten` and verify event-specific FAQ timing text and grouped FAQ rendering.
3. Open `/admin` (authenticated) and verify dashboard loads.

#### Idempotency confirmation (same release window)

Run the same backfill command a second time:

```bash
php artisan hfm:backfill:event-content \
  --part=events \
  --part=athlete-assignments \
  --part=content-assets \
  --part=content-partners \
  --part=content-sponsors \
  --part=content-faqs \
  --part=content-sport-types \
  --no-interaction
```

Expected signal:

- No duplication side effects; command still exits successfully.

#### If release fails

1. Do not run `--delete-unresolved`.
2. Preserve unresolved export JSON.
3. Roll back app code if required.
4. Restore DB from backup only if strictly necessary and coordinated.

---

## 6) Reusable Upgrade Simulation Environment (Required Gate)

Before production release, run a full rehearsal in an isolated environment that can be reused for future migrations.

### 6.1 Objective

Provide a one-command (or scripted) environment where you can choose:

1. Which application revision to boot (`main`, feature branch, specific commit SHA).
2. Which production-like dump to use (`--dump-path=...`, sanitized as needed).

This environment is the mandatory pre-deploy validation gate for this migration.

### 6.2 Requirements

- Environment must be reproducible (Docker Compose or equivalent is acceptable).
- Runtime versions must be explicitly pinned and documented:
    - PHP version
    - MariaDB version
    - PHP extensions (installed + enabled)
    - Node/npm versions if frontend build is part of release flow
    - queue/cache/mail service dependencies used in production
- Include a small bootstrap script with parameters like:
    - `--commit=<sha-or-branch>`
    - `--dump-path=<path-to-dump.sql>`
    - `--dump-path=<path>` (required when `dump` is selected)

### 6.3 Rehearsal flow (must pass)

1. **Baseline phase (current live behavior)**
    - Boot environment at current `main` (or exact live commit).
    - Load selected data source.
    - Run baseline smoke/manual checks and record results.

2. **Upgrade phase (target branch)**
    - Switch environment to target feature commit.
    - Run migrations.
    - Run explicit event-content backfill parts in production order.
    - Run the same backfill command a second time (idempotency).
    - Re-run smoke/manual checks and compare with baseline.

3. **Go/No-Go output**
    - Produce a short outcome report:
        - pass/fail per check,
        - unresolved-athlete export path (if any),
        - blockers requiring fixes before production.

### 6.4 Manual review matrix (minimum)

- Home page (`/`): hero copy, partners, sponsors, logos.
- FAQ page (`/fragen-und-antworten`): grouping, event-specific entries, fallback behavior.
- Registration pages: guard behavior with/without active event.
- Admin dashboard: loads successfully for authenticated user.
- Logs: no critical migration/backfill/runtime exceptions.

### 6.5 Persistence policy

- Preferred: keep this environment and scripts as a reusable "upgrade lab" for future releases.
- If temporary in first iteration, retain scripts and docs so the environment can be recreated quickly.

---

## 7) Commit Slicing for Manageability

To keep review and conflict handling manageable, split by concern:

1. Docs-only commit(s).
2. Schema/model/factory commit(s).
3. Backfill command + seeders + migration cleanup.
4. Public rendering switch + related tests.
5. Admin datatables + admin tests.
6. Optional/unrelated dependency lockfile updates in separate commit.

This structure improves cherry-picking/reverts and reduces blast radius during review.

Current status:

1. Docs-only commit(s): done.
2. Schema/model/factory commit(s): done.
3. Backfill command + seeders + migration cleanup: done.
4. Public rendering switch + related tests: done.
5. Admin datatables + admin tests: done (implemented as a separate, non-committed change set in this branch).
6. Lockfile updates: done as separate chore commit.

---

## 8) Verification Checklist

### 8.1 Build/test

- `php artisan migrate:fresh --seed --no-interaction`
- `php artisan test --compact` (or targeted suites for touched areas)

### 8.2 Backfill idempotency

- Run event-content backfill command twice.
- Confirm second run does not duplicate pivot or catalog rows.
- Confirm unresolved export behavior is stable.

### 8.3 Runtime parity

- `/` renders correct partners/sponsors for current event.
- `/fragen-und-antworten` renders event-correct FAQs plus only valid global fallback.
- Logos resolve from `storage/app/public/partners` and `storage/app/public/sponsors`.

### 8.4 Admin

- Admin pages remain auth-protected.
- New list pages (if included) render seeded/backfilled content.

### 8.5 Upgrade simulation gate

- Rehearsal completed in isolated environment using:
    - baseline commit (`main`/live commit), and
    - target feature commit.
- Dump-only rehearsal path tested and documented.
- Runtime versions (PHP, MariaDB, extensions, etc.) verified against production and documented.
- Baseline vs upgraded manual check matrix reviewed and signed off.

---

## 9) Definition of Done for This PR

This PR is done when:

1. Public content sections are DB-driven with parity to current production behavior.
2. Backfill command is safe, rerunnable, and operationally documented for one-time production migration.
3. Migrations remain schema-only.
4. Key regressions are covered by tests.
5. Upgrade simulation rehearsal passes in isolated environment with documented runtime parity.
6. Remaining out-of-scope architecture work stays in `docs/multi-event-restructure-plan.md`.
