<?php

namespace App\Services\BookChecker;

use App\Enums\BookStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookCheckerService
{
    /** @param CheckerInterface[] $checkers Ordered list; first match wins. DefaultChecker must be last. */
    public function __construct(private readonly array $checkers) {}

    /** Fetch $url and return its availability. Returns Unsure on HTTP errors or timeout. */
    public function check(string $url): BookStatus
    {
        try {
            $response = Http::withUserAgent(
                'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            )->timeout(15)->get($url);

            if (! $response->successful()) {
                Log::warning("BookChecker: non-200 response for $url", ['status' => $response->status()]);
                return BookStatus::Unsure;
            }

            $content = $response->body();
        } catch (\Throwable $e) {
            Log::warning("BookChecker: failed to fetch $url — {$e->getMessage()}");
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
