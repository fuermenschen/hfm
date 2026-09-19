@extends('layouts.public')

@section('content')
    @component('components.page-title')
        Impressum
    @endcomponent

    @php
        $invoiceSettings = app(\App\Settings\InvoiceSettings::class);

        $addressLines = array_values(array_filter([
            trim((string) $invoiceSettings->creditor_name),
            (string) $invoiceSettings->creditor_care_of !== '' ? 'c/o '.trim((string) $invoiceSettings->creditor_care_of) : null,
            trim((string) $invoiceSettings->creditor_street.' '.(string) $invoiceSettings->creditor_building_number),
            trim((string) $invoiceSettings->creditor_postal_code.' '.(string) $invoiceSettings->creditor_city),
        ], fn ($line): bool => $line !== null && $line !== ''));

        if ($addressLines === []) {
            $addressLines = ['Verein für Menschen', 'c/o Kai Frehner', 'Rössligasse 6', '8405 Winterthur'];
        }
    @endphp

    <p>Diese Website ist ein Projekt von:</p>
    @foreach ($addressLines as $line)
        <p>{{ $line }}</p>
    @endforeach
@endsection
