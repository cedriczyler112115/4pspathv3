<?php

namespace App\Providers;

use App\Models\ApplicationSetting;
use App\View\Composers\SidebarMenuComposer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureApplicationSettings();
        $this->configureViewComposers();
    }

    protected function configureApplicationSettings(): void
    {
        $appName = ApplicationSetting::valueFor('app_name');

        if (filled($appName)) {
            config(['app.name' => (string) $appName]);
        }
    }

    protected function configureViewComposers(): void
    {
        View::composer('layouts.app.sidebar', SidebarMenuComposer::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
