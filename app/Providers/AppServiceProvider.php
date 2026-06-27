<?php

namespace App\Providers;

use App\Services\BookChecker\BookCheckerService;
use App\Services\BookChecker\DefaultChecker;
use App\Services\BookChecker\MackChecker;
use App\Services\BookChecker\SteidlChecker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Checkers are tried in order; DefaultChecker must remain last
        $this->app->singleton(BookCheckerService::class, fn () => new BookCheckerService([
            new SteidlChecker(),
            new MackChecker(),
            new DefaultChecker(),
        ]));
    }

    public function boot(): void
    {
        //
    }
}
