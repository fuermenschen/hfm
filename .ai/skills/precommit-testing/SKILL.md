---
name: precommit-testing
description: Run and interpret the AI-first precommit quality gate for this project, including when to run each sub-command and when to avoid rerunning the full suite.
---

# Precommit Testing

## When to use this skill

Use this skill when validating code quality before commit, especially when deciding between targeted checks and the full
project gate.

## Goal

This project has a precommit pipeline that validates PHP formatting, static analysis, tests, and e2e behavior.

Because it is intentionally broad and slow, do not run the full suite after every small edit. Run the narrowest useful
command during development, then run `composer precommit:ai` once near the end.

## Full suites

- `composer precommit:ai`:
    - Canonical full gate for this project.
    - Runs `lint:ai`, frontend build, `test:sca:ai`, `test:pest:ai`, and `test:e2e:ai`.
    - Default full run for agent-driven workflows.

## Individual commands and what they validate

- `composer lint:ai`
    - Runs Laravel Pint with reduced-noise output.
    - Ensures consistent PHP code formatting.

- `composer test:sca:ai`
    - Runs PHPStan static analysis via Larastan.
    - Catches typing and API misuse issues quickly without executing runtime tests.

- `composer test:pest:ai`
    - Runs the Pest test suite in parallel with compact output.
    - Validates test suite execution.

- `composer test:e2e:ai`
    - Builds frontend, runs Playwright e2e suite with JUnit reporter.
    - Validates browser-level behavior and integration flows.

## Recommended execution strategy (fast feedback first)

1. During active coding, run only checks relevant to your change area (for example `composer test:sca:ai` or
   `composer lint:ai`).
2. After feature completion, run `composer precommit:ai` once to validate the entire gate with compact output.
3. If `precommit:ai` fails, fix and rerun only the failed section until green.
4. Avoid rerunning long steps (`test:e2e:ai`) unless related code changed.

## Practical guidance for agents

- Prefer `:ai` variants by default for routine autonomous validation.
- Avoid repeatedly rerunning long steps (`test:e2e:ai`) unless touched code justifies it.
- If a broad run fails early in one section, avoid rerunning the whole suite immediately; rerun the failing command
  first.
- Treat `precommit:ai` as a release-quality gate, not an edit-loop command.

If a human specifically needs verbose output, the `:ai` suffix can be omitted.
