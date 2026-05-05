# Multi-Event / Multi-Year Restructure Plan

## A) Current-state assessment

### Where single-event assumptions currently live

- **Core schema is event-less**:
    - `athletes` stores participation fields (`rounds_estimated`, `rounds_done`, `verified`) on the person row, with no
      event FK.
    - `donations` links `donor_id` and `athlete_id` only (no event scope).
    - `athletes.partner_id` models partner selection globally instead of per event.
    - Migrations: `database/migrations/2024_04_24_201842_create_athletes_table.php`,
      `2024_05_01_065017_create_donations_table.php`, `2024_05_10_171654_add_foreign_keys_to_athlete.php`.

- **Model/service assumptions are global (single event)**:
    - `Athlete::donations()` and `Donation::athlete()` are direct links.
    - Totals/amounts use `donation->athlete->rounds_*` as if there is one canonical participation.
    - Files: `app/Models/Athlete.php`, `app/Models/Donation.php`, `app/Services/DonationService.php`,
      `app/Services/DonorService.php`.

- **Registration flows are globally scoped**:
    - `BecomeAthleteForm` creates an athlete as both identity + participation.
    - `BecomeDonorForm` queries all verified athletes globally and blocks duplicates globally (`donor + athlete`).
    - Files: `app/Components/BecomeAthleteForm.php`, `app/Components/BecomeDonorForm.php`.

- **Authentication entrypoints are role-token and event-less**:
    - `/sportlerinnen/{login_token}` and `/spenderinnen/{login_token}` split by route type and token.
    - If same human is both athlete and donor, they may receive two links/tokens.
    - Files: `routes/web.php`, `app/Components/AthleteDetails.php`, `app/Components/DonorDetails.php`,
      `app/Components/LoginForm.php`, `app/Notifications/NewLoginLink.php`.

- **Reporting/admin are not event-filtered**:
    - Results/dashboard aggregate all rows.
    - Admin tables have no event dimension.
    - Files: `app/Components/Results.php`, `app/Services/DashboardService.php`, `app/Components/AdminAthleteTable.php`,
      `app/Components/AdminDonationTable.php`, `app/Components/AdminDonorTable.php`.

- **Event details are hardcoded in views/jobs**:
    - Static 2025 date references in pages/meta and invoice text metadata.
    - Files: `resources/views/pages/results.blade.php`, `resources/views/layouts/base.blade.php`,
      `resources/views/pages/questions-and-answers.blade.php`,
      `resources/views/printables/athlete_welcome_letter.blade.php`, `app/Jobs/CreateDonorInvoiceLetter.php`.

### Highest-risk coupling points / hidden invariants

1. `rounds_done` on athlete means one person cannot cleanly have independent yearly results.
2. Duplicate-donation prevention is global (`donor_id + athlete_id`) and will break multi-year scenarios.
3. Invoice totals derive from global athlete fields and risk cross-year leakage.
4. Token-route split (athlete view vs donor view) creates UX/security issues when one human has multiple roles and write
   operations are introduced.
5. Partner selection currently conflicts with the requirement “partners are selected per event”.

---

## B) Proposed target data model (high level)

> Updated recommendation based on your feedback: unify athlete/donor identity into **external users**, while keeping
> admins (`users`) separate.

### Core entities

1. **`donation_events`** (new, finalized core shape)
    - `id`, `slug` (unique), `title`.
    - `starts_at` (`datetimeTz`), `ends_at` (`datetimeTz`).
    - `registration_opens_at` (`datetimeTz`, nullable), `athlete_registration_closes_at` (`datetimeTz`, nullable),
      `donor_registration_closes_at` (`datetimeTz`, nullable).
    - `location_name` (nullable), `location_street` (nullable), `location_postal_code` (nullable string),
      `location_city` (required), `location_url` (nullable).
    - `is_published` (`boolean`, default `false`) to control whether an event is publicly visible/eligible as current.
    - `content` (`json`, nullable) for event-scoped rich text (markdown) snippets used on public pages (hero copy,
      homepage intro/body, SEO text, invoice metadata fallback text), with safe rendering and application
      fallbacks.
    - timestamps.

