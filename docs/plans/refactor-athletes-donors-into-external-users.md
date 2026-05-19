# Refactor Athletes/Donors into External Users

## Context and rollout assumptions

- No concurrent writes during full migration window.
- No new `users`, `external_users`, `athletes`, `donors`, `athlete_registrations`, or `donations` until PR3 merged.
- No athlete or donor login activity until after PR3 merged.
- Public registration, donation creation, donor login, invoice handling, and printable athlete documents are not productive during PR3.
- Each PR merge auto-deploys.
- PR2 operational step is mandatory: run `php artisan hfm:backfill:external-users` immediately after deployment.
- Because assumptions above hold, transition can be strict and fail-fast.

## Goal

Replace split `athletes` + `donors` identity and static token login with unified `external_users`, event-scoped `athlete_registrations`, and passwordless signed-URL auth. End-user behavior should stay effectively same, while model becomes ready for multi-event growth.

Parent plan: `docs/plans/multi-event-restructure-plan.md` (Section B).

Production rehearsal artifact: `storage/upgrade-lab/dumps/2026-05-17_16-44-51.sql`.

---

## Current state

- Identity split across `athletes` and `donors`.
- External login relies on legacy static token routes.
- `donations` references legacy foreign keys (`donor_id`, `athlete_id`).
- Portal behavior depends on legacy models.
- Admin pages read legacy tables.

## Target state (after PR3)

- Identity unified in `external_users` (passwordless signed login, `auth:external`).
- Participation modeled as `athlete_registrations` scoped by `donation_event_id`.
- `donations` linked via `donor_external_user_id` and `athlete_registration_id`.
- Portal and admin read from new model graph.
- Legacy token routes, models, and tables removed.
- Public registration and printable document flows are disabled until rebuilt on top of the new event/group model.
- Donor invoices are not part of this refactor. Future event-scoped invoice work is tracked in GitHub issue #134.

---

## PR1 - Introduce new models and auth foundation

### Steps

- [x] Create `external_users` model + migration (`Authenticatable`, signed-URL login identity, trace columns `legacy_athlete_id`/`legacy_donor_id`).
- [x] Create `athlete_registrations` model + migration with unique (`donation_event_id`, `external_user_id`).
- [x] Extend `donations` with nullable `donor_external_user_id` and `athlete_registration_id` while keeping legacy columns.
- [x] Configure `auth:external` guard + provider.
- [x] Split routes into `routes/admin.php`, `routes/portal.php`, `routes/web.php` with strict guard separation.
- [x] Add external signed login callback at `/portal/login/{uuid}` (controller action).
- [x] Keep `/portal` as placeholder behind `auth:external`.
- [x] Keep legacy token routes fully operational in parallel.

### Exit criteria

- [x] New schema exists and is additive only.
- [x] Guard separation enforced by middleware.
- [x] Signed external login flow works (valid + expired/invalid signature behavior covered).
- [x] Placeholder portal renders without legacy data dependency.

---

## PR2 - Backfill and switch portal reads

PR2 introduces one guarded migration command for this exact production data shape, then flips portal reads.

### Command contract

- [x] Implement `php artisan hfm:backfill:external-users` with `--dry-run`.
- [x] Treat command as one-time guarded migration for this rollout, not generic dedupe engine.
- [x] Execute preflight before any write (including dry-run).
- [x] Write mode runs in one transaction and exits non-zero on violation.
- [x] No report file export; console output only.
- [x] Dry-run repeatable; write-mode rerun intentionally unsupported.

### Preflight assumptions (fail-fast)

- [x] Duplicate normalized donor emails count is `0`.
- [x] Duplicate normalized athlete emails count is `0`.
- [x] Duplicate donation pair count for (`donor_id`, `athlete_id`) is `0`.
- [x] Every athlete has exactly one `donation_event_id`.
- [x] Every donation references existing donor and athlete.
- [x] `external_users`, `athlete_registrations`, and new donation FK columns are empty before write mode.

If any check fails: print blocking counts, exit non-zero, write nothing.

### Backfill mapping rules

- [x] Normalize email with `trim(mb_strtolower($email))`.
- [x] Build `external_users` by normalized email only (no name/address/phone matching).
- [x] Same athlete+donor email becomes one dual-role `external_user`.
- [x] Dual-role merge policy: athlete fields win when both non-empty; fill athlete gaps from donor; keep donor-only `country_of_residence`.
- [x] Preserve migration trace via `legacy_athlete_id` / `legacy_donor_id`.
- [x] Create one `athlete_registration` per legacy athlete with copied event-scoped fields.
- [x] Map each donation to `donor_external_user_id` and `athlete_registration_id`.
- [x] Preserve donation row cardinality (1 legacy row -> 1 new-mapped row).

