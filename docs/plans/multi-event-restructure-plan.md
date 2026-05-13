# Multi-Event / Multi-Year Restructure Plan

## A) Previous State

Single-event system. `athletes` and `donors` are flat identity+participation rows with no event scope. Participation fields (`rounds_estimated`, `rounds_done`, `verified`) on person row, not per-event. Donations link `donor_id` + `athlete_id` globally. Partners selected per-athlete globally (`partner_id`), not per-event. Auth uses vulnerable plain-text token routes: `/sportlerinnen/{token}` + `/spenderinnen/{token}` — same human with both roles gets two tokens, no proper login page. Reporting aggregates across all events. Event details hardcoded in views/jobs.

Highest-risk coupling: `rounds_done` on athlete breaks per-year results. Partner selection conflicts with per-event requirement. Duplicate-donation prevention is global (`donor_id + athlete_id`). Invoice totals derive from global athlete fields — cross-year leakage risk. Vulnerable plain-text token auth (`/sportlerinnen/{token}` + `/spenderinnen/{token}`) creates UX/security issues for dual-role humans and exposes tokens in URLs/notifications.

> Transitional state (`donation_events` + pivots already shipped): `athletes.donation_event_id` FK added; `donation_events.content` JSON drives hero/SEO/invoice metadata; `CurrentDonationEventService` resolves active event; `BecomeAthleteForm` uses event-scoped partner list + equal-split flag; `Results` groups by event for equal-split. Remaining gaps: `athlete_welcome_letter.blade.php` still has hardcoded 2025 date; admin tables show event column but no filter; services still aggregate globally.

---

## B) Target Data Model

> Unify athlete/donor identity into **external_users**, keep admins (`users`) separate.

### Core entities

> Partners, sponsors, faqs, sport_types are **global catalogs** — identity lives outside any single event; event membership is pivot-only.

1. **`donation_events`** (shipped):
   - `id`, `slug` (unique), `title`
   - `starts_at` (`datetimeTz`), `ends_at` (`datetimeTz`)
   - `registration_opens_at` (`datetimeTz`, nullable), `athlete_registration_closes_at` (`datetimeTz`, nullable), `donor_registration_closes_at` (`datetimeTz`, nullable)
   - `location_name` (nullable), `location_street` (nullable), `location_postal_code` (nullable string), `location_city` (required), `location_url` (nullable)
   - `is_published` (`boolean`, default `false`) — controls public visibility / current-event eligibility
   - `content` (`json`, nullable) — event-scoped rich text markdown snippets (hero copy, homepage intro/body, SEO text, invoice metadata fallback), safe rendering + app fallbacks
   - `timezone` (defaults `Europe/Zurich`)
   - `has_equal_split_option` (`boolean`, default `true`) — controls whether equal-split selection is offered
   - Timestamps
   - Constraints: `slug` unique; index `starts_at`, `ends_at`, `athlete_registration_closes_at`, `donor_registration_closes_at`; DB check `ends_at > starts_at`; DB check registration windows coherent (`registration_opens_at <= athlete_registration_closes_at` + `registration_opens_at <= donor_registration_closes_at`)
   - On delete: `RESTRICT` — cannot delete event with related registrations/donations

2. **`partners`** (shipped, global catalog):
   - `id`, `name` (unique), `logo_light_filename`, `logo_dark_filename`, `beneficiary_blurb`, `url`, timestamps
   - Event membership + event-specific presentation → pivot `donation_event_partner`

3. **`sponsors`** (shipped, global catalog):
   - `id`, `name` (unique), `description`, `logo_filename`, `url`, timestamps
   - Event membership + event-specific presentation/funding metadata → pivot `donation_event_sponsor`

4. **`faqs`** (shipped, reusable content):
   - `id`, `title`, `content_md`, timestamps
   - Event membership + event-specific ordering/group/publication → pivot `donation_event_faq`
   - FAQs without pivot rows = globally visible fallback

5. **`donation_event_partner`** (shipped pivot):
   - `id`, `donation_event_id`, `partner_id`, `sort_order`, `is_published`, timestamps
   - Constraints: unique (`donation_event_id`, `partner_id`); index (`donation_event_id`, `is_published`, `sort_order`)

6. **`donation_event_sponsor`** (shipped pivot):
   - `id`, `donation_event_id`, `sponsor_id`, `size`, `contribution_text`, `sort_order`, `is_published`, timestamps
   - Constraints: unique (`donation_event_id`, `sponsor_id`); index (`donation_event_id`, `is_published`, `sort_order`)

