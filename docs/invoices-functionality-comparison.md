# Invoice Functionality: Legacy vs Reactivated

Legacy reference: commit [`aec2a4652a2a6fcfffd9374b99b074cf05389e9e`](https://github.com/fuermenschen/hfm/tree/aec2a4652a2a6fcfffd9374b99b074cf05389e9e)

Implementation reference: [`invoices-implementation-plan.md`](invoices-implementation-plan.md)

## Restored Features

| Feature                        | Legacy | Reactivated | Improvement                          |
| ------------------------------ | :----: | :---------: | ------------------------------------ |
| Create invoices                |   ✓    |      ✓      | Scoped per external user and event   |
| Send invoices                  |   ✓    |      ✓      | Blocked until event end              |
| Resend invoices                |   ✓    |      ✓      | Confirmation retained                |
| Send reminders                 |   ✓    |      ✓      | Live Webling safety check            |
| Resend reminders               |   ✓    |      ✓      | Confirmation retained                |
| Download PDFs                  |   ✓    |      ✓      | Scoped per event invoice             |
| Bulk create                    |   ✓    |      ✓      | Scoped to selected event             |
| Bulk send                      |   ✓    |      ✓      | Scoped to selected event             |
| Bulk reminders                 |   ✓    |      ✓      | Live-checks each selected invoice    |
| Bulk PDF download              |   ✓    |      ✓      | Event-scoped ZIP                     |
| Delete invoices                |   ✓    |      ✓      | Settled invoices cannot be deleted   |
| Payment summary                |   ✓    |      ✓      | Scoped to selected event             |
| Manual status refresh          |   ✓    |      ✓      | Refreshes selected event only        |
| Include unconfirmed donations  |   ✓    |      ✓      | Billing all donations is intentional |
| Webling invoice link           |   ✓    |      ✓      | Derived from Debitor ID              |
| Local PDF cache                |   ✓    |      ✓      | One immutable PDF per event invoice  |
| Queued mail timestamps         |   ✓    |      ✓      | Stored per event invoice             |
| Association invoice separation |   ✓    |      ✓      | Unchanged                            |

## New Safeguards

| Feature                           | Legacy | Reactivated | Benefit                                                         |
| --------------------------------- | :----: | :---------: | --------------------------------------------------------------- |
| Event-scoped invoices             |   ✗    |      ✓      | Prevents donations from different events being combined         |
| Event required for actions        |   ✗    |      ✓      | Prevents cross-event admin actions                              |
| Creation warning before event end |   ✗    |      ✓      | Warns that totals may still change                              |
| Sending blocked before event end  |   ✗    |      ✓      | Prevents provisional invoices being mailed                      |
| Integer-cent calculations         |   ✗    |      ✓      | Avoids floating-point money errors                              |
| Immutable creation snapshot       |   ✗    |      ✓      | Webling invoice and PDF always use identical data               |
| Retry-safe creation marker        |   ✗    |      ✓      | Prevents common duplicate Webling invoices                      |
| Per-invoice creation lock         |   ✗    |      ✓      | Prevents concurrent duplicate creation                          |
| Raw Webling state cache           |   ✗    |      ✓      | Preserves remote payment state without a second writable status |
| Derived overdue status            |   ✗    |      ✓      | Supports accurate reminders                                     |
| Remote deletion detection         |   ✗    |      ✓      | Confirmed `404` disables unsafe actions                         |
| Live reminder check               |   ✗    |      ✓      | Prevents reminders for settled invoices                         |
| Live deletion check               |   ✗    |      ✓      | Prevents deletion of settled invoices                           |
| Unknown-state protection          |   ✗    |      ✓      | Risky actions fail closed                                       |

## Deliberately Not Included

| Feature                                    | Reason                                                                 |
| ------------------------------------------ | ---------------------------------------------------------------------- |
| Skipping unconfirmed donations             | Billing all donations is intentional                                   |
| Automatic status synchronization           | Manual event refresh is sufficient at current volume                   |
| Incremental replication cursor             | Adds recovery and persistence complexity without current need          |
| Daily Webling audit                        | Duplicates manual refresh without observed value                       |
| Deleted-invoice history                    | Webling remains accounting authority; one reusable local row is enough |
| Local-versus-Webling mismatch warning      | Unsettled invoices can be deleted and recreated                        |
| Full remote accounting-data cache          | Webling link covers uncommon detail needs                              |
| Scheduler and permanent worker requirement | Core invoice workflow should not require new deployment machinery      |
| Legacy invoice import or matching          | Old global invoices cannot be mapped safely to event scope             |

## Main Change

Legacy system had one global invoice per donor.

Reactivated system has one invoice lifecycle per external user and donation event, with manual Webling refresh and live checks around risky actions.
