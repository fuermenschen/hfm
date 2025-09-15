<?php

namespace App\Providers;

use App\Services\DashboardService;
use App\Services\DonationService;
use App\Services\DonorService;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
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

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DonorService::class);
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(DonationService::class);
        $this->app->singleton(DashboardService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isLocal()) {
            Model::preventLazyLoading();
        }

        Gate::define('viewPulse', fn (User $user) => true);
        Gate::define('viewLogViewer', fn (User $user) => true);

        // Inject computed dashboard data via a view composer
        View::composer('pages.admin.dashboard', function ($view): void {
            /** @var DashboardService $dashboard */
            $dashboard = app(DashboardService::class);
            $view->with($dashboard->getData());
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
            $to = collect($event->message->getTo() ?? [])->map(fn ($addr) => method_exists($addr, 'getAddress') ? $addr->getAddress() : (string) $addr)->all();
            Log::info('Mail sending', [
                'to' => $to,
                'subject' => method_exists($event->message, 'getSubject') ? $event->message->getSubject() : null,
            ]);
        });

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            $to = collect($event->message->getTo() ?? [])->map(fn ($addr) => method_exists($addr, 'getAddress') ? $addr->getAddress() : (string) $addr)->all();
            Log::info('Mail sent', [
                'to' => $to,
                'subject' => method_exists($event->message, 'getSubject') ? $event->message->getSubject() : null,
            ]);
        });

        // Capture failures from queued mail/notification jobs (e.g., mail server down)
        Event::listen(JobFailed::class, function (JobFailed $event): void {
            $payload = $event->job->payload();
            $displayName = $payload['displayName'] ?? ($event->job->resolveName() ?? 'unknown');
            $context = [
                'connection' => $event->connectionName,
                'queue' => method_exists($event->job, 'getQueue') ? $event->job->getQueue() : null,
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
