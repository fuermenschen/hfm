---
paths:
  - 'app/Models/**'
---

# Models

## Keep model helpers database-free
Model helpers may only use in-memory attributes and already-loaded relation data. Do not lazy-load relations, call relationship queries, or issue database queries from helpers; put cross-model work in services/actions. Relation definitions remain normal Eloquent methods.
