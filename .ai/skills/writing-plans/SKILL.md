---
name: writing-plans
description: "Write and improve project plans for coding agents. Use for roadmap, migration, refactor, or execution plans. Plans capture decided direction: why, what, order, progress, and constraints."
---

# Writing Plans

## Mindset

Plan is map, not discussion.

It tells future agents:

- why this matters
- what target looks like
- which order to move in
- what is done vs open
- which decisions are settled

If decision is missing, stop and ask user. Do not write options, maybe-paths, open questions, or “decide later” into plan. Chat can explore options; plan records chosen path.

## Level

Big plan stays durable: target, reasons, phases, constraints. No PR chatter, temporary migration states, stale slice links, or implementation noise.

Slice plan can be tactical: current sequence, temporary states, cutover, rollback, verification. It exists to land current work safely and may disappear after completion.

## Truth

Target state means final state. Do not mix in transition mechanics like “nullable first, required later”. Put those in slice execution only.

Checkboxes are progress truth:

- `[x]` done
- `[ ]` planned
- first relevant unchecked item is next

Avoid prose artifacts (`NEXT UP`, vague “future”) when checkboxes already say status.

## Decisions

Plans should narrow choices, not multiply them.

Use project rules and domain skills to shape decisions before writing. For Laravel work, Laravel best practices matter: model relationships, constraints, authorization, validation, deletion behavior, queues, tests, and Laravel conventions should be reflected in plan direction.

## Editing

Read existing plan first. Keep good parts. Change only what violates mindset. Smaller diff is better.

Before finishing, remove ambiguity, stale references, option lists, target/transition mixing, and checklist/prose mismatch.
