# Event-Scoped Invoice Reactivation Plan

Restore donor invoices after the multi-event refactor. Keep workflow close to legacy behavior while adding event isolation and safeguards around creation, reminders, and deletion.

Webling API reference: <https://vfme.webling.ch/api>

## Scope

- One invoice per external user and donation event.
- Restore individual and bulk create, send, reminder, download, and status-refresh actions.
- Keep association donation invoices separate and unchanged.
- Ignore existing remote legacy invoices. Do not import or match them.
- Keep Webling authoritative after invoice creation. Correct unsettled invoices by deleting and recreating them.

## Deliberate Exclusions

- No automatic Webling synchronization or invoice-specific scheduler.
- No replication cursor or daily audit.
- No deleted-invoice history or soft deletes.
- No local-versus-Webling mismatch detection.
- No full remote accounting-data cache.
- No PDF template redesign.
- No group sponsorship support.
- No automatic update of remote invoice content.

Add automatic refresh only when manual event refresh becomes an observed burden. Add invoice history only when legal or operational requirements demand it. Reuse existing queue infrastructure for creation and mail; do not add a dedicated invoice worker or scheduler.

## Business Rules

### Donations

- Include all donations, including unconfirmed donations, for the selected external user and donation event.
- Do not skip unconfirmed donations; billing them is intentional.
- Preserve legacy minimum, maximum, and zero-line calculations.
- Do not create an invoice without billable lines.
- Calculate and store money as integer CHF cents.

### Creation

- Warn and require explicit confirmation before event end.
- Create one local invoice row before calling Webling.
- Use one immutable source snapshot for both Webling invoice data and PDF generation.
- Capture every value that appears in either document, including normalized lines, debtor details, dates, totals, creditor and QR-bill settings, and letter content or template version.
- Never update Webling invoice content after creation.
- Use event-local time for invoice and due dates.

### Sending

- Block sending until event end.
- Require current donor email, local PDF, and active Webling Debitor ID.
- Dispatch through the application's configured mail queue. If that queue is synchronous, delivery happens in the request; otherwise existing queue infrastructure processes it.
- Set `invoice_sent_at` only after dispatch succeeds. Leave it unchanged when dispatch fails.
- Resending requires confirmation.
- A successful resend updates `invoice_sent_at` to the latest queue time.

### Reminders

- Require an invoice that was sent previously.
- Read current Debitor state from Webling before sending.
- Require an unpaid invoice whose due date has passed.
- Block paid, written-off, missing, unknown, and not-yet-overdue invoices.
- Fail closed when Webling is unavailable.
- Set `invoice_reminder_sent_at` only after dispatch succeeds. Leave it unchanged when dispatch fails.
- Resending requires confirmation.
- A successful reminder resend updates `invoice_reminder_sent_at` to the latest queue time.

### Deletion

- Read current Debitor state from Webling before deletion.
- Allow deletion only for unpaid and unsettled invoices.
- Block partially paid, paid, written-off, unknown, and unavailable states.
- Treat remote `204` and `404` as successful deletion outcomes.
- If local creation never reached a Webling Debitor ID, clean up the local incomplete invoice without a remote request.
- Set `remote_deleted_at`, delete the local PDF, and clear remote, PDF, snapshot, state, and mail fields on the existing row.
- Reuse the same row when creating a replacement invoice and clear `remote_deleted_at` after successful remote creation.

## Persistence

Create `DonorEventInvoice` backed by `donor_event_invoices`.

Columns:

- `external_user_id`
- `donation_event_id`
- `webling_debitor_id`
- `webling_invoice_number`
- `webling_state`
- `webling_due_date`
- `webling_total_cents`
- `webling_remaining_cents`
- `webling_synced_at`
- `pdf_disk`
- `pdf_path`
- `invoice_sent_at`
- `invoice_reminder_sent_at`
- `source_snapshot`
- `source_total_cents`
- `remote_deleted_at`
- timestamps

Constraints and relationships:

