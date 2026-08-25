<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CurrentDonationEventService;
use App\Support\Pulse\ResolvesUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\DevCommands;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Contracts\ResolvesUsers as PulseResolvesUsersContract;
use Symfony\Component\Mime\Address;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Scoped binding is required because the service uses once() to memoize
        // current event resolution across repeated view composer resolutions.
        $this->app->scoped(CurrentDonationEventService::class);

        // Pulse user resolution for both admin and external user guards.
        $this->app->singleton(PulseResolvesUsersContract::class, ResolvesUsers::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isLocal()) {
            Model::preventLazyLoading();
        }

        // define dev commands
        DevCommands::register('mailpit', 'mailpit')->pink();

        Gate::define('viewPulse', fn (User $user): true => true);
        Gate::define('viewLogViewer', fn (User $user): true => true);

        View::composer('*', function ($view): void {
            $eventService = resolve(CurrentDonationEventService::class);

            $view->with('currentDonationEvent', $eventService->current());
            $view->with('currentDonationEventIssue', $eventService->issue());
        });

        // Global logging for notifications and mails
        Event::listen(NotificationSending::class, function (NotificationSending $event): void {
            $notifiable = is_object($event->notifiable) ? get_class($event->notifiable) : gettype($event->notifiable);
            Log::info('Notification sending', [
                'channel' => $event->channel,
                'notification' => get_class($event->notification),
                'notifiable_type' => $notifiable,
                'notifiable_id' => method_exists($event->notifiable, 'getKey') ? $event->notifiable->getKey() : null,
            ]);
        });

        Event::listen(NotificationSent::class, function (NotificationSent $event): void {
            $notifiable = is_object($event->notifiable) ? get_class($event->notifiable) : gettype($event->notifiable);
            Log::info('Notification sent', [
                'channel' => $event->channel,
                'notification' => get_class($event->notification),
                'notifiable_type' => $notifiable,
                'notifiable_id' => method_exists($event->notifiable, 'getKey') ? $event->notifiable->getKey() : null,
                'response' => $event->response ?? null,
            ]);
        });

        Event::listen(NotificationFailed::class, function (NotificationFailed $event): void {
            $notifiable = is_object($event->notifiable) ? get_class($event->notifiable) : gettype($event->notifiable);
            Log::error('Notification failed', [
                'channel' => $event->channel,
                'notification' => get_class($event->notification),
                'notifiable_type' => $notifiable,
                'notifiable_id' => method_exists($event->notifiable, 'getKey') ? $event->notifiable->getKey() : null,
                'response' => $event->response ?? null,
            ]);
        });

        // Mail events
        Event::listen(MessageSending::class, function (MessageSending $event): void {
            $to = collect($event->message->getTo())
                ->map(fn (Address $addr): string => $addr->getAddress())
                ->all();
            Log::info('Mail sending', [
                'to' => $to,
                'subject' => $event->message->getSubject(),
            ]);
        });

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            $to = collect($event->message->getTo())
                ->map(fn (Address $addr): string => $addr->getAddress())
                ->all();
            Log::info('Mail sent', [
                'to' => $to,
                'subject' => $event->message->getSubject(),
            ]);
        });

        // Capture failures from queued mail/notification jobs (e.g., mail server down)
        Event::listen(JobFailed::class, function (JobFailed $event): void {
            $payload = $event->job->payload();
            $displayName = $payload['displayName'] ?? $event->job->resolveName();
            $context = [
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'display_name' => $displayName,
                'exception' => $event->exception->getMessage(),
            ];

            // Only log for queued mail / notification related jobs or log all failures?
            if (is_string($displayName) && (
                str_contains($displayName, 'Mail') ||
                str_contains($displayName, 'Mailable') ||
                str_contains($displayName, 'Notification')
            )) {
                Log::error('Queued job failed (mail/notification)', $context);
            }
        });
    }
}
