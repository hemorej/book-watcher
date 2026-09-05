<?php

namespace App\Models;

use App\Enums\BookStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A book on the watch list.
 *
 * @property int $id
 * @property string $url Publisher product page the checker fetches
 * @property string $title
 * @property string $author
 * @property BookStatus $status
 * @property Carbon|null $last_checked_at
 * @property bool $override When true, status was set manually and the checker skips this book.
 * @property string|null $found_at_source Name of the secondary source that matched this book, if any.
 * @property string|null $found_at_url Link to the secondary-source search result, if any.
 */
class Book extends Model
{
    protected $fillable = [
        'url', 'title', 'author', 'status', 'last_checked_at', 'override', 'found_at_source', 'found_at_url',
    ];

    protected $casts = [
        'status' => BookStatus::class,
        'last_checked_at' => 'datetime',
        'override' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'unavailable',
        'author' => '',
        'title' => '',
        'override' => false,
    ];
}
