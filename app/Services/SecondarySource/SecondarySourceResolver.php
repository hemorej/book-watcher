<?php

namespace App\Services\SecondarySource;

use App\Enums\BookStatus;
use App\Jobs\CheckBookAvailability;
use App\Providers\AppServiceProvider;

/**
 * Consults each registered {@see SecondarySource} in turn for a title/author,
 * used by {@see CheckBookAvailability} once a book's own publisher
 * page has come back Unavailable or Unsure.
 *
 * A confident, in-stock match short-circuits the search (that source is good
 * enough to trust on its own). Otherwise the highest-scoring match found
 * across all sources is kept and returned so the UI can still offer a link,
 * even though {@see SecondarySourceMatch::isConfident()} will be false and
 * the caller must not use it to flip the book's status.
 *
 * Wired as a singleton in {@see AppServiceProvider::register()}.
 */
class SecondarySourceResolver
{
    /** @param list<SecondarySource> $sources */
    public function __construct(private readonly array $sources) {}

    public function resolve(string $title, string $author): ?SecondarySourceMatch
    {
        $best = null;

        foreach ($this->sources as $source) {
            $match = $source->search($title, $author);

            if ($match === null) {
                continue;
            }

            if ($best === null || $match->confidence > $best->confidence) {
                $best = $match;
            }

            if ($match->isConfident() && $match->status === BookStatus::Available) {
                return $match;
            }
        }

        return $best;
    }
}
