---
paths:
  - 'app/**/*.php'
---

# App

## Respect DonationEvent timezone
For DonationEvent timeline logic, use event-local time (Date::now($event->timezone) or now($event->timezone)). Event timestamp attributes use LocalizedDateTime; do not compare against server-default time.

## Reuse equal-split translation labels
UI labels for equal-split partners use __('app.equal_split') or __('app.equal_split_full'). Raw alle zu gleichen Teilen only identifies legacy persisted partner data.
