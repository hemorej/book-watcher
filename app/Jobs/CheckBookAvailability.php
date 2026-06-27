<?php

namespace App\Jobs;

use App\Enums\BookStatus;
use App\Mail\BookAvailable;
use App\Models\Book;
use App\Services\BookChecker\BookCheckerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
            return;
        }

        $previousStatus = $this->book->status;
        $newStatus = $checker->check($this->book->url);

        $this->book->status = $newStatus;
        $this->book->last_checked_at = now();
        $this->book->save();

        $becameAvailable = $previousStatus !== BookStatus::Available
            && $newStatus === BookStatus::Available;

        if ($becameAvailable && $recipient = config('app.notification_recipient')) {
            Mail::to($recipient)->send(new BookAvailable($this->book));
        }
    }
}
