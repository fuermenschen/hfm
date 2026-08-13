<?php

use App\Enums\StoryImageVariant;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Services\AthleteStoryImageService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('generates event and athlete specific story images', function (): void {
    Storage::fake('public');

    $event = DonationEvent::factory()->year(2036)->create(['title' => 'Winterlauf für alle']);
    foreach (['bruehlgut', 'iks', '143'] as $index => $logo) {
        Storage::disk('public')->put('partners/'.$logo.'_light.svg', File::get(resource_path('images/'.$logo.'_light.svg')));
        Storage::disk('public')->put('partners/'.$logo.'_dark.svg', File::get(resource_path('images/'.$logo.'_dark.svg')));

        $event->partners()->attach(Partner::factory()->create([
            'logo_light_filename' => $logo.'_light.svg',
            'logo_dark_filename' => $logo.'_dark.svg',
        ]), ['is_published' => true, 'sort_order' => ($index + 1) * 10]);
    }
    $athlete = ExternalUser::factory()->create([
        'first_name' => 'Anna',
        'last_name' => 'Muster',
    ]);
    $registration = AthleteRegistration::factory()
        ->forVerifiedEventUser($event, $athlete)
        ->create();

    $image = app(AthleteStoryImageService::class)->build($registration, StoryImageVariant::Light);

    expect($image['filename'])->toBe('story_single_light_'.$athlete->public_id_string.'.jpg')
        ->and(getimagesizefromstring($image['contents']))->toMatchArray(['0' => 1080, '1' => 1920]);
});

it('wraps event titles into balanced lines', function (): void {
    $service = app(AthleteStoryImageService::class);
    $method = new ReflectionMethod(AthleteStoryImageService::class, 'layoutTitle');
    $method->setAccessible(true);
    $layout = fn (string $title): array => $method->invoke($service, $title, resource_path('fonts/darkmode_on_xbold.otf'));

    $long = $layout->call($service, 'Höhenmeter für Menschen Winterlauf');
    expect($long['lines'])->toBe(['Höhenmeter für', 'Menschen Winterlauf'])
        ->and($long['fontSize'])->toBeGreaterThan(60)
        ->and($long['fontSize'])->toBeLessThan(115);

    $short = $layout->call($service, 'Winterlauf');
    expect($short['lines'])->toBe(['Winterlauf'])
        ->and($short['fontSize'])->toBe(115);

    $huge = $layout->call($service, 'Ein extrem langer Anlasstitel der niemals aufhört zu wachsen und wachsen');
    expect($huge['lines'])->toHaveCount(2)
        ->and($huge['fontSize'])->toBe(60);
});

it('allows athletes to retrieve only their own published event images', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $unverifiedEvent = DonationEvent::factory()->year(2037)->create();
    $unpublishedEvent = DonationEvent::factory()->year(2035)->create(['is_published' => false]);
    $athlete = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();
    $unverifiedRegistration = AthleteRegistration::factory()->forEvent($unverifiedEvent)->forExternalUser($athlete)->create(['verified' => false]);
    $unpublishedRegistration = AthleteRegistration::factory()->forVerifiedEventUser($unpublishedEvent, $athlete)->create();
    $stranger = ExternalUser::factory()->create();
    $strangerRegistration = AthleteRegistration::factory()->forVerifiedEventUser($event, $stranger)->create();

    actingAs($athlete, 'external');

    get(route('portal.story-image.download', [$registration, StoryImageVariant::Dark->value]))
        ->assertDownload('story_single_dark_'.$athlete->public_id_string.'.jpg');

    get(route('portal.story-image.preview', [$registration, StoryImageVariant::Dark->value]))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/jpeg');

    get(route('portal.story-image.download', [$unverifiedRegistration, StoryImageVariant::Light->value]))
        ->assertNotFound();

    get(route('portal.story-image.preview', [$unverifiedRegistration, StoryImageVariant::Light->value]))
        ->assertNotFound();

    get(route('portal.story-image.download', [$strangerRegistration, StoryImageVariant::Light->value]))
        ->assertNotFound();

    get(route('portal.story-image.preview', [$strangerRegistration, StoryImageVariant::Light->value]))
        ->assertNotFound();

    get(route('portal.story-image.download', [$unpublishedRegistration, StoryImageVariant::Light->value]))
        ->assertNotFound();
});

it('allows donors with pending donations to retrieve supported athlete images', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    $donor = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();
    Donation::factory()->forPair($donor, $registration)->create(['verified' => false]);

    actingAs($donor, 'external');

    get(route('portal.story-image.download', [$registration, StoryImageVariant::Light->value]))
        ->assertDownload('story_single_light_'.$athlete->public_id_string.'.jpg');
});

it('denies story images to external users without a donation or athlete registration', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    $stranger = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();

    actingAs($stranger, 'external');

    get(route('portal.story-image.download', [$registration, StoryImageVariant::Light->value]))
        ->assertNotFound();
});

it('shows personalized story sharing on athlete participation pages', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();

    actingAs($athlete, 'external');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertSeeText('Deine Runden können noch mehr bewegen')
        ->assertSeeText('Gewinne weitere Spender:innen für deine Spendenaktion: Teile personalisierte Bilder und Texte.')
        ->assertSeeText('Story teilen')
        ->assertSeeText('Kurz & direkt')
        ->assertSeeText('Etwas ausführlicher')
        ->assertSeeText('Für eine gute Sache')
        ->assertSeeText('Text kopieren')
        ->assertSeeText('Story-Bild herunterladen')
        ->assertSee(route('become-donor', ['sportlerin' => $athlete->public_id_string]), false)
        ->assertSee(route('portal.story-image.download', [$registration, 'light']), false)
        ->assertSee(route('portal.story-image.download', [$registration, 'dark']), false)
        ->assertSee(route('portal.story-image.preview', [$registration, 'light']), false)
        ->assertSee(route('portal.story-image.preview', [$registration, 'dark']), false);
});

it('shows welcome letter downloads for confirmed athlete participations', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();

    actingAs($athlete, 'external');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertSeeText('Willkommensbrief herunterladen')
        ->assertSee(route('portal.welcome-letter.download', $registration), false);
});

it('shows story image downloads for donors', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    $donor = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();
    Donation::factory()->forPair($donor, $registration)->create(['verified' => false]);

    actingAs($donor, 'external');

    get(route('portal.donations'))
        ->assertSuccessful()
        ->assertSeeText('Weitere Spender:innen für '.$athlete->privacy_name.' ('.$athlete->public_id_string.') finden')
        ->assertSeeText('Kennst du jemanden, der '.$athlete->privacy_name.' ('.$athlete->public_id_string.') unterstützen möchte? Teile diese persönliche Story auf WhatsApp oder Instagram.')
        ->assertSeeText('Story teilen')
        ->assertDontSeeText('Text kopieren')
        ->assertDontSeeText('Story-Bild hell')
        ->assertSee(route('portal.story-image.download', [$registration, 'light']), false)
        ->assertSee(route('portal.story-image.download', [$registration, 'dark']), false);
});

it('renders share text for equal split participations', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create(['partner_id' => null]);

    actingAs($athlete, 'external');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertSeeText('100 % deiner Spende geht an die Benefizpartner des Anlasses.');
});

it('hides story sharing for unconfirmed participations', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    AthleteRegistration::factory()->forEvent($event)->forExternalUser($athlete)->create(['verified' => false]);

    actingAs($athlete, 'external');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertDontSeeText('Deine Spendenaktion teilen');
});