- Unique index on `(external_user_id, donation_event_id)`.
- Restrict deletion of referenced external users and donation events.
- Typed relationships from invoice to `ExternalUser` and `DonationEvent`.
- Inverse invoice relationships on both existing models.
- No `SoftDeletes` and no generated active-key column.

Derive rather than store:

- Webling Debitor URL from Webling configuration and Debitor ID.
- Stable comment marker as `HFM-DONOR-INVOICE:{local_invoice_id}`.
- Overdue status from raw Webling state and due date.

The source snapshot contains only data needed to reproduce the issued invoice, including normalized lines in cents, debtor details, invoice date, due date, total, creditor and QR-bill settings, and letter content or template version. Both Webling payload and PDF generation consume this snapshot; neither reads mutable invoice settings after the snapshot is stored.

## Status Model

Store raw Webling state:

- `open`
- `partially paid`
- `paid`
- `writeoff`

Derive display status in this order:

1. Remote deleted.
2. Paid.
3. Written off.
4. Overdue open or partially paid.
5. Partially paid.
6. Sent.
7. Created.
8. Not created.

Unknown Webling states remain visible as unknown. Block sending, reminders, deletion, recreation, and corresponding bulk actions; allow read-only details and an existing local PDF download unless the remote invoice is confirmed deleted. Do not store a separate writable `payment_status`.

## Webling Safety

Webling has no idempotency key. Protect invoice creation with the stable comment marker and one application lock per local invoice.

Creation sequence:

1. Create or load the unique local invoice row.
2. Commit local database changes.
3. Acquire an application lock keyed by local invoice ID before creating or replacing its snapshot.
4. Store its immutable source snapshot when no remote Debitor ID exists.
5. Use stored Debitor ID when present.
6. Otherwise search Webling for the exact comment marker.
7. Persist one matching Debitor ID, fail if multiple matches exist, or create a Debitor when none exists.
8. Persist Debitor ID before generating the PDF.
9. Generate and store the PDF from the same snapshot.

Never hold a database transaction across Webling HTTP calls.

Never blindly retry Debitor `POST`. Every creation retry searches for the marker first. Bounded retries may be used for `GET` and idempotent `DELETE` requests on `429`, `500`, and `503`. A connection failure never means remote deletion.

Webling response handling must classify thrown HTTP failures into confirmed `404`, authentication or permission failure, rate limiting, and transient failure. Only confirmed `404` means remote deletion.

## PDF and Download Behavior

- Store each generated PDF on the configured disk and path. It is immutable until invoice deletion or remote-deletion cleanup.
- Individual download requires an active local invoice, readable cached file, and authorization for the selected event.
- A missing local file disables send and download, reports the failure, and never regenerates a document from changed data.
- Bulk ZIP download includes only selected invoices with readable cached PDFs in the selected event. Report skipped or missing files without blocking valid downloads.

## Manual Status Refresh

Add one admin action to refresh locally tracked invoices for the selected event.

For each tracked Debitor:

- Fetch current Webling data.
- Update raw state, due date, invoice number, total cents, remaining cents, and sync timestamp.
- On confirmed `404`, set `remote_deleted_at`, delete the local PDF, clear lifecycle fields, and disable send, reminder, recreation, and download actions.
- Preserve cached values and report failure for all other errors.

This same direct read is sufficient at current invoice volume. No replication API integration is planned.

## Admin Features

Use the existing event filter. Invoice actions require exactly one selected event.

Restore:

- Create and recreate.
- Send and resend.
- Reminder and reminder resend.
- PDF download.
- Webling link.
- Delete unsettled invoice.
- Bulk create, send, reminder, and PDF ZIP download.
- Manual status refresh.
- Event-scoped payment summary.

Individual actions use the same eligibility rules as bulk actions. Bulk actions validate every selected donor against the selected event, process each eligible invoice independently, and report created, sent, skipped, and failed counts. A failed item does not roll back unrelated items. Bulk reminders perform the same live Webling read for every selected invoice.

