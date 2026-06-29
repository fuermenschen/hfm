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

### Phase 1: Foundation

- [ ] Add `donorRegistrationIsOpen()` method to `DonationEvent` model (uses `registration_opens_at` + `donor_registration_closes_at`, mirrors `athleteRegistrationIsOpen()`)
- [ ] Create `CreateDonationAction` (mirrors `CreateAthleteRegistrationAction`)
  - Validates event is open for donor registration
  - Normalizes external user data (new vs existing)
  - Validates `athlete_registration_id` belongs to current event and is verified
  - Creates `ExternalUser` if new, creates `Donation` with `verified = false`
  - Allows multiple donations per donor per event (different athletes)
- [ ] Create `DonorRegistrationWizard` Livewire component
  - Reuse step navigation pattern from `AthleteRegistrationWizard`
  - Same email lookup / rate limiting / timing protection
  - Same honeypot spam protection

### Phase 2: Wizard Component

- [ ] Implement `mount()` — load current event, verified athlete registrations for selection (with sportType, partner, externalUser eager-loaded)
- [ ] Implement `rulesForStep()` with per-step validation
- [ ] Implement `next()`, `back()`, `goTo()`, `restart()`, `submit()`
- [ ] Implement `visibleSteps()` — dynamic step list based on auth state and participation type
- [ ] Implement `lookupExternalUserByEmail()` — reuse pattern, send `ContinueDonorRegistration` notification
- [ ] Implement `updatedAthleteRegistrationId()` — load selected athlete context (name, sport, partner, rounds, comment) into public properties for reactive display
- [ ] Implement submit flow — call `CreateDonationAction`, send `ConfirmDonorRegistration` notification

### Phase 3: Notifications

- [ ] Create `ConfirmDonorRegistration` notification (mirrors `ConfirmAthleteRegistration`)
- [ ] Create `ContinueDonorRegistration` notification (mirrors `ContinueAthleteRegistration`)

### Phase 4: Views

- [ ] Create `resources/views/forms/donor-registration-wizard.blade.php`
  - Mirror athlete wizard structure (progress bar, step navigation, back/next buttons)
  - Step-specific content:
    - `start`: email lookup fields
    - `login-link-sent`: email sent confirmation
    - `personal`: personal data fields (country selector for CH/DE/AT + phone with country code selector)
    - `donation`: athlete selector (radio cards or searchable select), **reactive athlete context card** (privacy name, sport, partner, rounds, comment), amount fields with "Wie funktioniert das?" info, comment, privacy checkbox
    - `submitted`: success screen + "Weitere:n Sportler:in unterstützen" button to restart wizard
- [ ] Update `resources/views/pages/become-donor.blade.php`
  - Add `BecomeDonorController` usage (check existing donation, event open state, verified athletes count)
  - Mount `donor-registration-wizard` when event is open AND verified athletes exist
  - Show "Aktuell sind noch keine Sportler:innen angemeldet" message when no verified athletes
  - Show appropriate messages when closed

### Phase 5: Controller & Routes

- [ ] Create `BecomeDonorController` (mirrors `BecomeAthleteController`)
  - Inject `CurrentDonationEventService`
  - Pass `currentDonation` (existing donation for this event + user) to view
  - Pass `hasVerifiedAthletes` boolean to view (controls form availability)
- [ ] Update route to use controller instead of direct view

### Phase 6: Tests

- [ ] Unit/Feature tests for `CreateDonationAction`
- [ ] Feature tests for `DonorRegistrationWizard` (step navigation, validation, submit)
- [ ] Feature test for `BecomeDonorController` (open/closed/already-registered states)
- [ ] Browser test for full donor registration journey
- [ ] Update existing `PublicFormsHoneypotTest` for new wizard component

### Phase 7: Cleanup

- [ ] Delete `BecomeDonorForm` component
- [ ] Delete `become-donor-form.blade.php` view
- [ ] Delete `BecomeAthleteForm` (already deleted) and `BecomeDonorForm` test files
- [ ] Remove dead-code ignore comments from old form

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
