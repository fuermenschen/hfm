---
paths:
  - 'routes/**'
---

# Routes

## Routes stay audience-partitioned
Public routes belong in web.php; external-user portal routes in portal.php behind auth:external; admin routes in admin.php behind auth:web. Keep route names audience-prefixed.

## Protect UUID login links with signatures
UUID authentication and confirmation URLs require signed middleware plus whereUuid constraints. Default timeout is 15 minutes. Preserve expired-link handling in bootstrap/app.php.