Confirmation is required for resend actions, early creation, and every destructive deletion. Bulk create and send show the eligible and skipped counts before dispatch.

Display:

- Derived status.
- Invoice number.
- Total and remaining amount.
- Sent and reminder timestamps.
- Last refresh time or stale/error state.

The event-scoped payment summary counts not-created, created, sent, overdue, partially paid, paid, written-off, deleted, and unknown invoices. It uses only rows for the selected event.

Admin queries must scope donations and invoices by both external user and selected donation event. Never combine invoices from different events.

## Implementation

### 1. Persistence

- Add invoice migration and model.
- Add factory and relationships.
- Use normal donor/event composite uniqueness.

### 2. Event-Scoped Data Collection

- Update `CollectDonorInvoiceDataAction` to accept invoice context.
- Query all donations explicitly by `external_user_id` and `donation_event_id`.
- Normalize calculations to integer cents.
- Return immutable creation snapshot data.

### 3. Invoice Creation

- Use one creation action and one queued creation job.
- Reuse existing Webling invoice and letter services.
- Recover remote creation through stable marker lookup.
- Generate Webling invoice and PDF from the same snapshot.

### 4. Lifecycle Actions

- Restore send, resend, reminder, download, deletion, and recreation.
- Add event-end guards and live Webling safety checks.
- Reuse the local row after deletion.

### 5. Admin UI

- Restore row and bulk actions in the existing event-filtered donor table.
- Restore event-scoped payment summary.
- Add manual status refresh and clear failure reporting.

### 6. Reconnect Existing Invoice Code

- Refactor `app/Actions/CollectDonorInvoiceDataAction.php` and `app/Services/DonorInvoiceService.php` from dormant global-donor behavior to invoice and event context.
- Replace the dormant split donor-invoice job flow with one creation job that owns marker recovery, Debitor creation, and PDF generation in sequence.
- Update `app/Services/Webling/Invoice/Dto/InvoiceCreateData.php` and `app/Services/Webling/Invoice/WeblingInvoiceService.php` to write and find the stable Debitor comment marker.
- Update `app/Services/Webling/WeblingApiService.php` to expose status-aware errors for direct reads and deletion.
- Update the letter service/template inputs so PDF generation uses the saved snapshot, not mutable live invoice settings.
- Extend `app/Components/AdminPersonTable.php` and its donor-table view with row, bulk, download, and refresh actions.
- Mount `app/Components/AdminPaymentStatusSummary.php` and its view with selected-event invoice data.
- Retire or leave unused `app/Jobs/CheckDonorInvoicesStatus.php`; do not schedule it or retain its writable `payment_status` behavior.
- Keep association-invoice files and routes unchanged.

### 7. Tests

Cover:

- Donor/event uniqueness and relationships.
- Event-scoped line collection including confirmed and unconfirmed donations.
- Integer-cent minimum, maximum, and total calculations.
- Creation warning and send block before event end.
- Snapshot consistency between Webling payload and PDF.
- Marker recovery after ambiguous POST outcome.
- Multiple marker match failure.
- Raw and derived status behavior.
- Live reminder and deletion checks that fail closed.
- Paid and partially paid deletion rejection.
- Confirmed `404` handling.
- Individual and bulk event isolation.
- Resend confirmations and latest queue timestamps.
- Individual download, missing cached PDF, and ZIP contents.
- Per-item bulk eligibility, live reminder checks, failures, and result counts.
- Event-scoped payment-summary counts.
- Refresh changes for only the selected event and error preservation.
- Derived Webling link rendering.
- Snapshot immutability, same-row recreation, and concurrent creation locking.
- Current-email mail dispatch and dispatch failure behavior.
- Unknown-state action restrictions.
- Association invoice isolation.
- One complete create, PDF, send, refresh, and delete path.

Run targeted Pest tests, Pint, PHPStan, and full CI including MariaDB migration tests.

## Rollout

Deliver the complete reactivation in one pull request. Commits are implementation checkpoints, not independently deployed releases. Every commit must leave the repository buildable, formatted, and test-green; tests for new behavior land with the code that implements that behavior.

