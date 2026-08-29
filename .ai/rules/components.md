---
paths:
  - 'app/Components/**'
---

# Components

## Use validation attributes in Livewire components
Choose exactly one validation source for each field.

1. Attribute-first is mandatory when every rule is static and expressible in `#[Validate]`. Declare one attribute per rule with German user-facing messages. Do not add that field to `rules()`.
2. Use `rules()` only when at least one rule needs runtime state or a `Rule::` object, such as `unique()->ignore()` or a current-record allowlist. Add an empty `#[Validate]` with a short English comment solely to enable update-time validation. Put the field's complete rule set, including `required`, `string`, and `max`, in `rules()` and all German messages in `messages()`.

Never split a field's rules across attributes and `rules()`. Livewire does not merge colliding field keys. Call `$this->validate()` once.
Keep realtime validation enabled; only set `onUpdate: false` with an explicit interaction reason.

## Use Livewire reset APIs
Use reset() and resetValidation() for component state and validation cleanup. Do not create manual reset helpers that only duplicate default property values.

## Livewire components stay in Components
Put stateful Livewire classes in App\Components, not App\Livewire. Render matching Blade under resources/views/components/** or resources/views/forms/**. Components handle UI state and validation; delegate domain writes to Actions.

## Admin tables use shared datatable base
New admin data tables extend AbstractDatatableComponent and use InteractsWithDatatable. Define query, search allowlist, sort map, columns, visible defaults, and export rows. Do not rebuild pagination, selection, exports, or modal mechanics.

## Validate component mutations locally
Validate before state-changing component actions. Keep rules near component; use loaded or current-event IDs for dynamic allowlists.

## Keep admin table forms out of datatables
Admin datatable components own listing concerns: query, filters, sorting, selection, exports, and simple row actions. Resource editor components own create/edit form state, validation, authorization, persistence, and modal/route presentation. Use explicit resource editors; do not add generic model-driven CRUD editors. Use modals for small forms and routes for complex editors.

## Admin editors confirm changed fields before saving
Admin resource editors must prevent accidental writes: use the ConfirmsAdminEdits trait (app/Components/Concerns/ConfirmsAdminEdits.php). It snapshots form data on open(), blocks no-change saves, shows a confirmation modal listing German field labels of changed fields, and writes one audit Log::info entry with editor, record id, and changed field keys only — never values. Editors implement formData() (normalized), fieldLabels(), persist(), logContext(), call captureEditorSnapshot() in open(), and add a warning callout + confirm modal to the Blade view. In tests assert the log entry docs-style: Log::spy() + shouldHaveReceived('info')->with('Admin editor save confirmed.', [exact context])->once(); keep factory fixtures explicit so the changed-fields list is deterministic.

## Keep Results totals cache-free
The /resultate page recomputes its totals on every render (wire:poll.30s on the TV screen). Do NOT reintroduce a cache keyed on max(updated_at): timestamps have second precision, so updates within the same second (rapid round taps) leave the version unchanged and silently freeze the live tally. Data is small; per-render compute is the intended design.
