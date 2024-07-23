@extends('printables.base')

@props(['athlete'])

@php
    use Illuminate\Support\Facades\Vite;
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    $athlete = \App\Models\Athlete::find(1);

    $qrCode = QrCode::format('svg')
        ->margin(0)
        ->errorCorrection('L')
        ->color(27, 46, 71)
        ->size(100)
        ->generate(route('show-athlete', $athlete->login_token));
    $qrCodeData = base64_encode($qrCode);

    $partnerName = "";

    if (str_contains($athlete->partner->name, "alle")) {
        $partnerName = "alle drei Benefizpartner:innen zu gleichen Teilen";
    } elseif (str_contains($athlete->partner->name, "Brühlgut")) {
        $partnerName = "die Brühlgut Stiftung";
    } elseif (str_contains($athlete->partner->name, "Kinderseele")) {
        $partnerName = "das Institut Kinderseele Schweiz";
    } elseif (str_contains($athlete->partner->name, "143")) {
        $partnerName = "die Dargebotene Hand (Tel 143)";
    } else {
        throw new Exception("Unknown partner name: " . $athlete->partner->name);
    }

    $logo = Vite::asset("resources/images/logo_light.svg");
    $logoData = base64_encode(file_get_contents($logo));


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
            margin-left: 2px;
        }

        .logo-and-sender h1 {
            font-size: 20px;
            line-height: 25px;
            margin-top: 1rem;
            font-weight: bolder;
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
            top: 6cm;
            left: 11cm;
            width: 5cm;
            height: 3cm;
            border: solid 1px #1B2E47;
            font-size: 12px;
        }

        {{-- city and date --}}
        .city-and-date {
            position: absolute;
            top: 10cm;
            left: 2cm;
            font-size: 12px;
        }

        {{-- subject --}}
        .subject {
            position: absolute;
            top: 10.5cm;
            left: 2cm;
            font-size: 18px;
            font-weight: bolder;
        }

        {{-- body --}}
        .body {
            position: absolute;
            top: 12cm;
            left: 2cm;
            right: 2cm;
            font-size: 12px;
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

        {{-- qr code --}}
        .qr-code {
            position: absolute;
            bottom: 2cm;
            left: 2cm;
        }

        .qr-code svg {
            width: 100px;
            height: 100px;
        }

        .qr-code p {
            font-size: 12px;
            margin-top: 0.6rem;
        }

    </style>

    <!-- Logo and Sender -->
    <div class="logo-and-sender">
        <img src="data:image/svg+xml;base64,{{ $logoData }}" alt="Logo" />

        <h1>Höhenmeter<br>für Menschen</h1>

        <div>
            <p>
                RoundTable 25 Winterthur<br>
                c/o Simon Moser<br>
                Kirchplatz 1<br>
                8400 Winterthur
            </p>
            <p>hfm@rt25.ch</p>
        </div>
    </div>

    <!-- Recipient -->
    <div class="recipient">
        <p>
            @if ($athlete->adult == 0)
                An die Eltern von
            @endif
            <br>
            {{ $athlete->first_name }} {{ $athlete->last_name }}<br>
            {{ $athlete->address }}<br>
            {{ $athlete->zip_code }} {{ $athlete->city }}
        </p>
    </div>

    <!-- City and Date -->
    <div class="city-and-date">
        <p>Winterthur, {{ date('d.m.Y') }}</p>
    </div>

    <!-- Subject -->
    <div class="subject">
        <p>Willkommen bei Höhenmeter für Menschen</p>
    </div>

    <!-- Body -->
    <div class="body">
        <p>
            Liebe:r {{ $athlete->first_name }}
        </p>
        <p>
            Vielen Dank, dass du beim Anlass Höhenmeter für Menschen mitmachst!
        </p>
        <p>
            Du hast bei deiner Anmeldung angegeben, dass du ungefähr
            <strong>{{ $athlete->rounds_estimated }} Runden</strong>
            zurücklegen möchtest ({{ $athlete->sportType->name }}). Die Spenden deiner Spender:innen gehen dann
            an <strong>{{ $partnerName }}</strong>.
        </p>
        <p>
            Wir möchten dir das Suchen von
            Spender:innen so einfach wie möglich machen.
            Deshalb erhältst du anbei einige personalisierte Flyer.
            Zudem findest du in deinem persönlichen Bereich auf der Webseite personalisierte Bilder, die du auf
            Social Media teilen kannst.
        </p>
        <p>
            Wenn du mehr Flyer benötigst oder sonst etwas von uns brauchst, melde ich jederzeit bei uns.
        </p>
        <p>
            Am Anlass selbst, am <strong>21. September 2024</strong> hast du dann von 13&nbsp;Uhr bis 18&nbsp;Uhr
            Zeit, um so viele Runden wie möglich
            zurückzulegen. Alles weitere, etwa das Eintreiben der Spenden, erledigen wir für dich.
        </p>
        <p>
            Kurz vor dem Anlass senden wir dir nochmals alle wichtigen Informationen per Mail zu. Zudem
            aktualisieren wir
            laufend die Webseite mit den neusten Informationen.
        </p>
        <p>
            Wir freuen uns, dass du dabei bist und wünschen dir viel Erfolg bei der Spender:innen-Suche!
        </p>
        <p>
            Herzliche Grüsse<br>
            Das Team von Höhenmeter für Menschen
        </p>

    </div>

    <!-- QR Code -->
    <div class="qr-code">
        <img src="data:image/svg+xml;base64,{{ $qrCodeData }}" alt="QR Code" />
        <p>Direktlink zu deinem persönlichen Bereich</p>
    </div>

@endsection
