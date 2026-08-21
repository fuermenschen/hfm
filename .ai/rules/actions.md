---
paths:
  - 'app/Actions/**'
---

# Actions

## Actions own visible business operations
Actions perform one readable business operation. Keep orchestration and transition rules in `__invoke()`; use protected helpers only for action-local complexity. Do not use action inheritance or traits for reuse.
