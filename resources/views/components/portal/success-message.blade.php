@if (session('success'))
    <flux:callout icon="check-circle" variant="success">
        <flux:callout.heading>Bestätigung gespeichert</flux:callout.heading>
        <flux:callout.text>{{ session('success') }}</flux:callout.text>
    </flux:callout>
@endif
