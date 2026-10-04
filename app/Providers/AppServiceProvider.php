<?php

namespace App\Providers;

use App\Models\Estudiante;
use App\Observers\EstudianteObserver;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

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
        Carbon::setLocale(config('app.locale'));

        Estudiante::observe(EstudianteObserver::class);
    }
}
