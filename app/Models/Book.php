<?php

namespace App\Models;

use App\Enums\BookStatus;
use Illuminate\Database\Eloquent\Model;

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
