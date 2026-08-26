---
paths:
  - '**/*'
---

# Project Rules

## Portal donation totals switch at event start
Use DonationEvent::hasStarted() for estimated-versus-actual donation displays. Keep hasEnded() for archive and group-mutation rules; admin dashboard and portal all-events view retain both totals.
