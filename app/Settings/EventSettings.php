<?php

namespace App\Settings;

use App\Models\DonationEvent;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Settings;

class EventSettings extends Settings
{
    public ?int $current_event_id = null;

    public static function group(): string
    {
        return 'eventSettings';
    }

    public static function settingsDetails(): array
    {
        return [
            'title' => 'Anlass Steuerung',
            'description' => 'Legt fest, welcher veröffentlichte Anlass aktuell auf öffentlichen Seiten verwendet wird.',
        ];
    }

    public static function rules(): array
    {
        return [
            'current_event_id' => 'nullable|integer|min:1',
        ];
    }

    public static function titles(): array
    {
        return [
            'current_event_id' => 'Aktueller Anlass',
        ];
    }

    public static function descriptions(): array
    {
        return [
            'current_event_id' => 'Der gewählte Anlass muss veröffentlicht sein, sonst bleiben öffentliche Event-Inhalte leer.',
        ];
    }

    /**
     * @return array<string, array<string, int|string>>
     */
    public static function options(): array
    {
        if (! Schema::hasTable('donation_events')) {
            return [
                'current_event_id' => [],
            ];
        }

        $eventOptions = DonationEvent::query()
            ->orderByDesc('starts_at')
            ->get(['id', 'title', 'slug', 'is_published'])
            ->mapWithKeys(function (DonationEvent $event): array {
                $suffix = $event->is_published ? '' : ' - NICHT VEROEFFENTLICHT';

                return [
                    sprintf('%s (%s)%s', $event->title, $event->slug, $suffix) => $event->id,
                ];
            })
            ->all();

        return [
            'current_event_id' => $eventOptions,
        ];
    }
}
