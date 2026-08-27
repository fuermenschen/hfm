---
paths:
  - '**/*'
---

# Project Rules

## Language boundaries
Use English for developer-facing content: code comments, exceptions, logs, documentation, query names, and cache keys. Use German for all application-visible copy, including admin and portal UI, validation, and error messages.

## Portal donation totals switch at event start
Use DonationEvent::hasStarted() for estimated-versus-actual donation displays. Keep hasEnded() for archive and group-mutation rules; admin dashboard and portal all-events view retain both totals.
