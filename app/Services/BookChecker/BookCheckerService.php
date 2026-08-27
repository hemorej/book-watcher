<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches a book's publisher page once and delegates parsing to the first
 * registered {@see CheckerInterface} that supports the URL.
 *
 * The checker list is wired up as a singleton in
 * {@see \App\Providers\AppServiceProvider::register()}; {@see DefaultChecker}
 * is always last so an unrecognised URL yields Unsure rather than an error.
 */
class BookCheckerService
{
    /** @param CheckerInterface[] $checkers Ordered list; first match wins. DefaultChecker must be last. */
    public function __construct(private readonly array $checkers) {}

    /**
     * Fetch $url (spoofed desktop UA, 15s timeout) and return its availability.
     * Returns Unsure on any HTTP error, timeout, or when no checker matches.
     */
    public function check(string $url): BookStatus
    {
        try {
            $response = Http::withUserAgent(
                'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            )->timeout(15)->get($url);

            if (! $response->successful()) {
                Log::warning('book_checker.non_200_response', ['url' => $url, 'status' => $response->status()]);
                return BookStatus::Unsure;
            }

            $content = $response->body();
        } catch (\Throwable $e) {
            Log::warning('book_checker.fetch_failed', ['url' => $url, 'error' => $e->getMessage()]);
            return BookStatus::Unsure;
        }

        foreach ($this->checkers as $checker) {
            if ($checker->supports($url)) {
                return $checker->check($content, $url);
            }
        }

        return BookStatus::Unsure;
    }
}
