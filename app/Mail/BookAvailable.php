<?php

namespace App\Mail;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Your watched book is now available" notification.
 *
 * Sent by {@see \App\Jobs\CheckBookAvailability} when a book first flips to
 * Available. Renders the `emails.book-available` Blade view with `$book` in scope.
 */
class BookAvailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Book $book) {}

    /** Subject line: "<title> is now available". */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->book->title} is now available",
        );
    }

    /** Body view; `$book` is exposed automatically as a public property. */
    public function content(): Content
    {
        return new Content(
            view: 'emails.book-available',
        );
    }
}
