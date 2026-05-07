<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventContentSportTypesBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $eventIds = DB::table('donation_events')->pluck('id')->all();
        $sportTypeIds = DB::table('sport_types')->pluck('id')->all();

        if ($eventIds === [] || $sportTypeIds === []) {
            return;
        }

        $rows = [];

        foreach ($eventIds as $eventId) {
            foreach ($sportTypeIds as $index => $sportTypeId) {
                $rows[] = [
                    'donation_event_id' => (int) $eventId,
                    'sport_type_id' => (int) $sportTypeId,
                    'sort_order' => ($index + 1) * 10,
                    'is_enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('donation_event_sport_type')->insertOrIgnore($rows);
    }
}
