<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\SaveDonationEventAction;
use App\Models\DonationEvent;
use App\Settings\EventSettings;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AdminDonationEventForm extends Component
{
    public ?DonationEvent $donationEvent = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public bool $isCurrentEvent = false;

    public bool $hasUnsavedChanges = false;

    public function mount(?DonationEvent $donationEvent = null, bool $isCurrentEvent = false): void
    {
        $this->donationEvent = $donationEvent;
        $this->isCurrentEvent = $isCurrentEvent;
        $this->form = $this->formValues($donationEvent);
    }

    public function render(): Factory|View
    {
        return view('components.admin-donation-event-form');
    }

    public function save(SaveDonationEventAction $saveDonationEvent): void
    {
        abort_unless(Auth::check(), 403);

        try {
            $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        } catch (ValidationException $validationException) {
            $this->hasUnsavedChanges = true;

            throw $validationException;
        }

        if ($this->requiresUnpublishedConfirmation()) {
            $this->hasUnsavedChanges = true;
            Flux::modal('confirm-current-event-unpublish')->show();

            return;
        }

        $this->persist($saveDonationEvent, $validated['form']);
    }

    public function confirmUnpublished(SaveDonationEventAction $saveDonationEvent): void
    {
        abort_unless(Auth::check(), 403);

        $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        $this->persist($saveDonationEvent, $validated['form']);
    }

    protected function requiresUnpublishedConfirmation(): bool
    {
        return $this->donationEvent instanceof DonationEvent
            && $this->donationEvent->is_published
            && ! (bool) $this->form['is_published']
            && resolve(EventSettings::class)->current_event_id === $this->donationEvent->id;
    }

    /**
     * @param  array<string, mixed>  $form
     */
    protected function persist(SaveDonationEventAction $saveDonationEvent, array $form): void
    {
        $isCreating = ! $this->donationEvent instanceof DonationEvent;

        $this->donationEvent = $saveDonationEvent($this->donationEvent, $form);
        $this->form = $this->formValues($this->donationEvent);
        $this->hasUnsavedChanges = false;

        Flux::toast(
            heading: 'Gespeichert',
            text: $isCreating ? 'Anlass wurde erstellt.' : 'Anlass wurde aktualisiert.',
            variant: 'success',
        );

        $this->redirect(route('admin.donation-events.edit', $this->donationEvent), navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => ['required', 'string', 'max:255', Rule::unique(DonationEvent::class, 'slug')->ignore($this->donationEvent)],
            'form.starts_at' => ['required', 'date'],
            'form.ends_at' => ['required', 'date', 'after:form.starts_at'],
            'form.registration_opens_at' => ['nullable', 'date'],
            'form.athlete_registration_closes_at' => ['nullable', 'date', 'after_or_equal:form.registration_opens_at'],
            'form.donor_registration_closes_at' => ['nullable', 'date', 'after_or_equal:form.registration_opens_at'],
            'form.location_name' => ['nullable', 'string', 'max:255'],
            'form.location_street' => ['nullable', 'string', 'max:255'],
            'form.location_postal_code' => ['nullable', 'string', 'max:255'],
            'form.location_city' => ['required', 'string', 'max:255'],
            'form.location_url' => ['nullable', 'url', 'max:255'],
            'form.is_published' => ['boolean'],
            'form.has_equal_split_option' => ['boolean'],
            'form.content.hero.copy_md' => ['nullable', 'string'],
            'form.content.home.about_heading' => ['nullable', 'string', 'max:255'],
            'form.content.home.about_intro_md' => ['nullable', 'string'],
            'form.content.home.about_body_md' => ['nullable', 'string'],
            'form.content.results.heading_md' => ['nullable', 'string', 'max:255'],
            'form.content.seo.meta_description_md' => ['nullable', 'string'],
            'form.content.seo.og_description_md' => ['nullable', 'string'],
            'form.content.invoice.additional_information' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'form.title' => 'Titel',
            'form.slug' => 'Slug',
            'form.starts_at' => 'Start',
            'form.ends_at' => 'Ende',
            'form.registration_opens_at' => 'Anmeldestart',
            'form.athlete_registration_closes_at' => 'Anmeldeschluss Sportler:innen',
            'form.donor_registration_closes_at' => 'Anmeldeschluss Spender:innen',
            'form.location_name' => 'Name des Veranstaltungsorts',
            'form.location_street' => 'Strasse',
            'form.location_postal_code' => 'PLZ',
            'form.location_city' => 'Stadt',
            'form.location_url' => 'Kartenlink',
            'form.is_published' => 'Veröffentlichung',
            'form.has_equal_split_option' => 'Spendenaufteilung',
            'form.content.hero.copy_md' => 'Hero-Text',
            'form.content.home.about_heading' => 'Homepage-Überschrift',
            'form.content.home.about_intro_md' => 'Homepage-Einleitung',
            'form.content.home.about_body_md' => 'Homepage-Haupttext',
            'form.content.results.heading_md' => 'Resultate-Überschrift',
            'form.content.seo.meta_description_md' => 'SEO-Beschreibung',
            'form.content.seo.og_description_md' => 'OpenGraph-Beschreibung',
            'form.content.invoice.additional_information' => 'Zusatzinformation Spendenrechnung',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formValues(?DonationEvent $donationEvent): array
    {
        if (! $donationEvent instanceof DonationEvent) {
            return [
                'title' => '',
                'slug' => '',
                'starts_at' => '',
                'ends_at' => '',
                'registration_opens_at' => '',
                'athlete_registration_closes_at' => '',
                'donor_registration_closes_at' => '',
                'location_name' => '',
                'location_street' => '',
                'location_postal_code' => '',
                'location_city' => '',
                'location_url' => '',
                'is_published' => false,
                'has_equal_split_option' => true,
                'content' => $this->contentValues(null),
            ];
        }

        return [
            'title' => $donationEvent->title,
            'slug' => $donationEvent->slug,
            'starts_at' => $this->dateTimeValue($donationEvent->starts_at),
            'ends_at' => $this->dateTimeValue($donationEvent->ends_at),
            'registration_opens_at' => $this->dateTimeValue($donationEvent->registration_opens_at),
            'athlete_registration_closes_at' => $this->dateTimeValue($donationEvent->athlete_registration_closes_at),
            'donor_registration_closes_at' => $this->dateTimeValue($donationEvent->donor_registration_closes_at),
            'location_name' => $donationEvent->location_name ?? '',
            'location_street' => $donationEvent->location_street ?? '',
            'location_postal_code' => $donationEvent->location_postal_code ?? '',
            'location_city' => $donationEvent->location_city,
            'location_url' => $donationEvent->location_url ?? '',
            'is_published' => (bool) $donationEvent->is_published,
            'has_equal_split_option' => (bool) $donationEvent->has_equal_split_option,
            'content' => $this->contentValues($donationEvent),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function contentValues(?DonationEvent $donationEvent): array
    {
        return [
            'hero' => [
                'copy_md' => $donationEvent?->contentValue('hero.copy_md') ?? '',
            ],
            'home' => [
                'about_heading' => $donationEvent?->contentValue('home.about_heading') ?? '',
                'about_intro_md' => $donationEvent?->contentValue('home.about_intro_md') ?? '',
                'about_body_md' => $donationEvent?->contentValue('home.about_body_md') ?? '',
            ],
            'results' => [
                'heading_md' => $donationEvent?->contentValue('results.heading_md') ?? '',
            ],
            'seo' => [
                'meta_description_md' => $donationEvent?->contentValue('seo.meta_description_md') ?? '',
                'og_description_md' => $donationEvent?->contentValue('seo.og_description_md') ?? '',
            ],
            'invoice' => [
                'additional_information' => $donationEvent?->contentValue('invoice.additional_information') ?? '',
            ],
        ];
    }

    protected function dateTimeValue(mixed $value): string
    {
        return is_object($value) && method_exists($value, 'format')
            ? $value->format('Y-m-d\\TH:i:s')
            : '';
    }
}
