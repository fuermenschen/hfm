# Refactor Athletes/Donors into External Users

> **Constraint: no registrations are currently happening.** Zero concurrent-write risk during migration. No time pressure — refactor incrementally at whatever pace ensures correctness.
>
> **Extended guarantee for this migration window:** No new `users`, `external_users`, `athletes`, `donors`, `athlete_registrations`, or `donations` will be created between now and PR3 merge.

**Goal:** Replace the split `athletes` + `donors` identity + token-auth system with a unified `external_users` model, event-scoped `athlete_registrations`, and proper passwordless auth via signed URLs. After completion, the app behaves identically from a user perspective but is structurally prepared for multi-event features, groups, and future registration flows.

**Parent plan:** `docs/plans/multi-event-restructure-plan.md` — Section B defines the target data model; this document handles execution.

---

## PR1 — New identity schema + auth + landing page

> The new models and auth infrastructure exist and work, but the table is empty. Legacy paths remain fully operational. This PR makes the new world *possible* without disrupting the old.

### Schema + models

- [x] Create `external_users` migration + model (extends `Authenticatable` — no password column, passwordless login via signed URLs like admin `User`). Columns: `id`, `uuid` (unique, for signed URL routes), `first_name`, `last_name`, `address`, `zip_code`, `city`, `country_of_residence`, `phone_number`, `email` (unique), `public_id` (6-char uppercase alphanumeric, globally unique, always auto-generated, displayed as `XXX-XXX` to disambiguate athletes sharing the same privacy name), `remember_token`, timestamps, soft-deletes, `legacy_athlete_id` (nullable, migration trace), `legacy_donor_id` (nullable, migration trace)
- [x] Create `athlete_registrations` migration + model. Columns: `id`, `donation_event_id` FK, `external_user_id` FK, `sport_type_id`, `partner_id` (nullable), `rounds_estimated`, `rounds_done`, `comment`, `verified`, timestamps. Constraints: unique (`donation_event_id`, `external_user_id`)
- [x] Extend `donations` with nullable new columns: `donor_external_user_id`, `athlete_registration_id`. Event scope is derived through `athlete_registration.donation_event_id`; no redundant `donation_event_id` FK on donations. No `group_id` yet — added when groups are implemented. Keep old columns (`donor_id`, `athlete_id`) for compatibility

### Auth infrastructure

- [x] Configure `auth:external` guard + `external_users` provider in `config/auth.php`
- [x] Split route files now: move admin routes to dedicated `routes/admin.php` (`auth:web`) and external portal routes to dedicated `routes/portal.php` (`auth:external`); keep public pages in `routes/web.php`
- [x] Enforce strict guard separation via middleware (not Gates): `auth:external` cannot access admin routes; `auth:web` cannot access external write endpoints
- [x] Add signed-URL login route for external users at `/portal/login/{uuid}` (controller action, no closure business logic), same pattern as admin `User`: `URL::temporarySignedRoute` → `auth()->guard('external')->login()` + session regeneration
- [x] External signed login link TTL is 15 minutes and reusable within TTL (single-use invalidation out of scope)
- [x] Update `LoginForm` to also check `external_users` table (dead path until backfill, but wired and ready). Legacy `Athlete`/`Donor` token lookups remain the active path

### Landing page

- [x] PR1 scope: keep `/portal` as placeholder-only page behind `auth:external` guard (no legacy-data merge, no verified status rendering yet)

### Coexistence

- [x] Legacy `/sportlerinnen/{token}` + `/spenderinnen/{token}` routes remain fully functional
- [x] New `auth:external` routes exist in parallel — no disruption to existing users
- [x] No legacy token-route redirects in PR1; redirects start only in PR2
- [x] No new user-facing writes during this phase (no new `users`, `external_users`, `athletes`, `donors`, `athlete_registrations`, `donations`)

---

## PR2 — Backfill + switch reads to new models

> All legacy data lives in the new schema. The app reads from new models. Legacy tables become read-only fallback.

### Identity map (`athletes` + `donors` → `external_users`)

- [ ] Build index of normalized emails across `athletes` + `donors` (`donors.email` may duplicate)
- [ ] Unique emails across both tables → create one `external_users` row + attach all matching
- [ ] Multiple `donors` with same email:
  - [ ] Name + address identical → auto-merge, record all `legacy_donor_id`s
  - [ ] Name or address differ → "donor-email-conflicts" manual-review CSV; resolve before hard cutover
- [ ] Unmatched rows → create `external_users` keyed by name, address, phone
- [ ] Persist mapping via `legacy_athlete_id`/`legacy_donor_id` trace columns on `external_users`

### Backfill participations

- [x] `donation_event` for each legacy athlete: `athletes.donation_event_id` backfilled via `BackfillAthleteEventAssignmentsCommand`
- [ ] For each legacy athlete: create one `athlete_registration` from resolved `donation_event_id` + copy event-scoped fields (`rounds_estimated`, `rounds_done`, `verified`, legacy `partner_id`)

### Backfill donations

- [ ] For each legacy donation:
  - [ ] Map `donor_id` → `donor_external_user_id`
  - [ ] Map `athlete_id` → `athlete_registration_id`

