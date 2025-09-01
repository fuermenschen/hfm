@extends('printables.base')

@props(['donator', 'donations'])

@php
    use Illuminate\Support\Facades\Vite;

    $letterhead = Vite::asset("resources/images/letterhead_hfm.svg");
    $letterheadData = base64_encode(file_get_contents($letterhead));

    $totalAmount = 0;

@endphp

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
            page-break-after: always;
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
        <img src="data:image/svg+xml;base64,{{ $letterheadData }}" alt="Logo" />

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
        <p class="sender">Höhenmeter für Menschen, fuer-menschen.ch</p>
        <p>
            {{ $donator->first_name }} {{ $donator->last_name }}<br>
            {{ $donator->address }}<br>
            {{ $donator->country_of_residence }}-{{ $donator->zip_code }} {{ $donator->city }}
        </p>
    </div>

    <!-- City and Date -->
    <div class="city-and-date">
        <p>Winterthur, {{ date('d.m.Y') }}</p>
    </div>

    <!-- Subject -->
    <div class="subject">
        <p>Spendenrechnung Höhenmeter für Menschen</p>
    </div>

    <!-- Body -->
    <div class="body">
        <p>
            Liebe:r {{ $donator->first_name }}
        </p>
        <p>
            Wir schätzen dein Engagement sehr und möchten dir herzlich danken.
        </p>
        <p>
            Untenstehend findest du eine Übersicht über deine Spenden an
            Höhenmeter für
            Menschen.
        </p>

        <!-- Donation list using table for better formatting -->
        <table>
            <thead>
            <tr>
                <th>Sportler:in</th>
                <th>Benefizpartner:in</th>
                <th>Runden</th>
                <th>Pro Runde</th>
                <th>Subtotal</th>
                <th>Min.</th>
                <th>Max.</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($donations as $donation)
                @php
                    $this_total = $donation->athlete->rounds_done * $donation->amount_per_round;
                    $this_subtotal = $this_total;

                    if ($donation->amount_min) {
                        if ($this_total < $donation->amount_min) {
                            $this_total = $donation->amount_min;
                        }
                    }
                    if ($donation->amount_max) {
                        if ($this_total > $donation->amount_max) {
                            $this_total = $donation->amount_max;
                        }
                    }

                    $totalAmount += $this_total;
                @endphp
                <tr>
                    <td>{{ $donation->athlete->privacy_name }}</td>
                    <td>{{ $donation->athlete->partner->name}}</td>
                    <td>{{ $donation->athlete->rounds_done }}</td>
                    <td>Fr. {{ number_format($donation->amount_per_round, 2) }}</td>
                    <td>Fr. {{ number_format($this_subtotal, 2) }}</td>
                    <td>
                        @if ($donation->amount_min)
                            Fr. {{ number_format($donation->amount_min, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($donation->amount_max)
                            Fr. {{ number_format($donation->amount_max, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td>Fr. {{ number_format($this_total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <td colspan="7" style="text-align: right;">Gesamtbetrag:</td>
                <td>Fr. {{ number_format($totalAmount, 2) }}</td>
            </tr>
            </tfoot>
        </table>

        <p>Bitte verwende zur Einzahlung den beiliegenden Einzahlungsschein. Die Zahlung des Betrags von mindestens
            Fr.&nbsp;{{ number_format($totalAmount, 2) }} ist fällig innerhalb von 20&nbsp;Tagen ab Erhalt der
            Rechnung.</p>

        <p>Nach Eingang aller Spenden werden wir eine gemeinsame Überweisung an die drei Benefizpartner:innen
            vornehmen. Wir werden dich informieren, wann wir welche Beträge überweisen durften.</p>

        <p>
            Herzliche Grüsse<br>
            Das Team von Höhenmeter für Menschen
        </p>

    </div>

    @php
        // QR Bill generation
        // FIXME: The QR Bill generation should be moved to a separate controller

        use Sprain\SwissQrBill as QrBill;

        $qrBill = QrBill\QrBill::create();

        $qrBill->setCreditor(
            QrBill\DataGroup\Element\CombinedAddress::create(
                'Verein für Menschen',
                '',
                '8400 Winterthur',
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
                'CHF'
            )
        );

        $qrBill->setUltimateDebtor(
            QrBill\DataGroup\Element\CombinedAddress::create(
                $donator->first_name . ' ' . $donator->last_name,
                $donator->address,
                $donator->zip_code . ' ' . $donator->city,
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
                'Spendenzahlung, Höhenmeter für Menschen
                (DON-' . sprintf('25%04d', $donator->id) . ', ' . date('Ymd') . ')'
            )
        );

        $output = new QrBill\PaymentPart\Output\HtmlOutput\HtmlOutput($qrBill, 'de');

        $html = $output->setPrintable(false)->getPaymentPart();

    @endphp
    <div class="qr-bill">
        {!! $html !!}
    </div>

@endsection
