<?php

namespace App\Services\SecondarySource;

/**
 * A bookseller/search-engine catalog checked as a fallback when a book's own
 * publisher page reports it Unavailable or Unsure.
 *
 * Implementations are tried in order by {@see SecondarySourceResolver}; each
 * is expected to swallow its own network/parse errors and return null rather
 * than throw, so one dead source never aborts a check.
 */
interface SecondarySource
{
    /** Display name used in the UI link and in log lines, e.g. "CCA Bookstore". */
    public function name(): string;

    /**
     * Search the catalog for $title/$author and return the single
     * best-matching candidate, or null when nothing plausibly matches.
     */
    public function search(string $title, string $author): ?SecondarySourceMatch;
}
