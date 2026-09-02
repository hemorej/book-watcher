<?php

namespace App\Services\LibraryMetadata;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Books API volume search (https://developers.google.com/books/docs/v1/using).
 *
 * Free; an API key (config `services.google_books.key`) is optional and only
 * raises the rate limit. Used as the fallback when Open Library has no record
 * or leaves gaps.
 */
class GoogleBooksProvider implements MetadataProvider
{
    private const ENDPOINT = 'https://www.googleapis.com/books/v1/volumes';

    public function __construct(private readonly ?string $apiKey = null) {}

    public function name(): string
    {
        return 'googlebooks';
    }

    public function lookup(string $isbn): ?BookMetadata
    {
        $query = ['q' => "isbn:{$isbn}"];
        if ($this->apiKey) {
            $query['key'] = $this->apiKey;
        }

        try {
            $response = Http::acceptJson()->timeout(15)->get(self::ENDPOINT, $query);
        } catch (\Throwable $e) {
            Log::warning('library_metadata.googlebooks.request_failed', ['isbn' => $isbn, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('library_metadata.googlebooks.non_200', ['isbn' => $isbn, 'status' => $response->status()]);

            return null;
        }

        $info = $response->json('items.0.volumeInfo');

        if (! is_array($info) || ! isset($info['title'])) {
            return null;
        }

        return new BookMetadata(
            title: trim($info['title']),
            authors: array_values(array_filter(array_map('trim', $info['authors'] ?? []))),
            publisher: isset($info['publisher']) ? trim($info['publisher']) : null,
            year: self::extractYear($info['publishedDate'] ?? null),
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
