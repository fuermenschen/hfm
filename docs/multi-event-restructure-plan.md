# Multi-Event / Multi-Year Restructure Plan

## A) Current-state assessment

### Where single-event assumptions currently live

- **Core schema is event-less**:

    - `athletes` stores participation fields (`rounds_estimated`, `rounds_done`, `verified`) on the person row, with no event FK.
    - `donations` links `donator_id` and `athlete_id` only (no event scope).
    - `athletes.partner_id` models partner selection globally instead of per event.
    - Migrations: `database/migrations/2024_04_24_201842_create_athletes_table.php`, `2024_05_01_065017_create_donations_table.php`, `2024_05_10_171654_add_foreign_keys_to_athlete.php`.

- **Model/service assumptions are global (single event)**:

    - `Athlete::donations()` and `Donation::athlete()` are direct links.
    - Totals/amounts use `donation->athlete->rounds_*` as if there is one canonical participation.
    - Files: `app/Models/Athlete.php`, `app/Models/Donation.php`, `app/Services/DonationService.php`, `app/Services/DonorService.php`.

- **Registration flows are globally scoped**:

    - `BecomeAthleteForm` creates an athlete as both identity + participation.
    - `BecomeDonatorForm` queries all verified athletes globally and blocks duplicates globally (`donator + athlete`).
    - Files: `app/Components/BecomeAthleteForm.php`, `app/Components/BecomeDonatorForm.php`.

- **Authentication entrypoints are role-token and event-less**:

    - `/sportlerinnen/{login_token}` and `/spenderinnen/{login_token}` split by route type and token.
    - If same human is both athlete and donor, they may receive two links/tokens.
    - Files: `routes/web.php`, `app/Components/AthleteDetails.php`, `app/Components/DonatorDetails.php`, `app/Components/LoginForm.php`, `app/Notifications/NewLoginLink.php`.

- **Reporting/admin are not event-filtered**:

    - Results/dashboard aggregate all rows.
    - Admin tables have no event dimension.
    - Files: `app/Components/Results.php`, `app/Services/DashboardService.php`, `app/Components/AdminAthleteTable.php`, `app/Components/AdminDonationTable.php`, `app/Components/AdminDonatorTable.php`.

- **Event details are hardcoded in views/jobs**:
    - Static 2025 date references in pages/meta and invoice text metadata.
    - Files: `resources/views/pages/results.blade.php`, `resources/views/layouts/base.blade.php`, `resources/views/pages/questions-and-answers.blade.php`, `resources/views/printables/athlete_welcome_letter.blade.php`, `app/Jobs/CreateDonorInvoiceLetter.php`.

### Highest-risk coupling points / hidden invariants

1. `rounds_done` on athlete means one person cannot cleanly have independent yearly results.
2. Duplicate-donation prevention is global (`donator_id + athlete_id`) and will break multi-year scenarios.
3. Invoice totals derive from global athlete fields and risk cross-year leakage.
4. Token-route split (athlete view vs donor view) creates UX/security issues when one human has multiple roles and write operations are introduced.
5. Partner selection currently conflicts with the requirement “partners are selected per event”.

---

## B) Proposed target data model (high level)

> Updated recommendation based on your feedback: unify athlete/donor identity into **external users**, while keeping admins (`users`) separate.

### Core entities

1. **`donation_events`** (new)

    - `id`, `slug` (unique), `title`, `start_datetime`, `end_datetime`, `status`, `settings_json`, timestamps.

2. **`external_users`** (new, replaces separate person identity concerns)

    - Represents a real-world external person (today split across `athletes` and `donators`).
    - Fields: `id`, `first_name`, `last_name`, `address`, `zip_code`, `city`, `country_of_residence`, `phone_number`, `email`, `email_verified_at`, timestamps.
    - Optional: `legacy_athlete_id`, `legacy_donator_id` (temporary migration trace columns).

3. **`external_user_auth_identities`** (new)

    - For secure, flexible auth bootstraps + future magic links.
    - Fields: `id`, `external_user_id`, `type` (`static_qr`, `magic_link`, etc.), `token_hash`, `expires_at`, `last_used_at`, `revoked_at`, timestamps.
    - Never store raw token beyond creation response (hash only).

