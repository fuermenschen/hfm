# Donor Registration Wizard Refactor Plan

## Goal

Replace `BecomeDonorForm` (single-page, currently parked) with a multi-step `DonorRegistrationWizard` following the same pattern as `AthleteRegistrationWizard`.

## Current State

- `BecomeDonorForm` exists but `save()` shows "Anmeldung geschlossen" toast
- Donor form commented out in `become-donor.blade.php`
- Donors = `ExternalUser` records linked via `donations.donor_external_user_id`
- No `CreateDonationAction` exists
- No `donorRegistrationIsOpen()` method on `DonationEvent`
- No `BecomeDonorController` (page served by route -> view directly)

## Wizard Steps

| Step | Key | Fields | Notes |
|------|-----|--------|-------|
| 1 | `start` | `returning_email`, `returning_email_confirmation` | Email lookup, rate-limited, timing attack protected |
| 2 | `personal` | `first_name`, `last_name`, `address`, `zip_code`, `city`, `country_of_residence`, `phone_country`, `phone_national`, `email`, `email_confirmation` | New donors only; skipped for returning/authenticated |
| 3 | `donation` | `athlete_registration_id`, `amount_per_round`, `amount_min`, `amount_max`, `comment`, `privacy_accepted` | Core donation config |
| 4 | `submitted` | - | Confirmation screen |

### Conditional Steps

- `login-link-sent` — shown when returning email found (same as athlete wizard)
- `personal` — skipped for authenticated external users and returning donors

### UX: Athlete Selection Context

When a donor selects an athlete, the wizard must reactively show context so the donor knows who they're supporting:

- **Athlete privacy name** (e.g. "Francesca L.") — **NEVER show full name**, always use `ExternalUser::privacyName()`
- **Sport type** (e.g. "Laufen")
- **Benefizpartner:in** the athlete collects for (e.g. "Stiftung Kinderherz")
- **Estimated rounds** (e.g. "~11 Runden")
- **Athlete comment** (if any)

Implementation: `updatedAthleteRegistrationId()` loads the selected registration's related data into public properties (`currentAthleteName`, `currentSportType`, `currentPartner`, `currentRounds`, `currentAthleteComment`). Displayed as a callout/card below the athlete selector.

### UX: Amount Explanation

Preserve the old form's "Wie funktioniert das?" info for amounts. Show as a collapsible callout or info button explaining:
- Amount per round × athlete's rounds = total contribution
- Min/max boundaries cap the total
- 100% goes to the athlete's chosen Benefizpartner:in

### Open Questions (Resolved)

- **Donation verification**: ✅ `verified` field already exists on `donations` table. Require email verification like athletes.
- **Multiple donations per event**: ✅ Allow. Donor can support multiple athletes. Add "Weitere:r Sportler:in unterstützen" button on `submitted` step to restart wizard.
- **No verified athletes**: ✅ Disable form with message "Aktuell sind noch keine Sportler:innen angemeldet. Versuche es später erneut."

## Tasks

### Phase 1: Foundation ✅

- [x] Add `donorRegistrationIsOpen()` method to `DonationEvent` model (uses `registration_opens_at` + `donor_registration_closes_at`, mirrors `athleteRegistrationIsOpen()`)
- [x] Create `CreateDonationAction` (mirrors `CreateAthleteRegistrationAction`)
  - Validates event is open for donor registration
  - Normalizes external user data (new vs existing)
  - Validates `athlete_registration_id` belongs to current event and is verified
  - Creates `ExternalUser` if new, creates `Donation` with `verified = false`
  - Allows multiple donations per donor per event (different athletes)
- [x] Create `DonorRegistrationWizard` Livewire component
  - Reuse step navigation pattern from `AthleteRegistrationWizard`
  - Same email lookup / rate limiting / timing protection
  - Same honeypot spam protection
- [x] Create `ConfirmDonorRegistration` and `ContinueDonorRegistration` notifications
- [x] Add `verified` to `Donation` model fillable + casts
- [x] Add stub route `portal.donation.confirm`
- [x] Write tests for `CreateDonationAction` and `DonorRegistrationWizard`
- [x] Fix authenticated-user `restart()` flow so it returns to `donation`
- [x] Add `DonorRegistrationWizard` to public honeypot coverage

### Phase 2: Wizard Component ✅

Shipped in commit `919fb2a` alongside Phase 1. Component lives at `app/Components/DonorRegistrationWizard.php` (641 lines). Tests pass (`tests/Feature/DonorRegistrationWizardTest.php`, 21/21 with `CreateDonationActionTest`).

