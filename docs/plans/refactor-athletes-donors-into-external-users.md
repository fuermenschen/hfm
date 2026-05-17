# Refactor Athletes/Donors into External Users

## Context and rollout assumptions

- No concurrent writes during full migration window.
- No new `users`, `external_users`, `athletes`, `donors`, `athlete_registrations`, or `donations` until PR3 merged.
- No athlete or donor login activity until after PR3 merged.
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

- [ ] Implement `php artisan hfm:backfill:external-users` with `--dry-run`.
- [ ] Treat command as one-time guarded migration for this rollout, not generic dedupe engine.
- [ ] Execute preflight before any write (including dry-run).
- [ ] Write mode runs in one transaction and exits non-zero on violation.
- [ ] No report file export; console output only.
- [ ] Dry-run repeatable; write-mode rerun intentionally unsupported.

### Preflight assumptions (fail-fast)

- [ ] Duplicate normalized donor emails count is `0`.
- [ ] Duplicate normalized athlete emails count is `0`.
- [ ] Duplicate donation pair count for (`donor_id`, `athlete_id`) is `0`.
- [ ] Every athlete has exactly one `donation_event_id`.
- [ ] Every donation references existing donor and athlete.
- [ ] `external_users`, `athlete_registrations`, and new donation FK columns are empty before write mode.

If any check fails: print blocking counts, exit non-zero, write nothing.

### Backfill mapping rules

- [ ] Normalize email with `trim(mb_strtolower($email))`.
- [ ] Build `external_users` by normalized email only (no name/address/phone matching).
- [ ] Same athlete+donor email becomes one dual-role `external_user`.
- [ ] Dual-role merge policy: athlete fields win when both non-empty; fill athlete gaps from donor; keep donor-only `country_of_residence`.
- [ ] Preserve migration trace via `legacy_athlete_id` / `legacy_donor_id`.
- [ ] Create one `athlete_registration` per legacy athlete with copied event-scoped fields.
- [ ] Map each donation to `donor_external_user_id` and `athlete_registration_id`.
- [ ] Preserve donation row cardinality (1 legacy row -> 1 new-mapped row).

### Read switch scope

- [ ] After successful backfill + validation, portal reads from `ExternalUser`, `AthleteRegistration`, and new donation FKs.
- [ ] `LoginForm` normalizes input email and resolves `external_users` first.
- [ ] Legacy token routes stop token auth and redirect to `/portal` with no side effects.
- [ ] Remove admin token-login shortcuts that point to legacy token routes.
- [ ] Keep admin list pages on legacy reads for PR2 (`admin/sportlerinnen`, `admin/spenderinnen`, `admin/spenden`).

### Exit criteria

- [ ] Rehearsal completed in upgrade lab using `storage/upgrade-lab/dumps/2026-05-17_16-44-51.sql` in dry-run and write mode.
- [ ] Row parity checks pass.
- [ ] Amount parity per event/donor passes (event derived via `athlete_registration`).
- [ ] Every legacy athlete and donor maps to exactly one `external_user`.
- [ ] Every donation resolves to exactly one `athlete_registration`.
- [ ] Portal shows merged dual-role data grouped by donation event.
- [ ] Legacy token redirects do not authenticate and do not mutate verification state.

---

## PR3 - Remove legacy and complete switch

### Steps

- [ ] Enforce NOT NULL on `donations.donor_external_user_id` and `donations.athlete_registration_id`.
- [ ] Ensure delete rule for `donations.athlete_registration_id` is `RESTRICT`.
- [ ] Remove legacy donation columns `donor_id`, `athlete_id`.
- [ ] Drop legacy tables `athletes`, `donors`.
- [ ] Remove legacy models `Athlete`, `Donor`.
- [ ] Remove legacy token routes entirely (no transition redirects).
- [ ] Remove legacy login-token config keys.
- [ ] Remove `legacy_athlete_id` / `legacy_donor_id` trace columns.
- [ ] Remove legacy `LoginForm` fallback paths.
- [ ] Switch admin pages to new models and FKs.

### Exit criteria

- [ ] Legacy token route paths return 404.
- [ ] Admin pages work on new model graph with consistent event-scoped counts.
- [ ] External passwordless login remains functional through shared login entry.
- [ ] No remaining runtime dependency on `athletes` or `donors`.

---

## Rollback and risk posture

- [ ] PR1 is additive and low risk.
- [ ] PR2 rollback path is read-switch revert to legacy reads if needed.
- [ ] PR3 is clean break and should ship only after PR2 validations pass.

## Out of scope for this refactor

- [ ] Generic cross-environment dedupe engine.
- [ ] New registration flows.
- [ ] Group model and group-level sponsorship behavior.