4. **`athlete_registrations`** (new, event participation)

    - `id`, `donation_event_id` FK, `external_user_id` FK,
    - `sport_type_id`, `partner_id` (nullable), `rounds_estimated`, `rounds_done`, `comment`, `verified`,
    - optional `public_id` (event-local), timestamps.

5. **`donations` (evolved)**

    - Keep table name.
    - `id`, `donation_event_id` FK, `donator_external_user_id` FK,
    - target: `athlete_registration_id` FK nullable OR `group_id` FK nullable,
    - pledge fields: `amount_per_round`, `amount_min`, `amount_max`, `comment`, `verified`, timestamps.

6. **`donation_event_partner`** (new pivot)

    - `donation_event_id`, `partner_id`, optional display metadata.

7. **`groups`** (new, event scoped)

    - `id`, `donation_event_id`, `name`, `description`, timestamps.

8. **`group_memberships`** (new)
    - `id`, `group_id`, `athlete_registration_id`, timestamps.

### Relationships summary

- `DonationEvent hasMany AthleteRegistration`
- `ExternalUser hasMany AthleteRegistration`
- `ExternalUser hasMany Donations as donator` (via `donator_external_user_id`)
- `Donation belongsTo DonationEvent`
- `Donation belongsTo AthleteRegistration OR Group` (XOR)
- `DonationEvent belongsToMany Partner` through `donation_event_partner`
- `DonationEvent hasMany Group`
- `Group belongsToMany AthleteRegistration` through `group_memberships`

### Constraints and indexes

- **Uniqueness**

    - `donation_events.slug` unique.
    - `external_users.email` unique (or unique per normalized email; if historical duplicates exist, resolve in migration phase).
    - `athlete_registrations`: unique (`donation_event_id`, `external_user_id`).
    - `groups`: unique (`donation_event_id`, `name`).
    - `donation_event_partner`: unique (`donation_event_id`, `partner_id`).
    - `group_memberships`: unique (`group_id`, `athlete_registration_id`).
    - `donations`: unique (`donator_external_user_id`, `donation_event_id`, `athlete_registration_id`) when athlete target is used; unique (`donator_external_user_id`, `donation_event_id`, `group_id`) when group target is used.

- **Integrity checks**

    - DB check: exactly one donation target (`(athlete_registration_id IS NOT NULL AND group_id IS NULL) OR (athlete_registration_id IS NULL AND group_id IS NOT NULL)`).
    - DB check: `donations.donation_event_id` must match target event (enforced in app + migration validation; DB-level via trigger if needed).

- **Indexes**
    - All FKs indexed.
    - Lookups: `athlete_registrations(donation_event_id, verified)`, `donations(donation_event_id, donator_external_user_id)`,
      `external_user_auth_identities(token_hash, revoked_at, expires_at)`.

### Deletion / referential integrity

- **Foreign key strategy (financial tables)**
    - All financial / operational tables (`donations`, `athlete_registrations`, `group_memberships`) use `ON DELETE RESTRICT` (or equivalent `NO ACTION`) towards their parents to prevent accidental data loss.
    - `donation_events` is referenced by `athlete_registrations`, `groups`, and `donations` and must use `ON DELETE RESTRICT` on these FKs. Events cannot be deleted once any related registrations, groups, or donations exist.
    - `athlete_registrations` is referenced by `donations` and `group_memberships` and must use `ON DELETE RESTRICT`. Registrations cannot be deleted if they are used in any donation or group membership.
    - `groups` is referenced by `donations` and `group_memberships` and must use `ON DELETE RESTRICT`. Groups cannot be deleted if they are used in any donation or membership.
    - `donations` are never cascaded‑deleted via any parent FK; deletion is restricted once created (application‑level and DB‑level).
- **Foreign key strategy (configuration / link tables)**
    - Configuration/link tables that do not themselves hold financial records (e.g. `donation_event_partner`) may use `ON DELETE CASCADE` on their `donation_event_id` FK so that links are removed automatically when an unused event is deleted.
    - Where a configuration/link table is referenced by financial data (directly or indirectly), its FKs towards those financial tables must use `ON DELETE RESTRICT`.
