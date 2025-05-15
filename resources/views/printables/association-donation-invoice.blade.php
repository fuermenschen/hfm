@extends('printables.base')

@props(['first_name', 'last_name', 'address', 'zip_code', 'city', 'company_name' => '', 'amount' => null])

@section('body')
    <style>
        {{-- logo and sender --}}
        .logo-and-sender {
            position: absolute;
            top: 2cm;
            left: 2cm;
        }

        .logo-and-sender img {
            width: 100px;
            margin-left: -1px;
        }

        .logo-and-sender div {
            margin-top: 0.5rem;
            font-weight: lighter;
            font-size: 10px;
            line-height: 12px;
        }

        {{-- recipient --}}
        .recipient {
            position: absolute;
            top: 5.3cm;
            left: 11.5cm;
            width: 5cm;
            height: 3cm;
            font-size: 12px;
        }

        .recipient p.sender {
            position: absolute;
            top: -0.3cm;
            font-size: 9px;
            text-decoration: underline;
            font-style: italic;
        }

        {{-- city and date --}}
        .city-and-date {
            position: absolute;
            top: 8cm;
            left: 2cm;
            font-size: 12px;
        }

        {{-- subject --}}
        .subject {
            position: absolute;
            top: 8.5cm;
            left: 2cm;
            font-size: 18px;
            font-weight: bolder;
        }

        {{-- body --}}
        .body {
            position: absolute;
            top: 10cm;
            left: 2cm;
            right: 2cm;
            font-size: 12px;
            /*page-break-after: always;*/
        }

        .body p {
            margin-bottom: 12px;
        }

        .body p:first-child {
            margin-bottom: 20px;
        }

        .body p:last-child {
            margin-top: 20px;
        }

        {{-- table styles --}}
        table {
            font-size: 10px;
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        table th, table td {
            padding: 8px;
            text-align: left;
            border: none;
        }

        table th {
            font-weight: bold;
            border-bottom: 1px solid gray;
            border-top: 1px solid black;
        }

        table tfoot td {
            font-weight: bold;
            border-top: 1px solid gray;
            border-bottom: 1px solid black;
        }

        table td {
            text-align: left;
        }

        table td:first-child {
            text-align: left;
        }

        {{-- qr-bill --}}
        .qr-bill {
            position: absolute;
            top: 18.5cm;
            left: 0cm;
            font-size: 10px;
        }

        #qr-bill-currency {
            float: none !important;
            display: inline-block;
        }

        #qr-bill-amount {
            display: inline-block;
        }

    </style>

    <!-- Logo and Sender -->
    <div class="logo-and-sender">
        <div>
            <p>
                Verein für Menschen<br>
                c/o Felix Moser<br>
                Mühleweg 8<br>
                8413 Neftenbach
            </p>
            <p>info@fuer-menschen.ch</p>
        </div>
    </div>

    <!-- Recipient -->
    <div class="recipient">
        <p class="sender">Verein für Menschen, fuer-menschen.ch</p>
        <p>
            @if( $company_name )
                {{ $company_name }}<br>
            @endif
            {{ $first_name }} {{ $last_name }}<br>
            {{ $address }}<br>
            {{ $zip_code }} {{ $city }}
        </p>
    </div>

    <!-- City and Date -->
    <div class="city-and-date">
        <p>Winterthur, {{ date('d.m.Y') }}</p>
    </div>

    <!-- Subject -->
    <div class="subject">
        <p>Spendenrechnung Verein für Menschen</p>
    </div>

    <!-- Body -->
    <div class="body">
        <p>
            Liebe:r {{ $first_name }}
        </p>
        <p>
            Danke, dass du den Verein für Menschen finanziell unterstützen möchtest. Dank Spenden wie deiner
            können wir Spendenanlässe wie <i>Höhenmeter für Menschen</i> durchführen. Herzlichen Dank.
        </p>
        <p>
            Herzliche Grüsse<br>
            Das Team vom Verein für Menschen
        </p>

    </div>

    @php
        // QR Bill generation

        use Sprain\SwissQrBill as QrBill;

        $qrBill = QrBill\QrBill::create();

        $qrBill->setCreditor(
            QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
                'Verein für Menschen',
                'Nelkenstrasse',
                '6',
                '8400',
                'Winterthur',
                'CH'
            )
        );

        $qrBill->setCreditorInformation(
            QrBill\DataGroup\Element\CreditorInformation::create(
                'CH9500700114903053924' // This is a classic iban. QR-IBANs will not be valid in this minmal setup.
            )
        );

        // The currency must be defined.
        $qrBill->setPaymentAmountInformation(
            QrBill\DataGroup\Element\PaymentAmountInformation::create(
                'CHF',
                $amount
            )
        );

        $qrBill->setUltimateDebtor(
            QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
                $company_name ? $company_name : $first_name . ' ' . $last_name,
                $address,
                null,
                $zip_code,
                $city,
                'CH'
            )
        );

        // Explicitly define that no reference number will be used by setting TYPE_NON.
        $qrBill->setPaymentReference(
            QrBill\DataGroup\Element\PaymentReference::create(
                QrBill\DataGroup\Element\PaymentReference::TYPE_NON
            )
        );

        $qrBill->setAdditionalInformation(
            QrBill\DataGroup\Element\AdditionalInformation::create(
                'Spendenzahlung, Verein für Menschen'
            )
        );

        $output = new QrBill\PaymentPart\Output\HtmlOutput\HtmlOutput($qrBill, 'de');

        $html = $output->setPrintable(false)->getPaymentPart();

    @endphp
    <div class="qr-bill">
        {!! $html !!}
    </div>

@endsection
