# Donation Event Content Plan

## Scope

This document defines the event-scoped public-content strategy for the donation event rollout.

- Keep operational event data structured in dedicated columns (`starts_at`, `ends_at`, location fields, etc.).
- Store event-variable public content snippets in `donation_events.content` (JSON).
- Use markdown for rich text snippets and render safely.
- Keep fallback hardcoded copy in views/jobs so missing keys do not break rendering.

## Data Shape (`donation_events.content`)

Use a nested JSON object with these keys:

- `hero.copy_md`
- `home.about_heading`
- `home.about_intro_md`
- `home.about_body_md`
- `results.heading_md`
- `faq.general_event_md`
- `seo.meta_description_md`
- `seo.og_description_md`
- `invoice.additional_information`

## Rendering Rules

- Render markdown with Laravel `Str::markdown` / `Str::inlineMarkdown`.
- Always render with safe options:
    - `html_input: strip`
    - `allow_unsafe_links: false`
- For `<meta>` values, render markdown and then strip tags to plain text.

## Current Event Resolution

- Resolve current event by `eventSettings.current_event_id` (Spatie settings).
- The selected event must exist and have `is_published = true`.
- If the setting is missing, missing in DB, or points to an unpublished event, public event snippets stay empty.
- Login/token routes remain available regardless of event selection state.

## Rollout Rules

- No extra migration for `donation_events` while still uncommitted: update existing create migration directly.
- Seed both 2025 and 2026 with the current production copy values.
- Keep values identical for both seed rows for now.
- Seed both events as published by default (`is_published = true`) and let admins control the current event via settings.
- Move more copy into event content only when needed; avoid premature CMS complexity.
