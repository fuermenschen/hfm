---
paths:
    - "app/**/*.php"
    - "config/filesystems.php"
    - "tests/**/*.php"
---

# Storage

## Use local filesystem for current deployment

This app runs as a single-instance deployment on shared hosting. Keep application storage interactions on the configured `local` disk. Do not add provider abstractions, remote-storage fallbacks, or multi-instance coordination for speculative scale. Revisit this rule only when deployment model changes.
