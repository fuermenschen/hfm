---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## No Test-Only DOM Hooks
Do not add data-test, data-testid, or other test-only attributes to rendered DOM. Browser tests must use visible user-facing text, labels, roles, or existing semantic attributes.

## No explicit flux:error next to label-prop controls
Flux label/description shorthand automatically wraps controls in a Field with an error component (fluxui.dev/components/field#shorthand-props). Adding <flux:error> beside a control that uses the label prop renders every validation message twice. Only use explicit <flux:error> in long-form fields (flux:field + flux:label with no label prop on the control).
