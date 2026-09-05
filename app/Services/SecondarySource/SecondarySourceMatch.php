<?php

namespace App\Services\SecondarySource;

use App\Enums\BookStatus;

/**
 * The best-scoring candidate a {@see SecondarySource} found for a title, or
 * null from {@see SecondarySource::search()} when nothing plausibly matched.
 */
readonly class SecondarySourceMatch
{
    /** Below this title-similarity score, {@see SecondarySourceResolver} treats the match as too weak to auto-resolve status but still worth a manual-check link. */
    public const CONFIDENT_THRESHOLD = 85.0;

    public function __construct(
        public string $source,
        public string $url,
        public BookStatus $status,
        public float $confidence,
    ) {}

    /** True when the title match is strong enough to trust this source's stock status without a human double-checking. */
    public function isConfident(): bool
    {
        return $this->confidence >= self::CONFIDENT_THRESHOLD;
    }
}
