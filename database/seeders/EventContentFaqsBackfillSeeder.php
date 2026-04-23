<?php

namespace Database\Seeders;

use App\Models\DonationEvent;
use App\Models\Faq;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventContentFaqsBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $eventIds = DonationEvent::query()
            ->whereIn('slug', ['2025', '2026'])
            ->pluck('id', 'slug')
            ->all();

        if (! isset($eventIds['2025']) || ! isset($eventIds['2026'])) {
            return;
        }

        $timingRows = [
            [
                'donation_event_id' => (int) $eventIds['2025'],
                'group' => 'general',
                'title' => 'Wann und wo findet der Anlass statt?',
                'content_md' => 'Der Spendenlauf findet am **Samstag, 13. September 2025 in Winterthur** statt. Der Anlass dauert von **13 Uhr bis 18 Uhr**. Start und Ziel des Rundkurses sind bei der Brühlgut Stiftung (Brühlbergstrasse 6).\n\nHier findest du den Standort auf der Karte: [Zur Karte](https://s.geo.admin.ch/gxxx987d8p5k).',
                'sort_order' => 10,
                'is_published' => true,
            ],
            [
                'donation_event_id' => (int) $eventIds['2026'],
                'group' => 'general',
                'title' => 'Wann und wo findet der Anlass statt?',
                'content_md' => 'Der Spendenlauf findet am **Samstag, 12. September 2026 in Winterthur** statt. Der Anlass dauert von **13 Uhr bis 16 Uhr**. Start und Ziel des Rundkurses sind bei der Brühlgut Stiftung (Brühlbergstrasse 6).\n\nHier findest du den Standort auf der Karte: [Zur Karte](https://s.geo.admin.ch/gxxx987d8p5k).',
                'sort_order' => 10,
                'is_published' => true,
            ],
        ];

        foreach ($timingRows as $row) {
            $this->upsertFaqByEventSlot(
                eventId: $row['donation_event_id'],
                group: $row['group'],
                sortOrder: $row['sort_order'],
                title: $row['title'],
                contentMarkdown: $row['content_md'],
                isPublished: $row['is_published'],
            );
        }

        $extractedRows = $this->hardcodedFaqRows();

        foreach ($extractedRows as $row) {
            foreach ([(int) $eventIds['2025'], (int) $eventIds['2026']] as $eventId) {
                $this->upsertFaqByEventSlot(
                    eventId: $eventId,
                    group: $row['group'],
                    sortOrder: $row['sort_order'],
                    title: $row['title'],
                    contentMarkdown: $row['content_md'],
                    isPublished: true,
                );
            }
        }

        $events = DonationEvent::query()->get(['id', 'content']);

        foreach ($events as $event) {
            $content = is_array($event->content) ? $event->content : [];

            if (! array_key_exists('faq', $content)) {
                continue;
            }

            unset($content['faq']);

            DB::table('donation_events')
                ->where('id', $event->id)
                ->update([
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
        }
    }

    protected function upsertFaqByEventSlot(
        int $eventId,
        string $group,
        int $sortOrder,
        string $title,
        string $contentMarkdown,
        bool $isPublished,
    ): void {
        $existingPivot = DB::table('donation_event_faq')
            ->where('donation_event_id', $eventId)
            ->where('group', $group)
            ->where('sort_order', $sortOrder)
            ->first();

        $faqId = is_object($existingPivot) ? (int) $existingPivot->faq_id : null;

        if ($faqId !== null) {
            Faq::query()
                ->whereKey($faqId)
                ->update([
                    'title' => $title,
                    'content_md' => $contentMarkdown,
                    'updated_at' => now(),
                ]);
        } else {
            $faq = Faq::query()->firstOrCreate(
                ['title' => $title, 'content_md' => $contentMarkdown],
                ['title' => $title, 'content_md' => $contentMarkdown],
            );

            $faqId = (int) $faq->id;
        }

        DB::table('donation_event_faq')->upsert(
            [
                [
                    'donation_event_id' => $eventId,
                    'faq_id' => $faqId,
                    'group' => $group,
                    'sort_order' => $sortOrder,
                    'is_published' => $isPublished,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            ],
            ['donation_event_id', 'faq_id'],
            ['group', 'sort_order', 'is_published', 'updated_at'],
        );
    }

    /**
     * @return array<int, array{group:string,title:string,content_md:string,sort_order:int}>
     */
    protected function hardcodedFaqRows(): array
    {
        return [
            ['group' => 'general', 'title' => 'Wie kann man am besten anreisen?', 'content_md' => 'Am besten reist du mit dem öffentlichen Verkehr an. Die Brühlgut Stiftung ist mit dem Bus gut erreichbar ([Zum Fahrplan](https://www.sbb.ch/de?stops=[{%22label%22%3A%22%22%2C%22type%22%3A%22ID%22%2C%22value%22%3A%22%22}%2C{%22value%22%3A%228576180%22%2C%22type%22%3A%22ID%22%2C%22label%22%3A%22Winterthur%2C%20Loki%22}])). Wenn du mit dem Auto anreist, gibt es das nahegelegene Parkhaus Lokwerk. Bei der Brühlgut Stiftung hat es keine Parkplätze.', 'sort_order' => 20],
            ['group' => 'general', 'title' => 'Gibt es Verpflegung vor Ort?', 'content_md' => 'Es gibt nur Verpflegung für Sportler:innen. Es wird Getränke (Wasser und isotonisches Getränk) und Snacks geben. Die Verpflegungsstation befindet sich beim Start/Ziel des Rundkurses.', 'sort_order' => 30],
            ['group' => 'general', 'title' => 'Was passiert bei schlechtem Wetter?', 'content_md' => 'Der Anlass findet bei jedem Wetter statt. Sollte es regnen, empfehlen wir, entsprechende Kleidung mitzunehmen. Bei Gewitter oder Sturm kann der Anlass abgesagt werden. Wir informieren dich in diesem Fall rechtzeitig.', 'sort_order' => 40],
            ['group' => 'general', 'title' => 'Ich brauche für die Teilnahme weitere Unterstützung. An wen kann ich mich wenden?', 'content_md' => 'Wir geben unser Bestes, dass alle einen Teil von Höhenmeter für Menschen sein können. Sei es, dass du Unterstützung bei der Teilnahme als Sportler:in hast, dass du eine Unverträglichkeit hast und Fragen zum Essen hast oder sonstige Anliegen hast.\n\nBitte melde dich bei uns, wir finden eine Lösung.', 'sort_order' => 50],
            ['group' => 'athletes', 'title' => 'Wie läuft alles ab?', 'content_md' => "Der Ablauf für dich als Sportler:in ist wie folgt:\n\n- Du überlegst dir, welche:n der drei Benefizpartner:innen du unterstützen möchtest (alle drei auch möglich).\n- Du registrierst dich über das Anmeldeformular als Sportler:in.\n- Wir senden dir einige Flyer und Informationen zu, die du an deine Freunde und Familie weitergeben kannst.\n- Du suchst Spender:innen für dich. Diese können dich pro Runde unterstützen.\n- Am Anlass läufst oder fährst du so viele Runden wie möglich.\n- Fertig! Den Rest übernehmen wir.", 'sort_order' => 10],
            ['group' => 'athletes', 'title' => 'Wann startet der Lauf?', 'content_md' => 'Es gibt um **13 Uhr** einen gemeinsamen Start für alle Sportler:innen.', 'sort_order' => 20],
            ['group' => 'athletes', 'title' => 'Wie verläuft der Rundkurs?', 'content_md' => "Der Rundkurs führt durch das Brühlberg-Quartier in Winterthur. Die Strecke ist **1.75 km** lang, weist **50 Höhenmeter** auf und ist - bis auf ein kurzes Stück durch den Brühlgutpark - vollständig asphaltiert. Das unbefestigte Teilstück kann bei Bedarf über die Waldhofstrasse, Zürcherstrasse und zurück zur Brühlbergstrasse umfahren werden. Der Anstieg hat eine **Steigung von bis zu 11%**. Start und Ziel sind bei der Brühlgut Stiftung; von dort verläuft die Strecke im Uhrzeigersinn durch das Quartier.\n\nDu kannst die Strecke auch [online ansehen](https://s.geo.admin.ch/yb9swnrqvtal).", 'sort_order' => 30],
            ['group' => 'athletes', 'title' => 'Welche Sportarten sind geeignet?', 'content_md' => 'Am besten geeignet sind wohl Laufen und Velofahren. Aber es ist grundsätzlich alles erlaubt. Bitte beachte, dass es Steigungen von bis zu 11% hat.\n\nWer die Strecke aus eigener Kraft zurücklegen kann, soll dies auch tun. Wer dazu nicht in der Lage ist und Hilfsmittel oder Hilfspersonen benötigt, darf dies.', 'sort_order' => 40],
            ['group' => 'athletes', 'title' => 'Kann ich etwas gewinnen?', 'content_md' => 'Ja, es gibt verschiedene Preise zu gewinnen, die wir von unseren Sponsor:innen haben. Diese werden an die Sportler:innen vergeben, welche am meisten Spenden sammeln konnten.', 'sort_order' => 50],
            ['group' => 'athletes', 'title' => 'Darf ich mit dem Elektrovelo oder Elektroscooter kommen?', 'content_md' => 'Nein, grundsätzlich soll die Strecke aus eigener Kraft zurückgelegt werden. Ausgenommen hiervon sind Sportler:innen, denen es nicht möglich ist, die Strecke ohne Hilfsmittel oder Begleitpersonen zurückzulegen.', 'sort_order' => 60],
            ['group' => 'athletes', 'title' => 'Ich bin nicht besonders sportlich. Kann ich trotzdem teilnehmen?', 'content_md' => 'Ja, der Anlass ist für alle geeignet. Du kannst so viele Runden laufen oder fahren, wie du möchtest. Es geht nicht darum, wer am besten ist, sondern darum, gemeinsam Spenden zu sammeln.', 'sort_order' => 70],
            ['group' => 'athletes', 'title' => 'Wie ist der Ablauf am Anlass?', 'content_md' => "- Ab **12:00 Uhr** sind die Startnummern im Rundenbüro abholbereit.\n- Die Startnummer muss gut sichtbar auf der Vorderseite deines Trikots befestigt werden.\n- Um **13:00 Uhr** gibt es einen gemeinsamen Start mit allen Sportler:innen.\n- Um **16:00 Uhr** gibt es einen gemeinsamen Abschluss des Laufs.\n- Falls du nicht um 13 Uhr starten kannst, finden wir eine Lösung.\n- Wir zählen deine Runden. Es hilft, wenn du ebenfalls mitzählst.", 'sort_order' => 80],
            ['group' => 'donors', 'title' => 'Wie läuft alles ab?', 'content_md' => "Der Ablauf für dich als Spender:in ist wie folgt:\n\n- Du überlegst dir, welche:n Sportler:in du unterstützen möchtest.\n- Du überlegst dir, welchen Betrag du pro Runde spenden möchtest.\n- Du meldest dich über den Newsletter an und wir informieren dich, sobald das Spendenformular wieder offen ist.\n- Du feuerst die Sportler:innen kräftig an am 12. September 2026.\n- Wir senden dir eine Rechnung mit einem Einzahlungsschein zu.\n- Fertig! Vielen Dank für deine Unterstützung.", 'sort_order' => 10],
            ['group' => 'donors', 'title' => 'Wie kann ich meine Spende bezahlen?', 'content_md' => 'Du bekommst nach dem Anlass eine Rechnung mit einem Einzahlungsschein von uns. Die Rechnung wird entsprechend der zurückgelegten Runden ausgestellt.', 'sort_order' => 20],
            ['group' => 'donors', 'title' => 'Wie kann ich meine Spende von den Steuern abziehen?', 'content_md' => 'Da der Verein für Menschen eine gemeinnützige Organisation ist, kannst du deine Spende von den Steuern abziehen. Die Beilage der Rechnung sollte dafür reichen.', 'sort_order' => 30],
            ['group' => 'donors', 'title' => 'An wen gehen die Spenden?', 'content_md' => "Die Spenden gehen an die Benefizpartner:innen. Aktuell bestätigt ist:\n\n- **Brühlgut Stiftung** - Die Brühlgut Stiftung begleitet und fördert Menschen mit Beeinträchtigung. [Brühlgut Stiftung](https://www.xn--brhlgut-o2a.ch/)\n- **Weiterer Benefizpartner** - Information folgt.\n- **Weiterer Benefizpartner** - Information folgt.", 'sort_order' => 40],
            ['group' => 'donors', 'title' => 'Wie viel von meiner Spende kommt bei den Benefizpartner:innen an?', 'content_md' => '**100% deiner Spende kommt bei den Benefizpartner:innen an.** Der Verein für Menschen übernimmt die gesamten Kosten des Anlasses.', 'sort_order' => 50],
            ['group' => 'donors', 'title' => 'Wie viel soll ich spenden?', 'content_md' => 'Das ist dir überlassen, jeder Betrag ist willkommen. Du kannst einen Betrag pro Runde und auch Mindest- oder Maximalbeträge festlegen. Viele Spender:innen geben 5-10 Franken pro Runde.', 'sort_order' => 60],
            ['group' => 'background', 'title' => 'Weshalb heisst der Anlass Höhenmeter für Menschen?', 'content_md' => 'Für manche Menschen fühlt sich jeder einzelne Tag an, als müssten sie Berge erklimmen. Mit dem Anlass "Höhenmeter für Menschen" möchten wir versuchen, dieses Gefühl nachvollziehen und dabei Geld für Organisationen sammeln, welche diese Menschen unterstützen und begleiten.\n\nWir erklimmen Höhenmeter, um die täglichen Anstrengungen und Hindernisse zu symbolisieren, die viele Menschen überwinden müssen. Gemeinsam setzen wir ein Zeichen der Solidarität und Unterstützung.', 'sort_order' => 10],
            ['group' => 'background', 'title' => 'Wie wurden die Benefizpartner:innen ausgewählt?', 'content_md' => "Bei der Auswahl der Benefizpartner:innen haben wir darauf geachtet, dass nur Institutionen ausgewählt werden, die in Winterthur und Umgebung tätig sind. Zudem haben wir uns auf Institutionen fokussiert, die sich für Menschen einsetzen. Aktuell bestätigt ist:\n\n- [Brühlgut Stiftung](https://www.xn--brhlgut-o2a.ch/)\n- Weiterer Benefizpartner folgt.\n- Weiterer Benefizpartner folgt.", 'sort_order' => 20],
        ];
    }
}
