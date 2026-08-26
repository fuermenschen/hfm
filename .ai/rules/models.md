---
paths:
  - 'app/Models/**'
  - app/Models/DonationEvent.php
---

# Models

## Keep model helpers database-free
Model helpers may only use in-memory attributes and already-loaded relation data. Do not lazy-load relations, call relationship queries, or issue database queries from helpers; put cross-model work in services/actions. Relation definitions remain normal Eloquent methods.

## Portal donation totals switch at event start
Use DonationEvent::hasStarted() for estimated-versus-actual donation displays. Keep hasEnded() for archive and group-mutation rules; admin dashboard and portal all-events view retain both totals.
