---
paths:
  - 'app/Models/**'
---

# Models

## Keep model helpers database-free
Model helpers may only use in-memory attributes and already-loaded relation data. Do not lazy-load relations, call relationship queries, or issue database queries from helpers; put cross-model work in services/actions. Relation definitions remain normal Eloquent methods.

## Declare mass assignment with Fillable attributes
Use #[Fillable([...])] on Eloquent models. Keep only deliberate create and fill fields in list.

## Type Eloquent relationships
Declare relationships as named methods with concrete Eloquent relation return types. Add generic PHPDoc where relation typing needs model detail.
