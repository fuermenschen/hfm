<?php

use App\Enums\StoryImageVariant;
use App\Models\AthleteRegistration;
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

it('shows personalized story sharing on athlete participation pages', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();

    actingAs($athlete, 'external');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertSeeText('Deine Spendenaktion teilen')
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
