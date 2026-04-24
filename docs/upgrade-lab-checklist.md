# Upgrade Lab Manual Checklist

Use this checklist format when running `scripts/upgrade-lab` in non-interactive mode.

Pass a file path with:

- `--baseline-checklist-file=<path>`
- `--target-checklist-file=<path>`

Each file must contain these keys with `pass` or `fail` values:

```text
home=pass
faq=pass
registration_guards=pass
admin=pass
logs=pass
mailpit_email=pass
notes=
```

Key meanings:

- `home`: home page content check (`/`)
- `faq`: FAQ page content check (`/fragen-und-antworten`)
- `registration_guards`: registration-related access/guard behavior
- `admin`: admin access check (`/admin`)
- `logs`: no critical runtime errors found in logs
- `mailpit_email`: login-link (or equivalent) email received in Mailpit

Gate rule:

- All required checks must be `pass` for both baseline and target phases.
- Any `fail` value causes a gate failure (exit code non-zero).