7. **`donation_event_faq`** (shipped pivot):
   - `id`, `donation_event_id`, `faq_id`, `group`, `sort_order`, `is_published`, timestamps
   - Constraints: unique (`donation_event_id`, `faq_id`); index (`donation_event_id`, `group`, `is_published`, `sort_order`)

8. **`donation_event_sport_type`** (shipped pivot):
   - `id`, `donation_event_id`, `sport_type_id`, `sort_order`, `is_enabled`, timestamps
   - Constraints: unique (`donation_event_id`, `sport_type_id`); index (`donation_event_id`, `is_enabled`, `sort_order`)

9. **`external_users`** (new, replaces split person identity, extends `Authenticatable`):
      - `id`, `uuid` (unique, for signed URL routes — same pattern as admin `User`), `first_name`, `last_name`, `address`, `zip_code`, `city`, `country_of_residence`, `phone_number`, `email`, `remember_token`, timestamps, soft-deletes
      - Optional: `legacy_athlete_id`, `legacy_donor_id` (temp migration trace columns)
      - `ExternalUser extends Authenticatable` — standard Laravel authenticatable with passwordless login (same pattern as admin `User`: signed URL via email), uses `auth:external` guard/provider with session driver
      - No `password` column (passwordless) and no `email_verified_at` column (email ownership is proven by receiving the signed URL)
      - Constraints: `email` unique (resolve historical duplicates in migration)
      - On delete: prefer soft-deletes; FKs from financial tables (`donations`, `athlete_registrations`) use `RESTRICT`

10. **`athlete_registrations`** (new, event participation):
    - `id`, `donation_event_id` FK, `external_user_id` FK, `sport_type_id`, `partner_id` (nullable), `rounds_estimated`, `rounds_done`, `comment`, `verified`, optional `public_id` (event-local), timestamps
    - Event results live in `rounds_done` + `rounds_estimated`; `external_users` remain stable identities across years
    - Constraints: unique (`donation_event_id`, `external_user_id`); index (`donation_event_id`, `verified`); app check: `partner_id` (if not null) must link to same event through `donation_event_partner`; app check: `sport_type_id` must be enabled for same event through `donation_event_sport_type`; equal-split controlled by `donation_events.has_equal_split_option` — when selected, `partner_id = null`
    - On delete: `RESTRICT` — cannot delete if used in donations or group memberships

11. **`donations`** (evolved):
    - Keep table name. `id`, `donation_event_id` FK, `donor_external_user_id` FK
    - Target: `athlete_registration_id` FK nullable OR `group_id` FK nullable
    - Pledge fields: `amount_per_round`, `amount_min`, `amount_max`, `comment`, `verified`, timestamps
    - Constraints: unique (`donor_external_user_id`, `donation_event_id`, `athlete_registration_id`) when athlete target used; unique (`donor_external_user_id`, `donation_event_id`, `group_id`) when group target used; DB check: exactly one donation target — `(athlete_registration_id IS NOT NULL AND group_id IS NULL) OR (athlete_registration_id IS NULL AND group_id IS NOT NULL)`; DB check: `donation_event_id` must match target event; when equal-split enabled, `partner_id = null`
    - On delete: `RESTRICT` — donations never cascade-deleted via parent FK
    - Index: (`donation_event_id`, `donor_external_user_id`)

12. **`groups`** (new, event-scoped):
    - `id`, `donation_event_id`, `name`, `description`, timestamps
    - Constraints: unique (`donation_event_id`, `name`)
    - On delete: `RESTRICT` — cannot delete group if used in donations or memberships

13. **`group_memberships`** (new):
    - `id`, `group_id`, `athlete_registration_id`, timestamps
    - Constraints: unique (`group_id`, `athlete_registration_id`)
    - On delete: `RESTRICT` — consistent with financial tables; group deletion blocked if memberships exist

### On-delete strategy summary

- **Financial/operational tables** (`donations`, `athlete_registrations`, `groups`, `group_memberships`): all `RESTRICT` — prevent accidental data loss
- **Event pivots** (`donation_event_partner`, `_sponsor`, `_faq`, `_sport_type`): `CASCADE` on event delete — event-scoped config rows cleared automatically
- **External users**: prefer soft-deletes; FKs from financial tables use `RESTRICT` so historical records prevent hard deletion

---

## C) Migration Strategy

### Phase 0 — Preparation ✓

- [x] Full DB backup + checksum snapshot
- [x] Prepare idempotent migration command with `--dry-run` + report output — `hfm:backfill:event-content`
- [x] Prepare explicit historical event date mapping — `DonationEventSeeder` with 2025/2026 event data

### Phase 1 — Incremental content-model rollout ✓

