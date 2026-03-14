<?php

use App\Components\AdminAthleteTable;
use App\Models\Athlete;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Foundation\Vite as FoundationVite;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

it('downloads athlete welcome letter pdf via component action', function (): void {
    Vite::swap(new class extends FoundationVite
    {
        public function __invoke($entrypoints, $buildDirectory = null)
        {
            return new HtmlString('');
        }

        public function asset($asset, $buildDirectory = null)
        {
            return resource_path('images/letterhead_hfm.svg');
        }
    });

    $partner = Partner::create([
        'name' => 'Brühlgut Stiftung',
    ]);

    $sportType = SportType::create([
        'name' => 'Laufen',
    ]);

    /** @var Athlete $athlete */
    $athlete = Athlete::factory()->create([
        'first_name' => 'Anna',
        'last_name' => 'Muster',
        'login_token' => 'welcome-token-123',
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
    ]);

    Livewire::test(AdminAthleteTable::class)
        ->call('downloadWelcomeLetter', $athlete->id)
        ->assertStatus(200)
        ->assertFileDownloaded('Anna_Muster_Willkommensbrief.pdf');
});
