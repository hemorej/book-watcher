<?php

namespace App\Providers;

use App\Services\BookChecker\BookCheckerService;
use App\Services\BookChecker\DefaultChecker;
use App\Services\BookChecker\MackChecker;
use App\Services\BookChecker\SteidlChecker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bind BookCheckerService with its ordered checker list. Order is
     * significant: the first checker whose supports() matches wins, and
     * DefaultChecker (matches everything) must remain last.
     */
    public function register(): void
    {
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