2. **`partners`** (evolved, global catalog model)
    - Existing table becomes global identity/content: `id`, `name`, `logo_light_path`, `logo_dark_path`,
      `beneficiary_blurb`, `url`, timestamps.
    - Event membership and event-specific presentation move to pivot `donation_event_partner`.

3. **`sponsors`** (new, global catalog model)
    - `id`, `name`, `description`, `logo_path`, `url`, timestamps.
    - Event membership and event-specific presentation/funding metadata move to pivot `donation_event_sponsor`.

4. **`faqs`** (new, reusable content model)
    - `id`, `title`, `content_md`, timestamps.
    - Event membership and event-specific ordering/group/publication move to pivot `donation_event_faq`.
    - FAQs without any pivot rows are treated as globally visible fallback FAQs.

5. **`donation_event_partner`** (new pivot)
    - `donation_event_id`, `partner_id`, `sort_order`, `is_published`, timestamps.

6. **`donation_event_sponsor`** (new pivot)
    - `donation_event_id`, `sponsor_id`, `size`, `contribution_text`, `sort_order`, `is_published`, timestamps.

7. **`donation_event_faq`** (new pivot)
    - `donation_event_id`, `faq_id`, `group`, `sort_order`, `is_published`, timestamps.

8. **`donation_event_sport_type`** (new pivot)
    - `donation_event_id`, `sport_type_id`, `sort_order`, `is_enabled`, timestamps.

9. **`external_users`** (new, replaces separate person identity concerns)
    - Represents a real-world external person (today split across `athletes` and `donors`).
    - Fields: `id`, `first_name`, `last_name`, `address`, `zip_code`, `city`, `country_of_residence`, `phone_number`,
      `email`, `email_verified_at`, timestamps.
    - Optional: `legacy_athlete_id`, `legacy_donor_id` (temporary migration trace columns).

10. **`external_user_auth_identities`** (new)
    - For secure, flexible auth bootstraps + future magic links.
    - Fields: `id`, `external_user_id`, `type` (`static_qr`, `magic_link`, etc.), `token_hash`, `expires_at`,
      `last_used_at`, `revoked_at`, timestamps.
    - Never store raw token beyond creation response (hash only).

11. **`athlete_registrations`** (new, event participation)
    - `id`, `donation_event_id` FK, `external_user_id` FK,
    - `sport_type_id`, `partner_id` (nullable), `rounds_estimated`, `rounds_done`, `comment`, `verified`,
    - optional `public_id` (event-local), timestamps.

12. **`donations` (evolved)**
    - Keep table name.
    - `id`, `donation_event_id` FK, `donor_external_user_id` FK,
    - target: `athlete_registration_id` FK nullable OR `group_id` FK nullable,
    - pledge fields: `amount_per_round`, `amount_min`, `amount_max`, `comment`, `verified`, timestamps.

13. **`groups`** (new, event scoped)
    - `id`, `donation_event_id`, `name`, `description`, timestamps.

14. **`group_memberships`** (new)
    - `id`, `group_id`, `athlete_registration_id`, timestamps.

### Relationships summary

- `DonationEvent hasMany AthleteRegistration`
- `ExternalUser hasMany AthleteRegistration`
- `ExternalUser hasMany Donations as donor` (via `donor_external_user_id`)
- `Donation belongsTo DonationEvent`
- `Donation belongsTo AthleteRegistration OR Group` (XOR)
- `DonationEvent belongsToMany Partner` through `donation_event_partner`
- `DonationEvent belongsToMany Sponsor` through `donation_event_sponsor`
- `DonationEvent belongsToMany Faq` through `donation_event_faq`
- `DonationEvent belongsToMany SportType` through `donation_event_sport_type`
- `DonationEvent hasMany Group`
- `Group belongsToMany AthleteRegistration` through `group_memberships`

### Constraints and indexes

