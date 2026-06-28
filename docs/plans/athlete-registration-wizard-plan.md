# Athlete Registration Wizard Plan

## Context

Athlete registration has been rebuilt as a guided wizard on top of `external_users` and `athlete_registrations`; legacy `athletes` table is gone.

The old `BecomeAthleteForm` has been removed. The new flow uses the current `ExternalUser` + `AthleteRegistration` model and keeps persistence in actions.

## Target

Create a guided athlete registration wizard that asks only relevant questions, supports returning external users, creates an event-scoped `athlete_registration`, and records email-confirmed registration state for later donor-facing selection.

The wizard is shown on the public athlete registration page when the current donation event has athlete registration open.

## UX Principles

- Use a step-by-step flow with progressive disclosure.
- Ask for email first so returning and new participants land on the relevant path.
- Reuse known `ExternalUser` data instead of asking again.
- Keep each step focused on one topic or one small group of related fields.
- Use inline validation and visible help text.
- Keep back/change actions simple and predictable.

## Key Decisions

- Existing external users log in through the wizard by entering email directly in the first step.
- Login link carries a signed redirect back to the athlete registration page.
- Login and confirmation links intentionally create persistent external-user sessions.
- External user personal details are not editable in the wizard.
- Athlete registration confirmation is separate from email ownership. Email ownership is already proven by signed login link.
- Confirmation links open the user's portal; the registration is confirmed by an explicit portal action.
- New athlete registrations carry confirmation state for later donor-facing selection.
- Previous donors are notified only after registration confirmation.
- Previous donor notification uses athlete privacy name.
- Previous donors means all donors from earlier event registrations for the same external user, regardless donation verification state.
- Previous donor notification is enabled by default; disabling it shows a warning.
- Every registration submission requires explicit privacy/data-use consent in the wizard.
- Athlete registration is limited to Swiss residents with Swiss ZIP and telephone format.
- Existing current-event registrations cannot be changed from the public wizard; users are directed to log in and manage them in the portal.
- Deleted external-user accounts require manual admin/support contact before reuse.
- New-user pre-confirmation profile poisoning is an accepted risk; notification copy tells recipients to contact the team if they did not start the flow.
- Group participation is out of scope.

## Data Shape

Use existing registration confirmation and add donor-notification preference to `athlete_registrations`.

- `verified` boolean: registration is confirmed when true.
- `notify_previous_donors` boolean default true.

Keep existing unique constraint on `(donation_event_id, external_user_id)` as duplicate-registration guard.

Donor-facing confirmed-only filtering is handled outside this plan.

## Flow Outline

### 1. Start

- If external user is logged in, skip identity questions and continue to registration details.
- If not logged in, ask for email and email confirmation.
- If email belongs to an external user, send signed login link and stop until authentication.
- If email is unknown, continue to personal details with email prefilled.

### 2. Personal Details

- New users enter personal details for Swiss residence only.
- Create `ExternalUser` only on final submit; block existing email addresses and route those users through the returning-participant login path.

### 3. Registration Details

- Ask sport type, estimated rounds, partner/equal split, optional comment.
- Validate sport type and partner/equal-split options against current donation event.

### 4. Previous Donors

- Show only for authenticated external users with actual previous donor history.
- Default to informing previous donors.
- If unchecked, show warning that first donations may take longer.

### 5. Registration Created

- Create `ExternalUser` for new participants; reuse logged-in external user for returning participants.
- Create `AthleteRegistration` with `verified = false`.
- Send athlete a confirmation email.
- Show message that registration must be confirmed from email.
- If any registration already exists for the current event, block public wizard submission and direct the user to log in to the portal.

### 6. Registration Confirmation

- Confirmation email contains a signed registration-confirm link.
- Link lifetime matches current login links.
- Opening a valid signed link opens the user's portal.
- The portal shows a confirmation button for owned unverified registrations.
- Pressing the portal confirmation button sets `verified = true` only on the first successful transition.

### 7. After Confirmation

- Registration is confirmed and ready for later donor-facing selection.
- If `notify_previous_donors` is true, notify previous donors.

## Architecture Outline

Use the fewest seams that keep side effects testable.

- Livewire component owns wizard state, current step, and rendering.
- `CreateAthleteRegistrationAction` owns event-scoped registration creation, optional `ExternalUser` creation, and sport type / partner / equal-split validation in one transaction.
- Existing signed-login flow handles returning-participant authentication and resume redirect.
- Confirmation notification owns email copy and signed confirmation URL generation.
- Confirmation link route validates the signed URL, logs the external user in, and redirects to the portal.
- Confirmation POST route confirms the authenticated user's owned registration and redirects to the confirmation page.
- Previous donor notification is triggered inline only after the first atomic transition to confirmed.

## Suggested Implementation Order

- [x] Add minimal schema fields for registration confirmation and previous donor notification.
- [x] Build new wizard component and mount it on the athlete registration page.
- [x] Add one registration creation action for logged-in and new external users.
- [x] Use existing signed-login flow for returning participants.
- [x] Add confirmation notification and signed confirmation route.
- [x] Add previous donor notification after first confirmation.
- [x] Block existing current-event registrations in the public wizard and direct users to the portal.
- [x] Add visible submit/action errors to the wizard.
- [x] Validate sport types against current event.
- [x] Add explicit privacy/data-use consent.
- [x] Add spam protection to the public wizard.
- [x] Route browser journey tests through CI e2e workflow.
- [x] Remove old `BecomeAthleteForm` component and view.
- [x] Restrict athlete registration personal details to Switzerland.

## Testing Focus

- Wizard starts and branches correctly for guest, returning participant, and logged-in external user.
- Public page shows wizard only when current event athlete registration is open.
- Current-step validation blocks invalid transitions and clears now-irrelevant branch answers.
- Email lookup sends returning participant login link and unknown emails continue to personal details.
- Returning participant login link returns to the wizard without manual restart.
- Registration creation creates correct `ExternalUser` and `AthleteRegistration` rows for new users, and correct `AthleteRegistration` rows for authenticated external users.
- Duplicate event registration is blocked.
- Confirmation marks registration confirmed.
- Previous donors are notified only after confirmation and only when opted in.
- Disabling previous donor notification shows warning and prevents those notifications.
- Sport types unavailable for the current event are hidden and rejected.
- Privacy/data-use consent is required before submit.
- Invalid signed confirmation links do not authenticate the external user.
- Confirmation is idempotent and triggers previous donor notifications only once.
- Existing current-event registrations are blocked and direct the user to log in to the portal.

## Non-Goals

- Editing external user personal details.
- Group registration.
- Reworking donor registration or donor-facing athlete selection.
