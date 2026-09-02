<?php

namespace App\Services\LibraryMetadata;

use App\Providers\AppServiceProvider;

/**
 * Resolves an ISBN to {@see BookMetadata} by consulting each registered
 * {@see MetadataProvider} in turn.
 *
 * The first provider to name the book wins the title/authors; later providers
 * are still queried to backfill a missing publisher or year, and querying stops
 * as soon as every field is filled. Returns null only when no provider has a
 * record at all.
 *
 * Wired as a singleton in {@see AppServiceProvider::register()};
 * order there is significant (Open Library first, Google Books as fallback).
 */
class LibraryMetadataResolver
{
    /** @param list<MetadataProvider> $providers */
    public function __construct(private readonly array $providers) {}

    public function resolve(string $isbn): ?BookMetadata
    {
        $resolved = null;

        foreach ($this->providers as $provider) {
            $found = $provider->lookup($isbn);
            if ($found === null) {
                continue;
            }

            $resolved = $resolved === null ? $found : $resolved->mergeMissing($found);

            if ($resolved->isComplete()) {
                break;
            }
        }

        return $resolved;
    }
}
