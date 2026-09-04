---
name: pr-description
description: "Use this skill when making or editing pull request descriptions. Use for: branch diff review, clear PR summary, multi-purpose PR warning, reviewer setup commands, and PR creation. Not for commit messages, changelogs, or release notes."
---

# PR Description Skill

## When use this skill

Use when user asks:

- make PR
- write PR text
- improve PR text

## First look (always)

Run these first:

```bash
git status --short --branch
git log --oneline origin/main..HEAD
git diff --stat origin/main...HEAD
git diff origin/main...HEAD
git rev-parse --abbrev-ref HEAD
gh repo view --json nameWithOwner -q .nameWithOwner
```

If base branch not `main`, swap base:

- use `origin/<base>..HEAD` for commit list
- use `origin/<base>...HEAD` for diff

## Core rules

1. Short beat long.
2. No code dump in PR text.
3. Start with impact (why this matters).
4. Reviewer setup must be diff-based, not habit-based.
5. If branch has mixed unrelated work, warn user first.
6. Never mention local test, lint, build, or precommit results in PR text. CI owns validation; local precommit is a commit-readiness check, not reviewer information.

## Body shape

Use this shape. Drop empty sections.

````markdown
<1-3 lines: what got better and why>

## Details

- [ImportantFile.php](https://github.com/<owner>/<repo>/blob/<branch>/path/to/ImportantFile.php) - why reviewer should look

## Notes

<only real must-know: deploy steps, risk, migration/backfill instructions, or env/config change. Never add local validation results.>

## Reviewer Setup

In this PR vs `origin/main`, changes were made in <correct areas from diff>. You likely need to run:

```sh
composer install
npm install
npm run build
php artisan migrate
php artisan boost:update
php artisan optimize:clear
```

Fixes #<number>

---
**«<inspiring quote here>»**
_<author>_
````

## Reviewer Setup rules (strict)

### A) Must include

- `php artisan boost:update` (always)
- `php artisan optimize:clear` (always, and always last)

### B) Include when diff says yes

- if `composer.json` or `composer.lock` changed -> `composer install`
- if `package.json` or JS lockfile changed (`package-lock.json`, `pnpm-lock.yaml`, `yarn.lock`, `bun.lockb`) -> `npm install`
- if frontend inputs changed (Blade, JS, CSS, Tailwind, Vite inputs) -> `npm run build`
- if migrations changed -> `php artisan migrate`

### C) Command order

Use this order only when command exists:

1. dependency install (`composer install`, `npm install`)
2. build/migrate (`npm run build`, `php artisan migrate`)
3. `php artisan boost:update`
4. `php artisan optimize:clear` (last)

### D) Wording

Keep this exact style:

- heading: `## Reviewer Setup`
- intro line: `In this PR vs \`origin/main\`, changes were made in ... You likely need to run:`
- fenced `sh` block with only needed commands

## Details section rules

- Optional section.
- Only list files that need reviewer focus.
- Use filename-only link text.
- Link to blob on current branch.

## Notes section rules

Only add when truly needed:

- deploy step
- backfill/data migration step
- breaking/risky behavior
- env/config requirement
- unusual test coverage note

## Multi-purpose PR check

If commits look unrelated, warn user first:

> Branch look mixed:
>
> - commits A/B: one topic
> - commits C/D: another topic
>
> Split PR, or keep combined?

If user keeps combined, separate concerns clearly in summary.

## Make PR command

Default: draft PR unless user says ready.

```bash
gh pr create --draft --title "short title" --body "$(cat <<'EOF'
<impact summary>

## Reviewer Setup

In this PR vs `origin/main`, changes were made in <areas>. You likely need to run:

~~~sh
composer install
php artisan boost:update
php artisan optimize:clear
~~~

---
**«<quote>»**
_<author>_
EOF
)"
```

## Quote rule

End PR body with quote block. Fetch quote with:

```bash
php artisan inspire --no-interaction
```

## Common mistakes

- writing commit-by-commit changelog
- opening with `## Summary`
- putting code snippets in PR body
- guessing issue numbers
- mentioning local test, lint, build, or precommit results
- adding setup commands not implied by diff
- using `composer update` in reviewer setup
- putting `php artisan optimize:clear` anywhere but last
