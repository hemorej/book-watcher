<?php

namespace App\Jobs;

use App\Enums\BookStatus;
use App\Mail\BookAvailable;
use App\Models\Book;
use App\Services\BookChecker\BookCheckerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckBookAvailability implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly Book $book) {}

    public function handle(BookCheckerService $checker): void
    {
        // Books with override set were manually assigned a status — skip the check
        if ($this->book->override) {
            Log::debug('book_availability.check_skipped', ['book_id' => $this->book->id, 'reason' => 'override']);

            return;
        }

        $previousStatus = $this->book->status;
        $newStatus = $checker->check($this->book->url);

        $this->book->status = $newStatus;
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

    public function failed(\Throwable $e): void
    {
        Log::error('book_availability.check_failed', [
            'book_id' => $this->book->id,
            'url' => $this->book->url,
            'error' => $e->getMessage(),
        ]);
    }
}