- **Uniqueness**
    - `donation_events.slug` unique.
    - `partners.name` unique (global catalog identity).
    - `sponsors.name` unique (global catalog identity).
    - `donation_event_partner`: unique (`donation_event_id`, `partner_id`) and unique (`donation_event_id`, `sort_order`).
    - `donation_event_sponsor`: unique (`donation_event_id`, `sponsor_id`) and unique (`donation_event_id`, `sort_order`).
    - `donation_event_faq`: unique (`donation_event_id`, `faq_id`) and unique (`donation_event_id`, `group`, `sort_order`).
    - `donation_event_sport_type`: unique (`donation_event_id`, `sport_type_id`) and unique (`donation_event_id`, `sort_order`).
    - `external_users.email` unique (or unique per normalized email; if historical duplicates exist, resolve in
      migration phase).
        - `athlete_registrations`: unique (`donation_event_id`, `external_user_id`).
        - `groups`: unique (`donation_event_id`, `name`).
        - `group_memberships`: unique (`group_id`, `athlete_registration_id`).
    - `donations`: unique (`donor_external_user_id`, `donation_event_id`, `athlete_registration_id`) when athlete target
      is used; unique (`donor_external_user_id`, `donation_event_id`, `group_id`) when group target is used.

- **Integrity checks**
    - DB check: `donation_events.ends_at > donation_events.starts_at`.
    - DB check: where present, registration windows must be coherent (
      `registration_opens_at <= athlete_registration_closes_at` and
      `registration_opens_at <= donor_registration_closes_at`).
    - DB check: exactly one donation target (
      `(athlete_registration_id IS NOT NULL AND group_id IS NULL) OR (athlete_registration_id IS NULL AND group_id IS NOT NULL)`).
    - DB check: `donations.donation_event_id` must match target event (enforced in app + migration validation; DB-level
      via trigger if needed).
    - App check (incremental): equal-split selection is controlled by `donation_events.has_equal_split_option`; when
      selected, athlete registration stores `partner_id = null`.
    - App check: `athlete_registrations.partner_id` (if not null) must be linked to the same event through
      `donation_event_partner`.
    - App check: `athlete_registrations.sport_type_id` must be enabled for the same event through
      `donation_event_sport_type`.

- **Indexes**
    - All FKs indexed.
    - Event timeline lookups: `donation_events(starts_at)`, `donation_events(ends_at)`,
      `donation_events(athlete_registration_closes_at)`, `donation_events(donor_registration_closes_at)`.
    - Lookups: `athlete_registrations(donation_event_id, verified)`,
      `donations(donation_event_id, donor_external_user_id)`,
      `external_user_auth_identities(token_hash, revoked_at, expires_at)`,
      `donation_event_partner(donation_event_id, is_published, sort_order)`,
      `donation_event_sponsor(donation_event_id, is_published, sort_order)`,
      `donation_event_faq(donation_event_id, group, is_published, sort_order)`,
      `donation_event_sport_type(donation_event_id, is_enabled, sort_order)`.

### Deletion / referential integrity

- **Foreign key strategy (financial tables)**
    - All financial / operational tables (`donations`, `athlete_registrations`, `group_memberships`) use
      `ON DELETE RESTRICT` (or equivalent `NO ACTION`) towards their parents to prevent accidental data loss.
    - `donation_events` is referenced by `athlete_registrations`, `groups`, and `donations` and must use
      `ON DELETE RESTRICT` on these FKs. Events cannot be deleted once any related registrations, groups, or donations
      exist.
    - `athlete_registrations` is referenced by `donations` and `group_memberships` and must use `ON DELETE RESTRICT`.
      Registrations cannot be deleted if they are used in any donation or group membership.
    - `groups` is referenced by `donations` and `group_memberships` and must use `ON DELETE RESTRICT`. Groups cannot be
      deleted if they are used in any donation or membership.
    - `donations` are never cascaded‑deleted via any parent FK; deletion is restricted once created (application‑level
      and DB‑level).
- **Foreign key strategy (configuration / content tables)**
    - Catalog/content identities (`partners`, `sponsors`, `faqs`, `sport_types`) are global and should not depend on
      a single event FK.
    - Event linkage/configuration is stored in pivots (`donation_event_partner`, `donation_event_sponsor`,
      `donation_event_faq`, `donation_event_sport_type`) and may use `ON DELETE CASCADE` from `donation_events` to
      clear event-scoped configuration rows.
