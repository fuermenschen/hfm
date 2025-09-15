<?php

namespace App\Providers;

use App\Services\DashboardService;
use App\Services\DonationService;
use App\Services\DonorService;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
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

        // Inject computed dashboard data via a view composer
        View::composer('pages.admin.dashboard', function ($view): void {
            /** @var DashboardService $dashboard */
            $dashboard = app(DashboardService::class);
            $view->with($dashboard->getData());
        });
    }
}
