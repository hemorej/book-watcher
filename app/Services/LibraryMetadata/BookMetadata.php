<?php

namespace App\Services\LibraryMetadata;

/**
 * Bibliographic data resolved for a single ISBN.
 *
 * `title` is the only guaranteed field — a provider that cannot even name the
 * book returns null instead of a partial DTO. The rest are best-effort and any
 * of them may be null.
 */
readonly class BookMetadata
{
    /** @param list<string> $authors */
    public function __construct(
        public string $title,
        public array $authors = [],
        public ?string $publisher = null,
        public ?int $year = null,
    ) {}

    /** Authors joined for the single `library_books.author` column. */
    public function authorLine(): string
    {
        return implode(', ', $this->authors);
    }

    /**
     * Overlay $other onto gaps in this DTO (keep our title/authors, borrow a
     * missing publisher or year). Used to combine a primary and fallback
     * provider without letting the fallback overwrite good data.
     */
    public function mergeMissing(self $other): self
    {
        return new self(
            title: $this->title,
            authors: $this->authors ?: $other->authors,
            publisher: $this->publisher ?? $other->publisher,
            year: $this->year ?? $other->year,
        );
    }

    public function isComplete(): bool
    {
        return $this->authors !== [] && $this->publisher !== null && $this->year !== null;
    }
}
