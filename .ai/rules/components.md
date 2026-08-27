---
paths:
  - 'app/Components/**'
---

# Components

## Use validation attributes in Livewire components
Declare Livewire field validation with #[Validate] attributes and field-specific messages. For dynamic custom rules unsupported by attributes, validate only that rule directly in the action method.

## Livewire components stay in Components
Put stateful Livewire classes in App\Components, not App\Livewire. Render matching Blade under resources/views/components/** or resources/views/forms/**. Components handle UI state and validation; delegate domain writes to Actions.

## Admin tables use shared datatable base
New admin data tables extend AbstractDatatableComponent and use InteractsWithDatatable. Define query, search allowlist, sort map, columns, visible defaults, and export rows. Do not rebuild pagination, selection, exports, or modal mechanics.

## Validate component mutations locally
Validate before state-changing component actions. Keep rules near component; use loaded or current-event IDs for dynamic allowlists.