### Backfill command

- [ ] Idempotent Artisan command `hfm:backfill:external-users` with `--dry-run` + report output (same pattern as `hfm:backfill:event-content`)
- [ ] Validation checks after backfill: row count parity, amount parity per event/donor (event derived via athlete registration), every donation resolves to exactly one athlete registration, every legacy row mapped to exactly one external user

### Switch reads

- [ ] After backfill + validation: switch app reads to new models (`ExternalUser`, `AthleteRegistration`, donation queries via `donor_external_user_id`/`athlete_registration_id`)
- [ ] `LoginForm` resolves `external_users` first; falls back to legacy `Athlete`/`Donor` token lookups
- [ ] External-user landing page now shows real backfilled data
- [ ] Build simple external-user landing page (combination of current athlete + donor detail views: greeting, list of donations-as-donor, list of donations-as-athlete, verified status)
- [ ] Dual-role users see both athlete and donor sections in one page
- [ ] Legacy token routes (`/sportlerinnen/{token}`, `/spenderinnen/{token}`) redirect to `/portal` — the same content is available there via `auth:external`
- [ ] Welcome/registration notifications updated: send signed-URL login link (same pattern as admin) instead of legacy `/sportlerinnen/{token}` and `/spenderinnen/{token}` links (code-only change — no registrations happening currently)
- [ ] QR codes in welcome letters encode the login page URL (no tokens in QR codes)
- [ ] Legacy tables/columns become read-only fallback
- [ ] Admin views (`admin/sportlerinnen`, `admin/spenderinnen`, `admin/spenden`) continue reading legacy tables during PR2 — admin rework happens in PR3 when legacy tables are dropped

---

## PR3 — Remove legacy

> Clean break. No legacy code, tables, or token routes remain.

- [ ] Enforce NOT NULL on new FKs (`donations.donor_external_user_id`, `donations.athlete_registration_id`)
- [ ] Ensure `donations.athlete_registration_id` uses `RESTRICT` so athlete registrations with donations cannot be deleted
- [ ] Remove old nullable columns from `donations`: `donor_id`, `athlete_id`
- [ ] Drop legacy tables: `athletes`, `donors`
- [ ] Remove legacy models: `Athlete`, `Donor`
- [ ] Remove legacy token routes entirely (redirects from PR2 replaced with 404/removed)
- [ ] Replace temporary transition guard in `Donation::boot()->created` with final new-schema flow (no legacy `donor` / `athlete` assumptions)
- [ ] Remove `login_token` and `login_token_expiry_days` config
- [ ] Remove `legacy_athlete_id`/`legacy_donor_id` trace columns from `external_users`
- [ ] Remove legacy `LoginForm` fallback paths (only `external_users` lookup remains)
- [ ] Switch admin views to new models (`admin/sportlerinnen` → athlete_registrations scoped by event, `admin/spenderinnen` → external_users with donor role, `admin/spenden` → donations via new FKs)
- [ ] Login auditability: covered by `spatie/laravel-activitylog` (GH #111)

---

## Rollback + integrity

- [ ] Rollback path: PR2 can revert to legacy reads; PR1 new schema is additive (no legacy disruption)
- [ ] Integrity checks: row count parity, amount parity per event/donor (event derived via athlete registration), every donation resolves to exactly one athlete registration, every legacy row mapped to exactly one external user

---

## Verification

### PR1 — Schema + auth

- [x] `external_users` uniqueness/dedup behavior
- [x] `athlete_registrations` unique per (`event`, `external_user`)
- [x] External login resolves one portal for dual-role user
- [x] External guard **cannot** access admin routes (enforced by middleware, tested per route)
- [x] Internal guard **cannot** access external write endpoints
- [x] Route split verified: admin routes are loaded from `routes/admin.php`, portal routes from `routes/portal.php`, and public routes from `routes/web.php`
- [x] External signed-login callback uses `/portal/login/{uuid}` controller action; signed URL expiry and invalid signature are tested
- [x] Placeholder `/portal` page renders for authenticated external users without legacy athlete/donor dependency

### PR2 — Backfill + switch reads

- [ ] Backfill parity checks for counts and sums
- [ ] Conflict fixture test (same email, differing names) requires manual resolution path
- [ ] Dry-run migration reports identity merge decisions
- [ ] 100% legacy athlete/donor rows mapped to exactly one external user
- [ ] Per-event financial totals match baseline
- [ ] Donor can sponsor same athlete in different events
- [ ] Event-scoped results/dashboard totals are isolated
- [ ] Portal page switches from placeholder to real merged donor/athlete data after backfill

### PR3 — Legacy removal

- [ ] Old token routes (`/sportlerinnen/{token}`, `/spenderinnen/{token}`) return 404
- [ ] Admin views work with new models (athlete_registrations, external_users, donations via new FKs)
- [ ] Admin pages remain internal-only; event switching yields consistent counts
- [ ] Invoice generation works with event filter
- [ ] External auth uses same passwordless login page as admin users (email → signed URL) with `auth:external` guard; mandatory guard separation enforced