Invoice functionality remains unreachable from the admin UI until the final UI commit. This keeps intermediate low-level work safe without adding a temporary feature flag.

### Commit 1: Document the Intended Functionality

- Add the functionality comparison and this implementation plan.
- Establish the pull request's source of truth before code changes.
- Run documentation formatting checks.

### Commit 2: Add Event-Scoped Invoice Persistence

- Add `donor_event_invoices` migration, model, factory, relationships, casts, and donor/event uniqueness.
- Add money, snapshot, PDF, Webling state, mail timestamp, and remote-deletion fields.
- Keep the schema additive; do not expose new UI or change existing invoice behavior.
- Test migration portability, relationships, casts, and uniqueness.

### Commit 3: Prepare Webling and Document Foundations

- Add integer-cent conversion at the Webling boundary.
- Add stable Debitor comment-marker write and exact lookup support.
- Add status-aware handling for confirmed `404`, authentication, rate limits, and transient failures.
- Make letter and PDF generation accept immutable snapshot input.
- Preserve existing association-invoice behavior.
- Test Webling payloads, marker lookup, error classification, snapshot rendering, and association isolation.

### Commit 4: Implement Event-Scoped Collection and Creation

- Reactivate donation collection for one external user and donation event, including confirmed and unconfirmed donations.
- Add snapshot creation, per-invoice locking, marker recovery, Debitor creation, and PDF persistence.
- Replace dormant split creation jobs with one sequential creation workflow.
- Keep workflow callable from tests and actions but not yet reachable through admin UI.
- Test cent calculations, event isolation, snapshot immutability, duplicate recovery, concurrent creation, PDF caching, and incomplete creation recovery.

### Commit 5: Implement Invoice Lifecycle Actions

- Add send, resend, reminder, reminder resend, download, deletion, recreation, and manual status refresh actions.
- Apply event-end, live Webling, unknown-state, current-email, cached-PDF, and dispatch-success rules.
- Add individual and bulk action orchestration with per-item eligibility, failure isolation, and result counts.
- Retire the dormant automatic status job and writable `payment_status` behavior without adding scheduler wiring.
- Test every lifecycle action, queue timestamps, failure behavior, `404` handling, same-row recreation, ZIP contents, bulk outcomes, and unknown states.

### Commit 6: Restore Admin Invoice UI

- Connect individual and bulk actions to the existing event-filtered donor table.
- Add confirmations, eligibility feedback, error reporting, Webling links, PDF downloads, and manual refresh.
- Mount the event-scoped payment summary and status displays.
- Keep association invoice UI unchanged.
- Add component and feature tests for complete admin workflows, event isolation, action visibility, summary counts, and user-facing failure states.

### Commit 7: Complete End-to-End Verification

- Add or finish the complete create, PDF, send, refresh, reminder, delete, recreate, and bulk workflow tests.
- Reactivate relevant skipped donor-invoice tests against the new model and update obsolete expectations; do not retain contradictory legacy behavior.
- Run Prettier, Pint, targeted Pest tests, PHPStan, full test suite, asset build, and MariaDB migration coverage.
- Make no functionality changes unless verification exposes a defect.

### Merge and Automatic Deployment

- Merge only when all pull-request lint, test, analysis, and build checks pass.
- Merge the complete commit series together; do not merge or cherry-pick a partial sequence to `main`.
- A push to `main` triggers `.github/workflows/deploy.yml`, which runs lint and tests before deployment.
- Deployment enters maintenance mode, updates the complete application, installs production dependencies, runs migrations with `--force`, clears and rebuilds caches, then brings the application online.
- Because schema changes are additive and UI is introduced last, the final merged state activates complete functionality after migration without a separate enablement step.

After deployment, verify one selected-event invoice through creation, Webling marker and totals, PDF download, send, manual refresh, reminder eligibility, unsettled deletion, and recreation before running bulk actions in production.

No scheduler, cursor bootstrap, remote legacy import, or old-invoice matching is required.