### Read switch scope

- [x] After successful backfill + validation, portal reads from `ExternalUser`, `AthleteRegistration`, and new donation FKs.
- [x] `LoginForm` normalizes input email and resolves `external_users` first.
- [x] Legacy token routes stop token auth and redirect to `/portal` with no side effects.
- [x] Remove admin token-login shortcuts that point to legacy token routes.
- [x] Keep admin list pages on legacy reads for PR2 (`admin/sportlerinnen`, `admin/spenderinnen`, `admin/spenden`).

### Exit criteria

- [x] Rehearsal completed in upgrade lab using `storage/upgrade-lab/dumps/2026-05-17_16-44-51.sql` in dry-run and write mode.
- [x] Row parity checks pass.
- [x] Amount parity per event/donor passes (event derived via `athlete_registration`).
- [x] Every legacy athlete and donor maps to exactly one `external_user`.
- [x] Every donation resolves to exactly one `athlete_registration`.
- [x] Portal shows merged dual-role data grouped by donation event.
- [x] Legacy token redirects do not authenticate and do not mutate verification state.

---

## PR3 - Remove legacy and complete switch

PR3 is a destructive cleanup. It may remove or disable legacy-dependent runtime behavior instead of preserving every old screen, because the application is intentionally non-productive during this deployment window. Existing invoice state on `donors` may be lost; reusable Webling/invoice integration code should remain where practical for the future `donor_event_invoices` rebuild tracked in GitHub issue #134.

### Steps

- [ ] Enforce NOT NULL on `donations.donor_external_user_id` and `donations.athlete_registration_id`.
- [ ] Ensure delete rule for `donations.donor_external_user_id` is `RESTRICT`.
- [ ] Ensure delete rule for `donations.athlete_registration_id` is `RESTRICT`.
- [ ] Remove legacy donation columns `donor_id`, `athlete_id`.
- [ ] Drop legacy tables `athletes`, `donors`.
- [ ] Remove legacy models `Athlete`, `Donor`.
- [ ] Remove legacy token routes entirely (no transition redirects).
- [ ] Remove legacy login-token config keys.
- [ ] Remove `legacy_athlete_id` / `legacy_donor_id` trace columns.
- [ ] Remove legacy `LoginForm` fallback paths.
- [ ] Remove `hfm:backfill:external-users` command and its tests.
- [ ] Replace admin athlete/donor pages with one simple `external_users` admin datatable generated from `make:datatable`.
- [ ] Remove legacy admin athlete/donor routes, navigation entries, pages, and Livewire table components.
- [ ] Replace legacy donation, athlete-registration, and external-user factories with new-graph defaults.
- [ ] Rewrite default local seeding with two events: one past event and one near-future active event, with external users, athlete registrations, and donations across both.
- [ ] Seed or configure the near-future event as the active/current event for local development.
- [ ] Keep public registration pages disabled; remove remaining legacy Livewire form dependencies only where needed for boot/static-search cleanliness.
- [ ] Disable printable athlete documents that still require legacy athlete token routes.
- [ ] Keep admin replacement simple: external-users list first; event-scoped admin metrics/calculations can wait unless needed for boot.
- [ ] Remove invoice UI entrypoints only; keep reusable invoice/Webling services/jobs/actions parked for issue #134 where they can still autoload cleanly.

### Exit criteria

- [ ] Legacy token route paths return 404.
- [ ] Application boots without runtime dependency on `athletes`, `donors`, `donor_id`, or `athlete_id` outside old migrations.
- [ ] Admin navigation does not expose broken legacy-dependent actions.
- [ ] External passwordless login remains functional through shared login entry.
- [ ] Static search finds legacy table/column references only in old migrations, historical plan text, or explicitly disabled code.
- [ ] Local development seeding produces external users, athlete registrations, and donations for the current event.

---

## Rollback and risk posture

- [ ] PR1 is additive and low risk.
- [ ] PR2 rollback path is read-switch revert to legacy reads if needed.
- [ ] PR3 is clean break and should ship only after PR2 validations pass.

## Out of scope for this refactor

- [ ] Generic cross-environment dedupe engine.
- [ ] New registration flows.
- [ ] Group model and group-level sponsorship behavior.
- [ ] Event-scoped donor invoices (`donor_event_invoices`) and active invoice workflows; tracked in GitHub issue #134.