- **External users and identities**
    - Prefer soft-deletes for `external_users`; hard delete only before first production usage or with a strict
      compliance workflow.
    - FKs from financial tables (`donations`, `athlete_registrations`) to `external_users` must use `ON DELETE RESTRICT`
      so that historical records prevent hard deletion of identities.

### Results representation

- Event results live in `athlete_registrations.rounds_done` and `rounds_estimated`.
- `external_users` remain stable identities across years.

---

## C) Migration strategy (downtime allowed)

### Phase 0 — Preparation

1. Full DB backup + checksum snapshot.
2. Prepare idempotent migration command with `--dry-run` and report output.
3. Prepare explicit historical event date mapping source (from known years currently hardcoded in views/content).

### Phase 1 — Incremental content-model rollout (model-first)

1. Create/extend event-scoped content models first (independent from identity migration):
    - create global catalogs: `sponsors`, `faqs` and evolve `partners` into a global catalog,
    - create event pivots: `donation_event_partner`, `donation_event_sponsor`, `donation_event_faq`,
      `donation_event_sport_type`.
    - add `donation_events.has_equal_split_option` (default `true`) and allow `athletes.partner_id = null` for
      equal-split selections.
2. Keep compatibility behavior:
    - sponsor rendering uses event pivot rows; sponsor master data stays global,
    - faq rendering uses event pivot rows plus global fallback FAQs without event links,
    - if equal split is enabled for an event, registration offers "Alle zu gleichen Teilen" and persists `partner_id = null`.
3. Keep old hardcoded/fallback content in place until admin data entry for the new models is complete.

### Phase 2 — Expand schema for identity/financial refactor

1. Create: `external_users`, `external_user_auth_identities`, `athlete_registrations`, `groups`, `group_memberships`.
2. Extend `donations` with nullable `donation_event_id`, `donor_external_user_id`, `athlete_registration_id`,
   `group_id`.
3. Keep old columns/tables for compatibility during transition.

### Phase 3 — Seed historical events

1. Create one `donation_events` row per historical year.
2. If exact times are unknown for old events, store inferred defaults directly in `starts_at` / `ends_at` and keep
   migration-time provenance in migration logs/reports (not in event JSON columns).

### Phase 4 — Build external identity map (`athletes` + `donors` → `external_users`)

1. Build an index of normalized emails across `athletes` and `donors` (note: only `athletes.email` is constrained
   unique; `donors.email` may be duplicated).
2. For emails that are unique across both tables, create one `external_users` row and attach all matching `athletes` /
   `donors`.
3. For emails that appear on multiple `donors` rows:
    - If name and address are identical, auto-merge them into a single `external_users` entry and record all
      `legacy_donor_id`s.
    - If name or address differ (likely shared email or data inconsistency), write them to a "donor-email-conflicts"
      manual-review report/CSV and require resolution (deduplication or explicit shared-email modeling) before hard
      cutover.
4. For rows without email or still unmatched after the email-based pass, create new `external_users` keyed by other
   stable attributes (e.g. name, address, optional phone).
5. Persist a mapping table (`legacy_athlete_id` / `legacy_donor_id` → `external_user_id`) or a dedicated map table.

### Phase 5 — Backfill participations and donations

1. Determine the `donation_event` for each legacy athlete:
    - If all legacy data belongs to a single event, create one canonical `donation_events` row and assign all legacy
      athletes to that event.
    - If multiple historical events existed, create an explicit mapping (for example a `legacy_event_mappings` config
      array or temporary table) that maps legacy athletes to `donation_event_id` based on agreed business rules (such as
      `created_at` windows, ID ranges, or manually curated lists). This mapping MUST be defined before running the
      backfill.
2. For each legacy athlete row, create one `athlete_registration` using the resolved `donation_event_id` from step 1 and
   copy event-scoped fields (`rounds_estimated`, `rounds_done`, `verified`, and legacy `partner_id`).
3. For each legacy donation row:
    - map `donor_id` to `donor_external_user_id`,
    - map `athlete_id` to `athlete_registration_id`,
    - set `donation_event_id` from the registration’s event (`athlete_registrations.donation_event_id`),
    - keep `group_id = null`.
