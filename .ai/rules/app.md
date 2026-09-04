---
paths:
    - "app/**/*.php"
---

# App

## Respect DonationEvent timezone

For DonationEvent timeline logic, use event-local time (Date::now($event->timezone) or now($event->timezone)). Event timestamp attributes use LocalizedDateTime; do not compare against server-default time.

## Reuse equal-split translation labels

UI labels for equal-split partners use __('app.equal_split') or __('app.equal_split_full'). Raw alle zu gleichen Teilen only identifies legacy persisted partner data.

## Joining external_users? Add whereHas to exclude soft deletes

external_user_id is non-nullable, but ExternalUser uses SoftDeletes. Raw join('external_users', ...) bypasses the global scope and includes soft-deleted users; whereHas('externalUser') excludes them. Any admin query or export that joins external_users for names must add ->whereHas('externalUser') so rows of deleted users stay invisible everywhere (tables, exports, counts, start-number assignment).
