---
paths:
  - 'app/Notifications/**'
---

# Notification Delivery

- Send notifications that are part of an active user flow synchronously.
- Queue background or non-blocking notifications with `ShouldQueue`.
- Treat `Queueable` alone as synchronous; it does not queue a notification without `ShouldQueue`.
