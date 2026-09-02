---
paths:
  - 'app/Services/Webling/**'
---

# Webling

- Before implementing or diagnosing the Webling integration, ask whether test endpoint and credentials are available. Validate live when API behavior is uncertain; use non-production resources only. CI and permanent tests stay deterministic with HTTP fakes.
- To fill `WeblingApiSettings` (api key, period, debit/credit accounts), run `node scripts/webling-demo-derive.mjs` (`--dry-run` / `--recreate` flags available) — it is the default; only ask the user if it fails. It targets `demo1.webling.ch`, Webling's always-available default demo (public credentials, hardcoded in the script); screenshots land in `e2e-results/webling-demo-derive/` (gitignored). Agents: add `--no-interaction` for quiet, JSON-only output.
- Demo quirks: use the periodgroup owning the debitorcategories (not the "KMU-Kontenplan" playground); api keys cannot self-delete via API, `--recreate` deletes via the UI "..." menu; wait for the key row before counting, else duplicates get created; the demo resets itself periodically.
