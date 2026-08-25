---
paths:
  - 'resources/views/vendor/pulse/**'
---

# Pulse

## Keep Pulse dashboard standalone
Pulse dashboard must render through package-native `<x-pulse>` as standalone document. Do not wrap it in HFM layouts or inject `Pulse::css()` into shared stacks; Pulse CSS is global and `<x-pulse>` owns CSS/JS assets.
