---
paths:
    - "app/{Http,Components}/**"
---

# Http Components

## Keep admin and external auth guards separate

Use web for admins and external for participant and donor flows. Explicitly select guard in role-sensitive code; login into one guard logs out other.
