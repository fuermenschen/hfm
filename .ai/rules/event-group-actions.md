---
paths:
    - "app/Actions/*EventGroup*.php"
---

# Event-Group Actions

## Lock membership mutations

Perform event-group membership mutations in DB::transaction(). Use EventGroupMembershipService lock and authorization methods plus conditional updates so concurrent requests cannot overwrite membership state.
