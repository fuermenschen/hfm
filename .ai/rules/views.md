---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## No Test-Only DOM Hooks
Do not add data-test, data-testid, or other test-only attributes to rendered DOM. Browser tests must use visible user-facing text, labels, roles, or existing semantic attributes.