- [x] Create global catalogs: `sponsors`, `faqs`; evolve `partners` into global catalog
- [x] Create event pivots: `donation_event_partner`, `donation_event_sponsor`, `donation_event_faq`, `donation_event_sport_type`
- [x] Add `donation_events.has_equal_split_option` (default `true`); allow `athletes.partner_id = null` for equal-split
- [x] Keep compatibility:
  - [x] Sponsor rendering uses event pivot rows; master data global
  - [x] FAQ rendering uses event pivot rows + global fallback
  - [x] Equal split enabled → registration offers "Alle zu gleichen Teilen" + persists `partner_id = null`
- [x] Keep old hardcoded/fallback content until admin data entry complete; remaining: `athlete_welcome_letter.blade.php` date, `association.blade.php` text

### Phase 2 — Expand schema for identity/financial refactor ← NEXT UP

- [ ] Create: `external_users` (as `Authenticatable` — no password column, passwordless login via signed URLs like admin `User`), `athlete_registrations`, `groups`, `group_memberships`
- [ ] Extend `donations` with nullable `donation_event_id`, `donor_external_user_id`, `athlete_registration_id`, `group_id`
- [ ] Keep old columns/tables for compatibility during transition

### Phase 3 — Seed historical events ✓

- [x] One `donation_events` row per year — seeder creates 2025 + 2026 events
- [x] Unknown exact times → inferred defaults in `starts_at`/`ends_at`; provenance in migration logs, not JSON columns

### Phase 4 — Build external identity map (`athletes` + `donors` → `external_users`)

- [ ] Build index of normalized emails across `athletes` + `donors` (`donors.email` may duplicate)
- [ ] Unique emails across both tables → create one `external_users` row + attach all matching
- [ ] Multiple `donors` with same email:
  - [ ] Name + address identical → auto-merge, record all `legacy_donor_id`s
  - [ ] Name or address differ → "donor-email-conflicts" manual-review CSV; resolve before hard cutover
- [ ] Unmatched rows → create `external_users` keyed by name, address, phone
- [ ] Persist mapping table (`legacy_athlete_id`/`legacy_donor_id` → `external_user_id`)

### Phase 5 — Backfill participations and donations

- [x] `donation_event` for each legacy athlete: `athletes.donation_event_id` backfilled via `BackfillAthleteEventAssignmentsCommand`
- [ ] For each legacy athlete: create one `athlete_registration` from resolved `donation_event_id` + copy event-scoped fields (`rounds_estimated`, `rounds_done`, `verified`, legacy `partner_id`)
- [ ] For each legacy donation:
  - [ ] Map `donor_id` → `donor_external_user_id`
  - [ ] Map `athlete_id` → `athlete_registration_id`
  - [ ] Set `donation_event_id` from registration's event
  - [ ] Keep `group_id = null`

### Phase 6 — Migrate auth to unified login with guard separation

