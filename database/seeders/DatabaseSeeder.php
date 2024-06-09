<?php

namespace Database\Seeders;

use App\Models\Athlete;
use App\Models\Partner;
use App\Models\SportType;
use App\Models\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // create users
        User::create([
            "name" => "Simon",
            "email" => "simon.moser@mailbox.org",
        ]);

        // create sport types
        SportType::create([
            "name" => "Rennen",
        ]);

        SportType::create([
            "name" => "Velofahren",
        ]);

        SportType::create([
            "name" => "Rollstuhl",
        ]);

        SportType::create([
            "name" => "Andere (bitte spezifizieren)",
        ]);

        // create partners
        Partner::create([
            "name" => "alle zu gleichen Teilen",
        ]);

        Partner::create([
            "name" => "Brühlgut Stiftung Winterthur",
        ]);

        Partner::create([
            "name" => "Institut Kinderseele Schweiz",
        ]);

        Partner::create([
            "name" => "Tel. 143 - Die Dargebotene Hand",
        ]);

        // create athletes (example data)
        Athlete::factory(10)->create();
    }
}
