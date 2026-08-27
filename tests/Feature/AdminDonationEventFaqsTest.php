<?php

use App\Components\AdminDonationEventFaqsForm;
use App\Models\DonationEvent;
use App\Models\Faq;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

it('shows faq assignment form on the event edit page', function (): void {
    $donationEvent = DonationEvent::factory()->create();

    actingAs(User::factory()->create());

    get(route('admin.donation-events.edit', $donationEvent))
        ->assertSuccessful()
        ->assertSee('FAQs')
        ->assertSee('Zugeordnete FAQs')
        ->assertSee('Verfügbare FAQs')
        ->assertSee('Zuordnungen');
});

it('loads faq assignments with group, order and publication state', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $assignedFaq = Faq::factory()->create(['title' => 'Alpha FAQ']);
    Faq::factory()->create(['title' => 'Beta FAQ']);

    $donationEvent->faqs()->attach($assignedFaq, [
        'group' => 'donors',
        'sort_order' => 20,
        'is_published' => false,
    ]);

    Livewire::test(AdminDonationEventFaqsForm::class, ['donationEvent' => $donationEvent])
        ->assertSet('faqRows.0.title', 'Alpha FAQ')
        ->assertSet('faqRows.0.attached', true)
        ->assertSet('faqRows.0.group', 'donors')
        ->assertSet('faqRows.0.sort_order', 20)
        ->assertSet('faqRows.0.is_published', false)
        ->assertSet('faqRows.1.title', 'Beta FAQ')
        ->assertSet('faqRows.1.attached', false)
        ->assertSet('faqRows.1.group', 'general')
        ->assertSee('Allgemein')
        ->assertSee('Spender:innen');
});

it('shows a plain-text excerpt of the answer', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    Faq::factory()->create([
        'title' => 'Alpha FAQ',
        'content_md' => "**Fett** und _kursiv_\n\n".str_repeat('Sehr langer Antworttext. ', 20),
    ]);

    $component = Livewire::test(AdminDonationEventFaqsForm::class, ['donationEvent' => $donationEvent]);
    $excerpt = $component->get('faqRows.0.excerpt');

    $component
        ->assertSet('faqRows.0.title', 'Alpha FAQ')
        ->assertSee('Fett und kursiv');

    expect($excerpt)->toStartWith('Fett und kursiv Sehr langer Antworttext.')
        ->and($excerpt)->toEndWith('...')
        ->and($excerpt)->not->toContain('<strong>');
});

it('attaches updates and detaches faqs', function (): void {
    $donationEvent = DonationEvent::factory()->create();
    $detachedFaq = Faq::factory()->create(['title' => 'Alpha FAQ']);
    $attachedFaq = Faq::factory()->create(['title' => 'Beta FAQ']);

    $donationEvent->faqs()->attach($detachedFaq, [
        'group' => 'general',
        'sort_order' => 10,
        'is_published' => false,
    ]);

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventFaqsForm::class, ['donationEvent' => $donationEvent])
        ->set('faqRows.0.attached', false)
        ->set('faqRows.1.attached', true)
        ->set('faqRows.1.group', 'athletes')
        ->set('faqRows.1.sort_order', 5)
        ->set('faqRows.1.is_published', false)
        ->call('save')
        ->assertHasNoErrors();

    assertDatabaseMissing('donation_event_faq', [
        'donation_event_id' => $donationEvent->id,
        'faq_id' => $detachedFaq->id,
    ]);
    assertDatabaseHas('donation_event_faq', [
        'donation_event_id' => $donationEvent->id,
        'faq_id' => $attachedFaq->id,
        'group' => 'athletes',
        'sort_order' => 5,
        'is_published' => false,
    ]);
});

it('undoes a new faq assignment before saving', function (): void {
    Faq::factory()->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventFaqsForm::class, ['donationEvent' => DonationEvent::factory()->create()])
        ->call('attachFaq', 0)
        ->assertSet('faqRows.0.attached', true)
        ->assertSee('Zuordnung rückgängig')
        ->call('detachFaq', 0)
        ->assertSet('faqRows.0.attached', false)
        ->assertDontSee('Zuordnung rückgängig');
});

it('validates faq rows', function (): void {
    Faq::factory()->count(2)->create();

    actingAs(User::factory()->create());

    $component = Livewire::test(AdminDonationEventFaqsForm::class, ['donationEvent' => DonationEvent::factory()->create()]);
    $faqRows = $component->get('faqRows');

    $component
        ->set('faqRows.1.id', $faqRows[0]['id'])
        ->set('faqRows.0.attached', true)
        ->set('faqRows.0.group', 'unknown')
        ->set('faqRows.0.sort_order', -1)
        ->call('save')
        ->assertHasErrors([
            'faqRows.0.group' => 'in',
            'faqRows.0.sort_order' => 'min',
            'faqRows.1.id' => 'distinct',
        ]);
});

it('ignores invalid pivot data for detached faqs', function (): void {
    Faq::factory()->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventFaqsForm::class, ['donationEvent' => DonationEvent::factory()->create()])
        ->set('faqRows.0.sort_order', -1)
        ->call('save')
        ->assertHasNoErrors();
});

it('rejects unauthenticated faq mutations', function (): void {
    Livewire::test(AdminDonationEventFaqsForm::class, ['donationEvent' => DonationEvent::factory()->create()])
        ->call('save')
        ->assertForbidden();
});
