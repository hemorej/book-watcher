<?php

namespace App\Services\LibraryMetadata;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Open Library Books API (https://openlibrary.org/dev/docs/api/books).
 *
 * Free, no auth. `jscmd=data` returns a normalised record with `authors`,
 * `publishers` and `publish_date`. An unknown ISBN comes back as an empty
 * object.
 */
class OpenLibraryProvider implements MetadataProvider
{
    private const ENDPOINT = 'https://openlibrary.org/api/books';

    public function name(): string
    {
        return 'openlibrary';
    }

    public function lookup(string $isbn): ?BookMetadata
    {
        $key = "ISBN:{$isbn}";

        try {
            $response = Http::acceptJson()->timeout(15)->get(self::ENDPOINT, [
                'bibkeys' => $key,
                'format' => 'json',
                'jscmd' => 'data',
            ]);
        } catch (\Throwable $e) {
            Log::warning('library_metadata.openlibrary.request_failed', ['isbn' => $isbn, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('library_metadata.openlibrary.non_200', ['isbn' => $isbn, 'status' => $response->status()]);

            return null;
        }

        $record = $response->json($key);

        if (! is_array($record) || ! isset($record['title'])) {
            return null;
        }

        return new BookMetadata(
            title: trim($record['title']),
            authors: array_values(array_filter(array_map(
                fn ($author) => is_array($author) ? trim($author['name'] ?? '') : '',
                $record['authors'] ?? [],
            ))),
            publisher: isset($record['publishers'][0]['name'])
                ? trim($record['publishers'][0]['name'])
                : null,
            year: self::extractYear($record['publish_date'] ?? null),
        );
    }

    private static function extractYear(?string $date): ?int
    {
        if ($date !== null && preg_match('/\b(\d{4})\b/', $date, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
