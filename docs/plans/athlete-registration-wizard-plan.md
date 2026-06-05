# Athlete Registration Wizard Plan

## Context

Athlete registration is currently parked. Public page shows registration closed and the old Livewire form is commented out. Existing domain model already uses `external_users` and `athlete_registrations`; legacy `athletes` table is gone.

Current form still carries old shape: one large page, mixed validation/UI/business logic, old registration assumptions, and no active persistence path. New flow should rebuild from the current `ExternalUser` + `AthleteRegistration` model instead of reviving the old component.

## Target

Create a guided athlete registration wizard that asks only relevant questions, supports returning external users, creates an event-scoped `athlete_registration`, and confirms that registration by email before it becomes visible to donors.

The wizard stays hidden from public users until registration reopening is handled separately.

## UX Principles

- Use a step-by-step flow with progressive disclosure.
- Ask branching questions early so irrelevant steps disappear.
- Reuse known `ExternalUser` data instead of asking again.
- Keep each step focused on one topic or one small group of related fields.
- Store unfinished guest wizard input in the browser for about one day, then expire it.
- Use inline validation and visible help text; do not rely on placeholders for instructions.
- Show a final review screen before submission.
- Keep back/change actions simple and predictable.

## Key Decisions

- Existing external users log in through the wizard by entering email directly in the first branch.
- Login link carries minimal resume context in signed query arguments; no database draft is needed for this path.
- Browser-stored draft handles unfinished wizard state for new-user paths.
- External user personal details are not editable in the wizard.
- Athlete registration confirmation is separate from email ownership. Email ownership is already proven by signed login link.
- New athlete registrations are not visible/selectable for donors until registration confirmation.
- Previous donors are notified only after registration confirmation.
- Previous donor notification uses athlete privacy name.
- Previous donors means all distinct donors from earlier events for same athlete.
- Previous donor notification is enabled by default; disabling it shows a warning.
- Group participation is out of scope; wizard step order supports adding and skipping steps.

## Data Shape

Use existing registration confirmation and add donor-notification preference to `athlete_registrations`.

- `verified` boolean: registration is confirmed when true.
- `notify_previous_donors` boolean default true.

Keep existing unique constraint on `(donation_event_id, external_user_id)` as duplicate-registration guard.

Add a confirmed-registration query path so donor-facing lists only include confirmed registrations.

## Flow Outline

### 1. Start

- If external user is logged in, skip identity questions and continue to registration details.
- If not logged in, ask whether user has participated before.
- If yes, ask for email and send signed login link with resume context.
- If no, continue to personal details.

### 2. Personal Details

- New users enter personal details.
- Store step progress in browser with one-day expiry.
- Create `ExternalUser` only on final submit.

### 3. Registration Details

- Ask sport type, estimated rounds, partner/equal split, optional comment.
- Validate options against current donation event.

### 4. Previous Donors

- Show only when athlete has previous donors.
- Default to informing previous donors.
- If unchecked, show warning that first donations may take longer.

### 5. Review

- Show only relevant sections.
- Allow changing prior answers without restarting full flow.
- Submit creates registration.

### 6. Registration Created

- Create `ExternalUser` for new participants; reuse logged-in external user for returning participants.
- Create `AthleteRegistration` with `verified = false`.
- Send athlete a confirmation email.
- Show message that registration must be confirmed from email before donors can select them.

### 7. Registration Confirmation

- Confirmation email contains login link with registration-confirm intent.
- Link lifetime matches current login links.
- Opening link logs user in and lands on the registration confirmation page.
- User confirms registration by clicking “Registrierung als Sportler:in bestätigen”.
- Set `verified = true`.

### 8. After Confirmation

- Registration becomes visible/selectable for donors.
- If `notify_previous_donors` is true, notify previous donors.

## Architecture Outline

Use small seams, not a heavy framework.

- Livewire component owns wizard state, current step, and rendering.
- Browser draft helper stores and clears unfinished guest state.
- Registration action owns creation of `ExternalUser` and `AthleteRegistration` in one transaction.
- Confirmation action owns confirming the registration.
- Previous donor service owns selecting distinct previous donors.
- Events/listeners handle side effects after database commit.
- Notifications own email copy and links.

## Suggested Implementation Order

- [ ] Add minimal schema fields for registration confirmation and previous donor notification.
- [ ] Add confirmed-only filtering where donor-facing athlete lists are built.
- [ ] Build new wizard component behind existing hidden registration page.
- [ ] Add browser draft persistence for new-user wizard state.
- [ ] Add inline existing-user login branch with signed resume context.
- [ ] Add registration creation action.
- [ ] Add registration confirmation link and confirmation action/page.
- [ ] Add previous donor notification after confirmation.
- [ ] Replace old hidden form reference with new hidden wizard reference.
- [ ] Remove old athlete form once new tests cover the flow.

## Testing Focus

- Wizard starts and branches correctly for guest, returning participant, and logged-in external user.
- Returning participant login link returns to the wizard/confirmation context without manual restart.
- Browser draft restores new-user wizard input and expires after about one day.
- Registration creation creates correct `ExternalUser` and `AthleteRegistration` rows.
- Duplicate event registration is blocked.
- Unconfirmed registrations do not appear in donor-facing athlete selection.
- Confirmation marks registration confirmed and makes it visible.
- Previous donors are notified only after confirmation and only when opted in.
- Disabling previous donor notification shows warning and prevents those notifications.

## Non-Goals

- Publicly reopening registration.
- Editing external user personal details.
- Group registration.
- Database-backed wizard drafts.
- Reworking donor registration beyond confirmed-athlete filtering.
