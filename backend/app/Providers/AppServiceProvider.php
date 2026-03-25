<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Load auditor configuration
        $this->publishes([
            __DIR__.'/../../config/auditor.php' => config_path('auditor.php'),
        ], 'auditor-config');
    }
}