- **External users and identities**
    - Prefer soft-deletes for `external_users`; hard delete only before first production usage or with a strict compliance workflow.
    - FKs from financial tables (`donations`, `athlete_registrations`) to `external_users` must use `ON DELETE RESTRICT` so that historical records prevent hard deletion of identities.

### Results representation

- Event results live in `athlete_registrations.rounds_done` and `rounds_estimated`.
- `external_users` remain stable identities across years.

---

## C) Migration strategy (downtime allowed)

### Phase 0 — Preparation

1. Full DB backup + checksum snapshot.
2. Prepare idempotent migration command with `--dry-run` and report output.
3. Prepare explicit historical event date mapping source (from known years currently hardcoded in views/content).

### Phase 1 — Expand schema

1. Create: `donation_events`, `external_users`, `external_user_auth_identities`, `athlete_registrations`, `donation_event_partner`, `groups`, `group_memberships`.
2. Extend `donations` with nullable `donation_event_id`, `donator_external_user_id`, `athlete_registration_id`, `group_id`.
3. Keep old columns/tables for compatibility during transition.

### Phase 2 — Seed historical events

1. Create one `donation_events` row per historical year.
2. If exact times are unknown for old events, store inferred defaults + metadata flag in `settings_json`.

### Phase 3 — Build external identity map (`athletes` + `donators` → `external_users`)

1. Build an index of normalized emails across `athletes` and `donators` (note: only `athletes.email` is constrained unique; `donators.email` may be duplicated).
2. For emails that are unique across both tables, create one `external_users` row and attach all matching `athletes` / `donators`.
3. For emails that appear on multiple `donators` rows:
    - If name and address are identical, auto-merge them into a single `external_users` entry and record all `legacy_donator_id`s.
    - If name or address differ (likely shared email or data inconsistency), write them to a "donator-email-conflicts" manual-review report/CSV and require resolution (deduplication or explicit shared-email modeling) before hard cutover.
4. For rows without email or still unmatched after the email-based pass, create new `external_users` keyed by other stable attributes (e.g. name, address, optional phone).
5. Persist a mapping table (`legacy_athlete_id` / `legacy_donator_id` → `external_user_id`) or a dedicated map table.

### Phase 4 — Backfill participations and donations

1. Determine the `donation_event` for each legacy athlete:
    - If all legacy data belongs to a single event, create one `donation_events` row flagged (e.g. via `is_legacy_default = true` in `settings_json` or similar metadata) and assign all legacy athletes to that event.
    - If multiple historical events existed, create an explicit mapping (for example a `legacy_event_mappings` config array or temporary table) that maps legacy athletes to `donation_event_id` based on agreed business rules (such as `created_at` windows, ID ranges, or manually curated lists). This mapping MUST be defined before running the backfill.
2. For each legacy athlete row, create one `athlete_registration` using the resolved `donation_event_id` from step 1 and copy event-scoped fields (`rounds_estimated`, `rounds_done`, `verified`, and legacy `partner_id`).
3. For each legacy donation row:
    - map `donator_id` to `donator_external_user_id`,
    - map `athlete_id` to `athlete_registration_id`,
    - set `donation_event_id` from the registration’s event (`athlete_registrations.donation_event_id`),
    - keep `group_id = null`.
4. Fill `donation_event_partner` using the same mapping as in step 1: for each `donation_event_id`, take the distinct `partner_id` values from the associated legacy athletes / registrations and insert `(donation_event_id, partner_id)` rows.

### Phase 5 — Backfill auth identities

1. Convert legacy static tokens (`athletes.login_token`, `donators.login_token`) into `external_user_auth_identities` records (`type=static_qr`, long expiry or policy-based expiry).
2. If one user had both tokens, keep both temporarily and mark one canonical for newly generated links.

### Phase 6 — Enforce constraints and cutover

1. Switch application to new reads/writes (event + external user scoped).
2. Enforce NOT NULL + unique + XOR constraints.
3. Keep legacy tables/columns read-only for one release; drop only after validation window.

### Rollback + integrity checks

