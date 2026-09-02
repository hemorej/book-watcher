<?php

namespace App\Models;

use Database\Factories\LibraryBookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A book the user owns, catalogued by ISBN.
 *
 * Distinct from {@see Book} (the availability watch list): this table carries no
 * status/checker columns, just bibliographic data ingested from ISBN lookups via
 * `php artisan library:ingest`.
 *
 * @property int $id
 * @property string|null $isbn Normalised 13-digit ISBN; unique. Null for a hand-entered volume.
 * @property string $title
 * @property string $author One or more authors, comma-separated.
 * @property string|null $publisher
 * @property int|null $year Year of publication.
 * @property string|null $edition Free-text edition note ("First printing", "Revised edition, cloth").
 * @property string|null $condition Free-text condition grade ("Fine", "Very good").
 * @property Carbon|null $acquired_at When the volume entered the collection.
 */
class LibraryBook extends Model
{
    /** @use HasFactory<LibraryBookFactory> */
    use HasFactory;

    protected $fillable = ['isbn', 'title', 'author', 'publisher', 'year', 'edition', 'condition', 'acquired_at'];

    protected $casts = [
        'year' => 'integer',
        'acquired_at' => 'datetime',
    ];

    protected $attributes = [
        'author' => '',
    ];
}