- [x] Implement `mount()` — load current event, verified athlete registrations for selection (with sportType, partner, externalUser eager-loaded) (`DonorRegistrationWizard.php:136`)
- [x] Implement `rulesForStep()` with per-step validation (`:209`)
- [x] Implement `next()`, `back()`, `goTo()`, `restart()`, `submit()` (`:155`/`:384`/`:407`/`:422`/`:461`)
- [x] Implement `visibleSteps()` — dynamic step list based on auth state and participation type (`:359`)
- [x] Implement `lookupExternalUserByEmail()` — reuse pattern, send `ContinueDonorRegistration` notification (`:294`)
- [x] Implement `updatedAthleteRegistrationId()` — load selected athlete context (name, sport, partner, rounds, comment) into public properties for reactive display (`:577`)
- [x] Implement submit flow — call `CreateDonationAction`, send `ConfirmDonorRegistration` notification (`:494`/`:537`)

### Phase 3: Notifications ✅

Shipped in commit `919fb2a`. Files at `app/Notifications/ConfirmDonorRegistration.php` and `app/Notifications/ContinueDonorRegistration.php`.

- [x] Create `ConfirmDonorRegistration` notification (mirrors `ConfirmAthleteRegistration`)
- [x] Create `ContinueDonorRegistration` notification (mirrors `ContinueAthleteRegistration`)

### Phase 4: Views ✅

Wizard view shipped in commit `919fb2a` (`resources/views/forms/donor-registration-wizard.blade.php`, 318 lines). Page view wired in this phase.

- [x] Create `resources/views/forms/donor-registration-wizard.blade.php`
  - Mirror athlete wizard structure (progress bar, step navigation, back/next buttons)
  - Step-specific content:
    - `start`: email lookup fields
    - `login-link-sent`: email sent confirmation
    - `personal`: personal data fields (country selector for CH/DE/AT + phone with country code selector)
    - `donation`: athlete selector (radio cards or searchable select), **reactive athlete context card** (privacy name, sport, partner, rounds, comment), amount fields with "Wie funktioniert das?" info, comment, privacy checkbox
    - `submitted`: success screen + "Weitere:n Sportler:in unterstützen" button to restart wizard
- [x] Update `resources/views/pages/become-donor.blade.php`
  - Add `BecomeDonorController` usage (check event open state, verified athletes count)
  - Mount `donor-registration-wizard` when event is open AND verified athletes exist
  - Show "Aktuell sind noch keine Sportler:innen angemeldet" message when no verified athletes
  - Show appropriate messages when closed

### Phase 5: Controller & Routes ✅

- [x] Create `BecomeDonorController` (mirrors `BecomeAthleteController`)
  - Inject `CurrentDonationEventService`
  - Pass `hasVerifiedAthletes` boolean to view (controls form availability)
  - Multiple donations per donor per event allowed — wizard always shown when open + verified athletes exist. `CreateDonationAction` prevents same-athlete duplicates via unique constraint.
- [x] Update route to use controller instead of direct view

### Phase 6: Tests

- [x] Unit/Feature tests for `CreateDonationAction`
- [x] Feature tests for `DonorRegistrationWizard` (step navigation, validation, submit)
- [x] Feature test for `BecomeDonorController` (open/closed/no-athletes/admin states)
- [ ] Browser test for full donor registration journey
- [x] Update existing `PublicFormsHoneypotTest` for new wizard component

### Phase 7: Cleanup ✅

- [x] Delete `BecomeDonorForm` component
- [x] Delete `become-donor-form.blade.php` view
- [x] Delete `BecomeAthleteForm` (already deleted) and `BecomeDonorForm` test files
- [x] Remove dead-code ignore comments from old form

## Key Differences from Athlete Wizard

| Aspect | Athlete | Donor |
|--------|---------|-------|
| Sport type selection | Yes | No |
| Rounds estimated | Yes | No |
| Partner selection | Yes | No |
| Athlete selection | No | Yes (from verified registrations) |
| Amount per round | No | Yes |
| Amount min/max | No | Yes (optional) |
| Previous donors step | Yes | No |
| Country of residence | CH only | CH, DE, AT |
| Phone format | Swiss only (`079 123 45 67`) | Multi-country (+41/+49/+43) |
| Privacy requirement | Full name shown to admins | **NEVER show athlete full name**, always use `privacyName()` |
| Restart after submit | No | Yes ("Weitere:n Sportler:in unterstützen" button) |

## Dependencies / Blockers

- Athlete registrations must be verified before donors can select them
- `DonationEvent.donor_registration_closes_at` already exists in DB
- `donations` table already has `donor_external_user_id`, `athlete_registration_id`, and `verified` columns
- Multiple donations per donor per event allowed (one per athlete)