4. Backfill event pivots from legacy/hardcoded data:
    - `donation_event_partner` rows for event-specific partner visibility/order,
    - `donation_event_sponsor` rows for event-specific sponsor size/order/contribution text,
    - `donation_event_faq` rows for event-specific FAQ grouping/order/publication,
    - `donation_event_sport_type` rows for event-specific sport type availability/order.

### Phase 6 — Backfill auth identities

1. Convert legacy static tokens (`athletes.login_token`, `donors.login_token`) into `external_user_auth_identities`
   records (`type=static_qr`, long expiry or policy-based expiry).
    - For each legacy `login_token`, compute and store `token_hash` using the same hashing algorithm and format used for
      newly issued tokens in the new system.
    - Do **not** store raw legacy tokens in `external_user_auth_identities`; keep raw values only in legacy
      tables/columns until they are dropped at the end of the deprecation window.
    - The user-facing token (e.g. in QR codes or login links) remains the same string; only the persistence format
      changes from plain value to `token_hash`.
2. During the transition period, support both hashed and legacy tokens with a clear deprecation timeline:
    - Authentication should first attempt to resolve by `token_hash` in `external_user_auth_identities`.
    - If no match is found and a feature flag (for example, `legacy_plain_tokens_enabled`) is on, fall back to matching
      against the legacy plain-hex `login_token` columns, and then record/create the corresponding
      `external_user_auth_identities` entry with `token_hash` populated.
    - Define and document a concrete deprecation date (for example, 3–6 months after deployment) after which the legacy
      fallback is removed, remaining legacy tokens are reissued using the new scheme, and legacy token columns are
      considered write-locked and then dropped.
3. If one user had both tokens, keep both (each as its own `external_user_auth_identities` row with its own
   `token_hash`) temporarily and mark one canonical for newly generated links.

### Phase 7 — Enforce constraints and cutover

1. Switch application to new reads/writes (event + external user scoped).
2. Enforce NOT NULL + unique + XOR constraints.
3. Keep legacy tables/columns read-only for one release; drop only after validation window.

### Rollback + integrity checks

- Rollback path: return app to legacy reads (since legacy columns remain initially).
- Integrity checks:
    - row count parity (legacy vs migrated donations),
    - amount parity per event and per donor,
    - every donation target belongs to same event,
    - every legacy athlete/donor mapped to exactly one external user.

---

## D) Application-layer changes (plan only)

### Event scoping

1. Introduce canonical event routes: `/events/{event:slug}/...`.
2. Add event context resolver (route first, then fallback to the "current event" configured via the existing
   `SettingsService`).
3. Apply event scope to all dashboard/results/admin/export/invoice queries.

### Authentication architecture (internal vs external)

- **Internal admins**: keep `users` and existing admin auth, formalize as internal guard (`auth:internal` /
  `auth:admin`).
- **External participants**: authenticate `external_users` via separate external guard/provider (`auth:external`).
- This cleanly reflects privilege boundaries and aligns with Laravel guard architecture.

### External auth flow plan (UX + security)

1. Replace role-specific token pages with one external entry context:
    - e.g. `/events/{slug}/portal/{token}` resolves external user and shows role capabilities for that event (athlete,
      donor, both).
2. Keep QR utility:
    - static QR token can bootstrap access,
    - for write actions require step-up auth (short-lived magic link / one-time code).
3. Future-ready write operations:
    - donor donation confirmation,
    - profile edits,
    - group invitation workflows.

### UI correctness changes

- Replace split “athlete view” vs “donor view” navigation with unified external portal sections.
- If user has both roles, show both modules in one account context.
- Add event switcher/selector for historical views where appropriate.
- Incremental note: registration form rewrites (`BecomeAthleteForm`, `BecomeDonorForm`) are deferred to a dedicated
  follow-up refactor; current rollout may include only minimal compatibility changes.

### Exports/invoicing impacts

- Invoice/export jobs must include `donation_event_id` filter.
- Invoice texts and metadata derive from selected event (`title`, `starts_at` / `ends_at`, and location fields), not
  hardcoded 2025 strings.
- Public-page text fragments that need event variance should come from `donation_events.content` (markdown snippets),
  with explicit fallback copy for missing keys.

