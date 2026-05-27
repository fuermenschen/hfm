# Multi-Event / Multi-Year Restructure Plan

## A) Previous State

Single-event system. `athletes` and `donors` are flat identity+participation rows with no event scope. Participation fields (`rounds_estimated`, `rounds_done`, `verified`) on person row, not per-event. Donations link `donor_id` + `athlete_id` globally. Partners selected per-athlete globally (`partner_id`), not per-event. Auth uses vulnerable plain-text token routes: `/sportlerinnen/{token}` + `/spenderinnen/{token}` — same human with both roles gets two tokens, no proper login page. Reporting aggregates across all events. Event details hardcoded in views/jobs.

Highest-risk coupling: `rounds_done` on athlete breaks per-year results. Partner selection conflicts with per-event requirement. Duplicate-donation prevention is global (`donor_id + athlete_id`). Invoice totals derive from global athlete fields — cross-year leakage risk; event-scoped invoice reintroduction is tracked separately in GitHub issue #134. Vulnerable plain-text token auth (`/sportlerinnen/{token}` + `/spenderinnen/{token}`) creates UX/security issues for dual-role humans and exposes tokens in URLs/notifications.

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
       - `id`, `uuid` (unique, for signed URL routes — same pattern as admin `User`), `first_name`, `last_name`, `address`, `zip_code`, `city`, `country_of_residence`, `phone_number`, `email`, `public_id` (6-char uppercase alphanumeric, globally unique, always auto-generated, displayed as `XXX-XXX` to disambiguate users sharing the same privacy name), `remember_token`, timestamps, soft-deletes
        - `ExternalUser extends Authenticatable` — standard Laravel authenticatable with passwordless login (same pattern as admin `User`: signed URL via email), uses `auth:external` guard/provider with session driver
       - No `password` column (passwordless) and no `email_verified_at` column (email ownership is proven by receiving the signed URL)
       - Constraints: `email` unique (resolve historical duplicates in migration)
       - On delete: prefer soft-deletes; FKs from financial tables (`donations`, `athlete_registrations`) use `RESTRICT`

10. **`athlete_registrations`** (new, event participation):
     - `id`, `donation_event_id` FK, `external_user_id` FK, `sport_type_id`, `partner_id` (nullable), `rounds_estimated`, `rounds_done`, `comment`, `verified`, timestamps
    - Event results live in `rounds_done` + `rounds_estimated`; `external_users` remain stable identities across years
    - Constraints: unique (`donation_event_id`, `external_user_id`); index (`donation_event_id`, `verified`); app check: `partner_id` (if not null) must link to same event through `donation_event_partner`; app check: `sport_type_id` must be enabled for same event through `donation_event_sport_type`; equal-split controlled by `donation_events.has_equal_split_option` — when selected, `partner_id = null`
    - On delete: `RESTRICT` — cannot delete if used in donations or group memberships

11. **`donations`** (evolved):
    - Keep table name. `id`, `donor_external_user_id` FK, `athlete_registration_id` FK (every donation targets an athlete until groups are added)
    - Pledge fields: `amount_per_round`, `amount_min`, `amount_max`, `comment`, `verified`, timestamps
    - Event scope is derived through `athlete_registration.donation_event_id`; no redundant `donation_event_id` FK on donations
    - Constraints: unique (`donor_external_user_id`, `athlete_registration_id`)
    - Future (groups): add nullable `group_id` FK, make `athlete_registration_id` nullable, enforce XOR constraint — `(athlete_registration_id IS NOT NULL AND group_id IS NULL) OR (athlete_registration_id IS NULL AND group_id IS NOT NULL)`
    - Future (groups): event scope derives through the chosen target (`athlete_registration.donation_event_id` or `groups.donation_event_id`); no redundant event FK on donations
    - On delete: `RESTRICT` — donations never cascade-deleted via parent FK
    - Index: (`donor_external_user_id`, `athlete_registration_id`)

12. **`groups`** (new, event-scoped):
    - `id`, `donation_event_id`, `name`, `description`, timestamps
    - Constraints: unique (`donation_event_id`, `name`)
    - On delete: `RESTRICT` — cannot delete group if used in donations or memberships

13. **`group_memberships`** (new):
    - `id`, `group_id`, `athlete_registration_id`, timestamps
    - Constraints: unique (`group_id`, `athlete_registration_id`)
    - On delete: `RESTRICT` — consistent with financial tables; group deletion blocked if memberships exist

