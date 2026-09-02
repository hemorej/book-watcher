<?php

namespace App\Services\LibraryMetadata;

/**
 * A single bibliographic data source keyed by ISBN.
 *
 * Implementations are tried in order by {@see LibraryMetadataResolver}; each is
 * expected to swallow its own network/parse errors and return null rather than
 * throw, so one dead source never aborts an ingest run.
 */
interface MetadataProvider
{
    /** Short identifier used in log lines, e.g. "openlibrary". */
    public function name(): string;

    /** Look up a normalised 13-digit ISBN, or null when the source has no match. */
    public function lookup(string $isbn): ?BookMetadata;
}
