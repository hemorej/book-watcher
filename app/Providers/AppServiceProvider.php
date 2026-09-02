<?php

namespace App\Providers;

use App\Services\BookChecker\BookCheckerService;
use App\Services\BookChecker\DefaultChecker;
use App\Services\BookChecker\MackChecker;
use App\Services\BookChecker\SteidlChecker;
use App\Services\LibraryMetadata\GoogleBooksProvider;
use App\Services\LibraryMetadata\LibraryMetadataResolver;
use App\Services\LibraryMetadata\OpenLibraryProvider;
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
            new SteidlChecker,
            new MackChecker,
            new DefaultChecker,
        ]));

        // ISBN metadata sources, tried in order: Open Library first, Google
        // Books as the fallback that backfills any gaps.
        $this->app->singleton(LibraryMetadataResolver::class, fn () => new LibraryMetadataResolver([
            new OpenLibraryProvider,
            new GoogleBooksProvider(config('services.google_books.key')),
        ]));
    }

    public function boot(): void
    {
        //
    }
}
