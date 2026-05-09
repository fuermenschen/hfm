<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DonationEvent;
use App\Models\Sponsor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventContentSponsorsBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $event2025Id = DonationEvent::query()
            ->where('slug', '2025')
            ->value('id');

        if (! is_numeric($event2025Id)) {
            return;
        }

        $rows = [
            [
                'donation_event_id' => (int) $event2025Id,
                'name' => 'Rohner Spiller',
                'description' => 'Rohner Spiller hat unsere Flyer und Poster gedruckt und unterstützt uns damit tatkräftig bei der Akquise von Sportler:innen und Spender:innen. Herzlichen Dank für die wertvolle Hilfe!',
                'logo_filename' => 'rohner_spiller.svg',
                'size' => 'large',
                'contribution_text' => 'Druck der Flyer und Poster.',
                'url' => 'https://www.rohnerspiller.ch',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'donation_event_id' => (int) $event2025Id,
                'name' => 'TM Kommunikation',
                'description' => 'TM Kommunikation ist unsere Kommunikationsagentur und übernimmt einen grossen Teil ihrer Arbeit für uns kostenlos. Vielen Dank für das Engagement und die professionelle Unterstützung!',
                'logo_filename' => 'tm_kommunikation.svg',
                'size' => 'large',
                'contribution_text' => 'Kostenlose Kommunikationsarbeit.',
                'url' => 'https://www.tmkommunikation.ch/',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'donation_event_id' => (int) $event2025Id,
                'name' => 'Veloplus',
                'description' => 'Veloplus unterstützt uns mit Gutscheinen über insgesamt Fr. 150.-, die wir an unsere Sportler:innen abgeben können. Herzlichen Dank!',
                'logo_filename' => 'veloplus.svg',
                'size' => 'small',
                'contribution_text' => 'Gutscheine für Sportler:innen.',
                'url' => 'https://www.veloplus.ch',
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'donation_event_id' => (int) $event2025Id,
                'name' => 'Intersport Egli',
                'description' => 'Eglisport unterstützt uns mit Gutscheinen über insgesamt Fr. 300.-, die wir an unsere Sportler:innen abgeben können. Herzlichen Dank!',
                'logo_filename' => 'intersport_egli.svg',
                'size' => 'small',
                'contribution_text' => 'Gutscheine für Sportler:innen.',
                'url' => 'https://eglisport.ch/',
                'sort_order' => 4,
                'is_published' => true,
            ],
        ];

        foreach ($rows as $row) {
            $sponsor = Sponsor::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'description' => $row['description'],
                    'logo_filename' => $row['logo_filename'],
                    'url' => $row['url'],
                ],
            );

            DB::table('donation_event_sponsor')->upsert(
                [
                    [
                        'donation_event_id' => $row['donation_event_id'],
                        'sponsor_id' => $sponsor->id,
                        'size' => $row['size'],
                        'contribution_text' => $row['contribution_text'],
                        'sort_order' => $row['sort_order'],
                        'is_published' => $row['is_published'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                ],
                ['donation_event_id', 'sponsor_id'],
                ['size', 'contribution_text', 'sort_order', 'is_published', 'updated_at'],
            );
        }
    }
}