### Compatibility plan for naming (`donors` misspelling)

- Do **not** rename abruptly.
- During transition, keep legacy `donors` table as source for migration mapping.
- Rename/deprecate only in a dedicated follow-up with compatibility view/model alias if required.

### Identity modeling decision analysis (revised)

1. **(i) Keep separate `athletes` + `donors`**
    - Pros: lowest immediate migration risk.
    - Cons: poor UX for dual-role humans, duplicated auth state, harder write-permission model.

2. **(ii) Introduce unified `external_users` + role-by-relationship (recommended)**
    - Pros: one external identity, cleaner UX, secure guard separation (`internal` vs `external`), aligns with Laravel
      architecture for multi-auth and future authorization.
    - Cons: migration complexity is higher than (i), especially identity deduping.

3. **(iii) Merge externals into admin `users`**
    - Pros: single auth table.
    - Cons: weak privilege boundary, security/operational risk, not recommended.

**Conclusion**: choose **(ii)** now because it directly solves the dual-role UX/security issue and supports planned
write operations without conflating admin and external privileges.

---

## E) Testing & verification

### Minimal test additions/updates

1. **Schema/constraint tests**
    - `partners`, `sponsors`, `faqs`, `sport_types` are global catalog models.
    - Event pivot constraints hold for `donation_event_partner`, `donation_event_sponsor`, `donation_event_faq`,
      `donation_event_sport_type`.
    - `donation_events.has_equal_split_option` defaults to true and `athletes.partner_id` supports null for equal split.
    - `external_users` uniqueness/dedup behavior.
    - `donations` target XOR constraint.
    - `athlete_registrations` unique per (`event`, `external_user`).

2. **Feature tests**
    - Home "Wer profitiert" renders event partners only from DB configuration.
    - Athlete registration offers "Alle zu gleichen Teilen" when event flag is enabled and stores `partner_id = null`.
    - Sponsor section renders sponsors configured for the event (with event-specific pivot metadata like `size`).
    - FAQ page renders event FAQs in group/sort order, plus global fallback FAQs not linked to any event.
    - External login resolves one portal for dual-role user.
    - External guard cannot access admin routes; internal guard cannot access external write endpoints unless explicitly
      allowed.
    - Donor can sponsor same athlete in different events.
    - Event-scoped results/dashboard totals are isolated.

3. **Migration tests**
    - dry-run reports identity merge decisions,
    - backfill parity checks for counts and sums,
    - conflict fixture test (same email, differing names) requires manual resolution path.

4. **Regression tests**
    - invoice generation still works with event filter,
    - legacy token links (QR codes, emailed links) continue to work via compatibility redirect throughout the
      one-release transition window,
    - existing bookmarks and saved links to legacy routes (e.g. dashboards, invoices, athlete/donor views) remain
      functional during the transition window via redirect or compatibility routing.

### Post-migration verification checklist

- Identity mapping
    - 100% of legacy athlete/donor rows mapped to exactly one external user.
    - dual-role sample users can access both athlete/donor capabilities in one portal.

- Financial parity
    - per-event totals match baseline.
    - random donor invoice spot checks unchanged after migration.

- Security/auth
    - external tokens are revocable and auditable (`last_used_at`).
    - write operations require short-lived auth proof (no static-token-only writes).

- Functional
    - admin pages remain internal-only.
    - event switching yields consistent counts and lists.

---

## Recommendation

Adopt **incremental multi-event rollout + unified external identity**:

1. Deliver content models first (`partners`, `sponsors`, `faqs`, `sport_types`) plus event pivots to remove hardcoded
   page content and keep scope small.
2. Then continue with identity/financial migration (`external_users`, `athlete_registrations`, donation refactor).
3. Keep admins in `users` under internal guard; use separate external guard for participant-facing auth.
4. Preserve QR convenience, but treat static tokens as bootstrap and require short-lived auth for write actions.

**Risk**: Medium in early increments, rising to Medium-High for identity merge and auth refactor.
**Effort**: High overall, but with lower-risk slices that are deployable independently.
**Why this is recommended**: it reduces migration blast radius now while still converging on the same long-term
multi-event + unified external identity architecture.
