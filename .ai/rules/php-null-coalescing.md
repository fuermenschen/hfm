---
paths:
    - "**/*.php"
---

# PHP Null Coalescing

- Prefer `??` for undefined or `null` fallbacks.
- PHP differs from many languages: `??` uses `isset()` semantics. Property and array chains are not fully evaluated first.
- Use `$registration->partner->name ?? 'Alle Partnerorganisationen'`; missing/null chain parts safely return fallback. No `instanceof`, ternary, or `?->` needed.
- Method calls are not protected. `$object->relationship()->name ?? 'Fallback'` still invokes `relationship()` and may throw.
- Keep conditionals when `false`, `0`, or `''` should fall back, or when non-null value needs transformation.

```php
$username = $_GET["user"] ?? "nobody";
$username = isset($_GET["user"]) ? $_GET["user"] : "nobody";
$username = $_GET["user"] ?? ($_POST["user"] ?? "nobody");
```
