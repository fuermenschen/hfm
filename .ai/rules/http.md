---
paths:
  - 'app/Http/**'
---

# Http

## Preserve single authenticated guard
Never leave web and external guards authenticated together. Cross-guard login must invalidate opposite guard.
