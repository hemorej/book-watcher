<?php

namespace App\Models;

use Database\Factories\LibraryBookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A book the user owns, catalogued by ISBN.
 *
 * Distinct from {@see Book} (the availability watch list): this table carries no
 * status/checker columns, just bibliographic data ingested from ISBN lookups via
 * `php artisan library:ingest`.
 *
 * @property int $id
 * @property string $isbn Normalised 13-digit ISBN; unique.
 * @property string $title
 * @property string $author One or more authors, comma-separated.
 * @property string|null $publisher
 * @property int|null $year Year of publication.
 */
class LibraryBook extends Model
{
    /** @use HasFactory<LibraryBookFactory> */
    use HasFactory;

    protected $fillable = ['isbn', 'title', 'author', 'publisher', 'year'];

    protected $casts = [
        'year' => 'integer',
    ];

    protected $attributes = [
        'author' => '',
    ];
}
