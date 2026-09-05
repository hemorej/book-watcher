<?php

namespace App\Jobs;

use App\Enums\BookStatus;
use App\Mail\BookAvailable;
use App\Models\Book;
use App\Services\BookChecker\BookCheckerService;
use App\Services\SecondarySource\SecondarySourceResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Re-checks one book's availability and, on an Unavailable→Available
 * transition, emails the configured recipient.
 *
 * Dispatched one-per-book by the "sweep" action on the books list
 * (resources/views/livewire/books/index.blade.php). Queued; retried up to
 * 3 times. Books with `override` set are skipped entirely.
 */
class CheckBookAvailability implements ShouldQueue
{
    use Queueable;

    /** Max queue attempts before the job is marked failed. */
    public int $tries = 3;

    public function __construct(public readonly Book $book) {}

    /**
     * Fetch and parse the book's page, persist the new status/timestamp, log
     * any change, and send the "now available" email on a first transition
     * into Available (when app.notification_recipient is configured).
     *
     * When the publisher page itself isn't Available, falls back to
     * {@see SecondarySourceResolver}: a confident, in-stock match there
     * upgrades the status to Available; anything weaker is still recorded as
     * a `found_at_*` link for the user to check by hand, without changing
     * the status.
     */
    public function handle(BookCheckerService $checker, SecondarySourceResolver $secondarySources): void
    {
        // Books with override set were manually assigned a status — skip the check
        if ($this->book->override) {
            Log::debug('book_availability.check_skipped', ['book_id' => $this->book->id, 'reason' => 'override']);

            return;
        }

        $previousStatus = $this->book->status;
        $newStatus = $checker->check($this->book->url);

        $foundAtSource = null;
        $foundAtUrl = null;

        if ($newStatus !== BookStatus::Available) {
            $match = $secondarySources->resolve($this->book->title, $this->book->author);

            if ($match !== null) {
                $foundAtSource = $match->source;
                $foundAtUrl = $match->url;

                if ($match->isConfident() && $match->status === BookStatus::Available) {
                    $newStatus = BookStatus::Available;
                }
            }
        }

        $this->book->status = $newStatus;
        $this->book->found_at_source = $foundAtSource;
        $this->book->found_at_url = $foundAtUrl;
        $this->book->last_checked_at = now();
        $this->book->save();

        if ($newStatus !== $previousStatus) {
            Log::info('book_availability.status_changed', [
                'book_id' => $this->book->id,
                'title' => $this->book->title,
                'from' => $previousStatus->value,
                'to' => $newStatus->value,
            ]);
        }

        $becameAvailable = $previousStatus !== BookStatus::Available
            && $newStatus === BookStatus::Available;

        // notification_recipient is set via the NOTIFICATION_RECIPIENT env variable
        if ($becameAvailable && $recipient = config('app.notification_recipient')) {
            Mail::to($recipient)->send(new BookAvailable($this->book));
            Log::info('book_availability.notification_sent', ['book_id' => $this->book->id, 'recipient' => $recipient]);
        }
    }

    /** Called by the queue after the final retry fails; logs and swallows. */
    public function failed(\Throwable $e): void
    {
        Log::error('book_availability.check_failed', [
            'book_id' => $this->book->id,
            'url' => $this->book->url,
            'error' => $e->getMessage(),
        ]);
    }
}