- [ ] Wire `ExternalUser` into Laravel auth: configure `auth:external` guard + `external_users` provider in `config/auth.php`
- [ ] External users use the **same login page** as admin users; submit email → receive a temporarily signed URL (same pattern as current admin `User` login via `URL::temporarySignedRoute`)
- [ ] Add mandatory middleware/gates ensuring `auth:external` guard cannot access admin routes and `auth:web`/`auth:admin` guard cannot access external write endpoints — must be impossible for external users to reach admin routes
- [ ] Welcome/registration notifications: replace vulnerable `/sportlerinnen/{token}` and `/spenderinnen/{token}` links with the same signed-URL login pattern used for admin users — no plain-text tokens in URLs or emails
- [ ] QR codes in welcome letters etc. simply encode the login page URL (no tokens in QR codes)
- [ ] Login auditability: covered by `spatie/laravel-activitylog` (GH #111)
- [ ] Remove old token routes: `/sportlerinnen/{token}` + `/spenderinnen/{token}` → gone, no redirects needed
- [ ] Remove `login_token` and `login_token_expiry_days` config — no longer used
- [ ] Phases 2–5 continue using legacy `Athlete`/`Donor` token lookups; `auth:external` guard activation is Phase 6 cutover point

### Phase 7 — Enforce constraints and cutover

- [ ] Switch app to new reads/writes (event + external user scoped)
- [ ] Enforce NOT NULL + unique + XOR constraints
- [ ] Keep legacy tables/columns read-only for one release; drop after validation window

### Rollback + integrity checks

- [ ] Rollback path: return to legacy reads
- [ ] Integrity checks: row count parity, amount parity per event/donor, every donation target belongs to same event, every legacy row mapped to exactly one external user

---

## D) Application-Layer Changes

### Event scoping

- [ ] Introduce canonical event routes: `/events/{event:slug}/...`
- [x] Event context resolver — `CurrentDonationEventService` resolves from `EventSettings.current_event_id` with caching
- [ ] Apply event scope to all dashboard/results/admin/export/invoice queries

### Authentication architecture

- [ ] Same login page for both internal admins and external participants — submit email → receive temporarily signed URL (passwordless, same pattern as current admin `User` login). Auth routes are **not** event-scoped; one login gives access to all events the user participates in
- [ ] Internal admins: `users` table, `auth:web` / `auth:admin` guard
- [ ] External participants: `external_users` table, `auth:external` guard — standard `Authenticatable` with passwordless signed-URL login, no password column, no custom token columns. `remember_token` included (same "always remember" behavior as admin users)
- [ ] Mandatory guard separation: external users **cannot** access admin routes; internal users **cannot** access external write endpoints — enforced via middleware + gates, not convention

### External auth flow

- [ ] External users login on the same page as admin users — submit email → receive temporarily signed URL (same pattern as current admin `User` login: `URL::temporarySignedRoute`). Auth routes are **not** event-scoped; one login, all data, event is a navigation concern
- [ ] Dual-role users get one account, one signed URL — no more separate tokens per role
- [ ] Welcome/registration notifications: replace vulnerable `/sportlerinnen/{token}` and `/spenderinnen/{token}` links with the login page or a temporarily signed URL — no plain-text tokens in emails or URLs
- [ ] QR codes in printed materials (welcome letters etc.) encode the login page URL only — no tokens in QR codes
- [ ] Old token routes (`/sportlerinnen/{token}`, `/spenderinnen/{token}`) removed entirely

### UI correctness

- [ ] Replace split athlete/donor navigation with unified external portal
- [ ] External portal shows currently live event by default; allows navigation to other events — one login, all data, no event separation at auth level
- [ ] Dual-role users see both modules in one account context
- [ ] Add event switcher/selector for historical views
- [x] Registration forms use `CurrentDonationEventService` for partner list + equal-split + `active-event` guard; full rewrite deferred to follow-up

### Exports/invoicing

- [ ] Invoice/export jobs must include `donation_event_id` filter
- [x] Invoice texts + metadata derive from event, not hardcoded strings
- [x] Public text fragments → `donation_events.content` with fallback copy — `DonationEvent::contentValue()` family

### Naming compatibility (`donors` misspelling)

- [x] Do not rename abruptly; keep legacy `donors` table for migration mapping
- [ ] Rename/deprecate only in dedicated follow-up with compatibility alias if needed

### Identity modeling decision

- **Choice: unified `external_users` + role-by-relationship (option ii), extends `Authenticatable`**
- Why: solves dual-role UX/security, cleaner auth boundary, aligns with Laravel multi-auth, supports future write operations
- Auth via same login page as admin users (passwordless: submit email → receive temporarily signed URL) with `auth:external` guard — no password column, no custom token columns, no separate login routes
- Mandatory guard separation: external users cannot access admin routes, enforced via middleware + gates
- Tradeoff accepted: migration complexity higher than keeping separate tables
- Alternatives rejected: (i) keep separate `athletes`+`donors` — poor dual-role UX, duplicated auth state, harder write-permission model; (iii) merge into `users` — weak privilege boundary, security/operational risk

---

## E) Verification

### Pending schema/constraint tests

- [ ] `external_users` uniqueness/dedup behavior
- [ ] `donations` target XOR constraint
- [ ] `athlete_registrations` unique per (`event`, `external_user`)

### Pending feature tests

- [ ] External login resolves one portal for dual-role user
- [ ] External guard **cannot** access admin routes (enforced by middleware, tested per route)
- [ ] Internal guard **cannot** access external write endpoints
- [ ] Donor can sponsor same athlete in different events
- [ ] Event-scoped results/dashboard totals are isolated

### Pending migration tests

- [ ] Backfill parity checks for counts and sums
- [ ] Conflict fixture test (same email, differing names) requires manual resolution path
- [ ] Dry-run migration reports identity merge decisions

### Pending regression tests

- [ ] Invoice generation works with event filter
- [ ] Old token routes (`/sportlerinnen/{token}`, `/spenderinnen/{token}`) return 404 or redirect to login

### Post-migration acceptance criteria

- [ ] 100% legacy athlete/donor rows mapped to exactly one external user
- [ ] Per-event financial totals match baseline
- [ ] External auth uses same passwordless login page as admin users (email → signed URL) with `auth:external` guard; mandatory guard separation enforced
- [ ] Admin pages remain internal-only; event switching yields consistent counts