- Rollback path: return app to legacy reads (since legacy columns remain initially).
- Integrity checks:
    - row count parity (legacy vs migrated donations),
    - amount parity per event and per donor,
    - every donation target belongs to same event,
    - every legacy athlete/donator mapped to exactly one external user.

---

## D) Application-layer changes (plan only)

### Event scoping

1. Introduce canonical event routes: `/events/{event:slug}/...`.
2. Add event context resolver (route first, fallback to current event setting).
3. Apply event scope to all dashboard/results/admin/export/invoice queries.

### Authentication architecture (internal vs external)

- **Internal admins**: keep `users` and existing admin auth, formalize as internal guard (`auth:internal` / `auth:admin`).
- **External participants**: authenticate `external_users` via separate external guard/provider (`auth:external`).
- This cleanly reflects privilege boundaries and aligns with Laravel guard architecture.

### External auth flow plan (UX + security)

1. Replace role-specific token pages with one external entry context:
    - e.g. `/events/{slug}/portal/{token}` resolves external user and shows role capabilities for that event (athlete, donor, both).
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

### Exports/invoicing impacts

- Invoice/export jobs must include `donation_event_id` filter.
- Invoice texts and metadata derive from selected event (`title`, year, dates), not hardcoded 2025 strings.

### Compatibility plan for naming (`donators` misspelling)

- Do **not** rename abruptly.
- During transition, keep legacy `donators` table as source for migration mapping.
- Rename/deprecate only in a dedicated follow-up with compatibility view/model alias if required.

### Identity modeling decision analysis (revised)

1. **(i) Keep separate `athletes` + `donators`**

    - Pros: lowest immediate migration risk.
    - Cons: poor UX for dual-role humans, duplicated auth state, harder write-permission model.

2. **(ii) Introduce unified `external_users` + role-by-relationship (recommended)**

    - Pros: one external identity, cleaner UX, secure guard separation (`internal` vs `external`), aligns with Laravel architecture for multi-auth and future authorization.
    - Cons: migration complexity is higher than (i), especially identity deduping.

3. **(iii) Merge externals into admin `users`**
    - Pros: single auth table.
    - Cons: weak privilege boundary, security/operational risk, not recommended.

**Conclusion**: choose **(ii)** now because it directly solves the dual-role UX/security issue and supports planned write operations without conflating admin and external privileges.

---

## E) Testing & verification

### Minimal test additions/updates

1. **Schema/constraint tests**

    - `external_users` uniqueness/dedup behavior.
    - `donations` target XOR constraint.
    - `athlete_registrations` unique per (`event`, `external_user`).

2. **Feature tests**

    - External login resolves one portal for dual-role user.
    - External guard cannot access admin routes; internal guard cannot access external write endpoints unless explicitly allowed.
    - Donor can sponsor same athlete in different events.
    - Event-scoped results/dashboard totals are isolated.

3. **Migration tests**

    - dry-run reports identity merge decisions,
    - backfill parity checks for counts and sums,
    - conflict fixture test (same email, differing names) requires manual resolution path.

4. **Regression tests**
    - invoice generation still works with event filter,
    - legacy token links (QR codes, emailed links) continue to work via compatibility redirect throughout the one-release transition window,
    - existing bookmarks and saved links to legacy routes (e.g. dashboards, invoices, athlete/donor views) remain functional during the transition window via redirect or compatibility routing.

### Post-migration verification checklist

- Identity mapping

    - 100% of legacy athlete/donator rows mapped to exactly one external user.
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

Adopt **multi-event + unified external identity**:

- Create `donation_events` and `athlete_registrations` for correct event scoping.
- Replace split athlete/donor person identity with `external_users`.
- Keep admins in `users` under internal guard; use separate external guard for participant-facing auth.
- Preserve QR convenience, but treat static tokens as bootstrap and require short-lived auth for write actions.

**Risk**: Medium-High (identity merge + auth refactor + migration quality).
**Effort**: High (schema, migration tooling, route/auth refactor, UI portal unification).
**Why this is still recommended**: it fixes the core dual-role UX/security problem early, and prevents compounding complexity when write operations (profile changes, confirmations, invitations) are introduced.
