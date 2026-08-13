@extends('printables.base')

@props(['athlete'])

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

        <div>
            <p>
                @foreach ($officialAddress as $line)
                    {{ $line }}<br>
                @endforeach
            </p>
            <p>{{ $mailFromAddress }}</p>
        </div>
    </div>

    <!-- Recipient -->
    <div class="recipient">
        <p class="sender">{{ $associationName }}, {{ $associationDomain }}</p>
        <p>
            @if ($registration->adult === false)
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
        <p>{{ $associationCity }}, {{ date('d.m.Y') }}</p>
    </div>

    <!-- Subject -->
    <div class="subject">
        <p><strong>{{ $event->title }}</strong>: Willkommen!</p>
    </div>

    <!-- Body -->
    <div class="body">
        <p>
            Liebe:r {{ $athlete->first_name }}
        </p>
        <p>
            Vielen Dank, dass du beim Anlass <strong>{{ $event->title }}</strong> mitmachst!
        </p>
        <p>
            Du hast bei deiner Anmeldung angegeben, dass du ungefähr
            <strong>{{ $registration->rounds_estimated }} Runden</strong>
            zurücklegen möchtest ({{ $registration->sportType->name }}). Die Spenden deiner Spender:innen gehen dann an
            die Organisation <strong>{{ $partnerName }}</strong>.
        </p>
        <p>
            Wir möchten dir das Suchen von
            Spender:innen so einfach wie möglich machen.
            Deshalb erhältst du anbei verschiedene personalisierte Materialien, die dich dabei unterstützen.
            Zudem findest du in deinem persönlichen Bereich auf der Webseite weitere personalisierte Materialien.
        </p>
        <p>
            Wenn du weitere Materialien benötigst oder sonst etwas von uns brauchst, melde dich jederzeit bei uns.
        </p>
        <p>
            Am Anlass selbst, am <strong>{{ $eventDate }}</strong> hast du dann von {{ $eventStartTime }}&nbsp;Uhr bis {{ $eventEndTime }}&nbsp;Uhr
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
            Das Team von {{ $event->title }}
        </p>
        <p>
        P.S.: Auch dieses Jahr dürfen sich die Sportler:innen, die am meisten Spendengelder sammeln, auf attraktive Preise freuen!
        </p>



    </div>

    <!-- QR Code -->
    <div class="qr-code">
        <img src="{{ $qrCodeDataUri }}" alt="QR Code" />
        <p>Direktlink zu deinem persönlichen Bereich</p>
    </div>

@endsection