14. **`donor_event_invoices`** (future, tracked in GitHub issue #134):
    - `id`, `external_user_id` FK, `donation_event_id` FK
    - Webling state: `webling_debitor_id`, `webling_debitor_url`, `payment_status`
    - PDF state: `pdf_disk`, `pdf_path`, `pdf_size`
    - Send state: `invoice_sent_at`, `invoice_reminder_sent_at`, timestamps
    - Constraints: unique (`external_user_id`, `donation_event_id`); index `webling_debitor_id`; index (`donation_event_id`, `payment_status`)
    - On delete: `RESTRICT` — invoices remain tied to financial history

### On-delete strategy summary

- **Financial/operational tables** (`donations`, `athlete_registrations`, `groups`, `group_memberships`, `donor_event_invoices`): all `RESTRICT` — prevent accidental data loss
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

### Phase 2 — Seed historical events ✓

- [x] One `donation_events` row per year — seeder creates 2025 + 2026 events
- [x] Unknown exact times → inferred defaults in `starts_at`/`ends_at`; provenance in migration logs, not JSON columns

### Phase 3 — Refactor athletes/donors into external users

> Replace the split `athletes` + `donors` identity + token-auth system with unified `external_users`, event-scoped `athlete_registrations`, and passwordless auth via signed URLs. No registrations are currently happening, so zero concurrent-write risk during migration. Migration window guarantee: no new `users`, `external_users`, `athletes`, `donors`, `athlete_registrations`, or `donations` until the third pull request merge. Execute incrementally: add new schema/auth, backfill and switch reads, then remove legacy tables and token routes after validation.

> Roll out this refactor in three deployable slices because each merged pull request auto-deploys. The sequence is additive foundation, validated data cutover, then legacy removal.

- [x] Add the new external identity schema and passwordless auth foundation without disrupting legacy reads
- [x] Backfill legacy identities, participations, and donation links; then switch participant-facing reads to the new model
- [x] Remove legacy identity tables, token routes, and fallback code after validation

### Phase 4 — Groups, portal UI, event-scoped routes

> New features (not refactoring) that build on the new schema after Phase 3 is complete.

- [ ] Create `groups` + `group_memberships` tables/models; add `group_id` to `donations` with athlete/group XOR constraint
- [ ] Verify `donations` target XOR constraint (athlete_registration_id XOR group_id)
- [ ] Replace split athlete/donor navigation with unified external portal; add event switcher/selector
- [ ] Introduce canonical event routes: `/events/{event:slug}/...`
- [ ] Rebuild public registration flows and printable athlete documents on the new event/group model
- [ ] Apply event scope to dashboard/results/admin/export queries

### Phase 5 — Event-scoped invoices

> Reintroduce invoice workflows after registrations reopen work is unblocked. Existing Webling integration, invoice services, jobs, and admin patterns should be reused where practical. Detailed tracking lives in GitHub issue #134.

- [ ] Create `donor_event_invoices` with one row per external user per donation event
- [ ] Move Webling debitor state, PDF storage metadata, payment status, sent timestamp, and reminder timestamp onto the event invoice row
- [ ] Refactor invoice creation, letters, download, sending, reminders, payment-status sync, and admin bulk actions to operate per event invoice
- [ ] Filter invoice line collection by donation event

---

## D) Application-Layer Architecture

> Phase 3 execution details (auth wiring, landing page, read switching, admin migration) are tracked outside this big-picture plan. This section covers cross-cutting decisions.

### Authentication architecture

- Same login page for both internal admins and external participants — submit email → receive temporarily signed URL (passwordless, same pattern as current admin `User` login). Auth routes are **not** event-scoped; one login gives access to all events the user participates in. Dual-role users (athlete + donor) get one account, one signed URL — no more separate tokens per role
- Internal admins: `users` table, `auth:web` / `auth:admin` guard
- External participants: `external_users` table, `auth:external` guard — standard `Authenticatable` with passwordless signed-URL login, no password column, no custom token columns. `remember_token` included (same "always remember" behavior as admin users)
- Mandatory guard separation: external users **cannot** access admin routes; internal users **cannot** access external write endpoints — enforced via middleware, not convention

### Identity modeling decision

- **Choice: unified `external_users` + role-by-relationship (option ii), extends `Authenticatable`**
- Why: solves dual-role UX/security, cleaner auth boundary, aligns with Laravel multi-auth, supports future write operations
- Tradeoff accepted: migration complexity higher than keeping separate tables
- Alternatives rejected: (i) keep separate `athletes`+`donors` — poor dual-role UX, duplicated auth state, harder write-permission model; (iii) merge into `users` — weak privilege boundary, security/operational risk

---

## E) Verification

> Phase 3 verification covers schema, auth, backfill, legacy removal, and event-scoped totals.
