<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EventContentBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EventContentPartnersBackfillSeeder::class,
            EventContentSponsorsBackfillSeeder::class,
            EventContentFaqsBackfillSeeder::class,
            EventContentSportTypesBackfillSeeder::class,
        ]);
    }
}
