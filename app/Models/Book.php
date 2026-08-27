<?php

namespace App\Models;

use App\Enums\BookStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * A book on the watch list.
 *
 * @property int                             $id
 * @property string                          $url             Publisher product page the checker fetches
 * @property string                          $title
 * @property string                          $author
 * @property \App\Enums\BookStatus           $status
 * @property \Illuminate\Support\Carbon|null $last_checked_at
 * @property bool                            $override        When true, status was set manually and the checker skips this book.
 */
class Book extends Model
{
    protected $fillable = ['url', 'title', 'author', 'status', 'last_checked_at', 'override'];

    protected $casts = [
        'status'          => BookStatus::class,
        'last_checked_at' => 'datetime',
        'override'        => 'boolean',
    ];

    protected $attributes = [
        'status'   => 'unavailable',
        'author'   => '',
        'title'    => '',
        'override' => false,
    ];
}
