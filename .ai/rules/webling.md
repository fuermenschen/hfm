---
paths:
  - 'app/Services/Webling/**'
---

# Webling

## Use available Webling test credentials
Before implementing or diagnosing Webling integration, ask whether test endpoint and credentials are available. Use live validation when API behavior is uncertain. Use only non-production resources; keep credentials local, never commit or log them. CI and permanent tests remain deterministic with HTTP fakes.
