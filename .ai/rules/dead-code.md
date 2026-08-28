---
paths:
  - 'app/Support/DeadCode/**'
---

# Dead Code

## Livewire validation callbacks
Livewire calls protected rules(), messages(), and validationAttributes() dynamically. Keep those callbacks recognized by LivewireComponentUsageProvider rather than suppressing shipmonk.deadMethod findings inline.
