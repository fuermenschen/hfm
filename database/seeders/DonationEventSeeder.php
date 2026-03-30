<?php

namespace Database\Seeders;

use App\Models\DonationEvent;
use Illuminate\Database\Seeder;

class DonationEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DonationEvent::query()->upsert(
            values: [
                [
                    'slug' => '2025',
                    'title' => 'Höhenmeter für Menschen',
                    'timezone' => 'Europe/Zurich',
                    'starts_at' => '2025-09-13 11:00:00',
                    'ends_at' => '2025-09-13 16:00:00',
                    'registration_opens_at' => '2025-01-31 23:00:00',
                    'athlete_registration_closes_at' => '2025-09-13 14:00:00',
                    'donor_registration_closes_at' => '2025-09-21 21:59:59',
                    'location_name' => 'Brühlgut Stiftung',
                    'location_street' => 'Brühlbergstrasse 6',
                    'location_postal_code' => '8400',
                    'location_city' => 'Winterthur',
                    'location_url' => 'https://s.geo.admin.ch/yat5fpx761jk',
                    'is_published' => true,
                    'content' => json_encode($this->eventContent(2025, '13. September 2025', '13 Uhr bis 18 Uhr'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
                [
                    'slug' => '2026',
                    'title' => 'Höhenmeter für Menschen',
                    'timezone' => 'Europe/Zurich',
                    'starts_at' => '2026-09-12 11:00:00',
                    'ends_at' => '2026-09-12 14:00:00',
                    'registration_opens_at' => '2026-09-12 11:00:00',
                    'athlete_registration_closes_at' => '2026-09-12 11:00:00',
                    'donor_registration_closes_at' => '2026-09-20 21:59:59',
                    'location_name' => 'Brühlgut Stiftung',
                    'location_street' => 'Brühlbergstrasse 6',
                    'location_postal_code' => '8400',
                    'location_city' => 'Winterthur',
                    'location_url' => 'https://s.geo.admin.ch/yat5fpx761jk',
                    'is_published' => true,
                    'content' => json_encode($this->eventContent(2026, '12. September 2026', '13 Uhr bis 16 Uhr'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
            uniqueBy: ['slug'],
            update: [
                'title',
                'timezone',
                'starts_at',
                'ends_at',
                'registration_opens_at',
                'athlete_registration_closes_at',
                'donor_registration_closes_at',
                'location_name',
                'location_street',
                'location_postal_code',
                'location_city',
                'location_url',
                'is_published',
                'content',
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function eventContent(int $year, string $eventDateLabel, string $eventTimeWindow): array
    {
        return [
            'hero' => [
                'copy_md' => 'Ein Spendenlauf für Winterthur. Wir rennen, fahren, rollen - für lokale Benefizpartner:innen. Bist auch du am Start?',
            ],
            'home' => [
                'about_heading' => 'Um was geht es?',
                'about_intro_md' => 'Höhenmeter für Menschen ist ein Spendenlauf in Winterthur. Es werden Spenden für lokale Benefizpartner:innen gesammelt.',
                'about_body_md' => 'Der Spendenlauf ist ähnlich organisiert wie ein klassischer «Sponsorenlauf». Nur wird nicht für einen Verein gesammelt, sondern für Kinder psychisch kranker Eltern, für Menschen mit Beeinträchtigung und für Menschen in schwierigen Lebenssituationen.',
            ],
            'results' => [
                'heading_md' => "Resultate {$year}",
            ],
            'faq' => [
                'general_event_md' => "Der Spendenlauf findet am **Samstag, {$eventDateLabel} in Winterthur** statt. Der Anlass dauert von **{$eventTimeWindow}**. Start und Ziel des Rundkurses sind bei der Brühlgut Stiftung (Brühlbergstrasse 6).",
            ],
            'seo' => [
                'meta_description_md' => "Höhenmeter für Menschen: Ein Spendenlauf in Winterthur am {$eventDateLabel}. Mit den Spenden werden Winterthurer Benefizpartner:innen unterstützt.",
                'og_description_md' => "Ein Spendenlauf in Winterthur für Winterthur am {$eventDateLabel}.",
            ],
            'invoice' => [
                'additional_information' => "Spendenrechnung Höhenmeter für Menschen Winterthur {$year}",
            ],
        ];
    }
}
