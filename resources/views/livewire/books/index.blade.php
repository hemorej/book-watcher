<?php

use App\Enums\BookStatus;
use App\Jobs\CheckBookAvailability;
use App\Models\Book;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Watch List page (Volt single-file component).
 *
 * Shows every watched book, lets the user add books in bulk via a repeating
 * row form, manually override a status, and trigger a "sweep" that queues a
 * {@see CheckBookAvailability} job per book. The view polls every 5s
 * (`wire:poll.5s`) so statuses refresh as the queued jobs land.
 */
new #[Layout('components.layouts.app', params: ['title' => 'Watch List'])] class extends Component {
    /** Draft rows for the "add books" form; one blank row by default. */
    public array $rows = [['author' => '', 'title' => '', 'url' => '']];

    /** All books, Unavailable/Unsure before Available (enum value order), then by author. */
    #[\Livewire\Attributes\Computed]
    public function books(): \Illuminate\Database\Eloquent\Collection
    {
        return Book::orderBy('status')->orderBy('author')->get();
    }

    /** How many watched books are currently Available. */
    #[\Livewire\Attributes\Computed]
    public function availableCount(): int
    {
        return $this->books->filter(fn ($b) => $b->status === BookStatus::Available)->count();
    }

    /** "2 hours ago" / "never" — most recent last_checked_at across all books. */
    #[\Livewire\Attributes\Computed]
    public function lastSwept(): string
    {
        $latest = $this->books->max('last_checked_at');
        return $latest ? $latest->diffForHumans() : 'never';
    }

    /** Collapse the add form back to a single blank row. */
    public function resetRows(): void
    {
        $this->rows = [['author' => '', 'title' => '', 'url' => '']];
    }

    /** Append one blank row to the add form. */
    public function addRow(): void
    {
        $this->rows[] = ['author' => '', 'title' => '', 'url' => ''];
    }

    /** Remove row $index and reindex so wire:model bindings stay contiguous. */
    public function removeRow(int $index): void
    {
        array_splice($this->rows, $index, 1);
        $this->rows = array_values($this->rows);
    }

    /**
     * Persist the draft rows. Rows with no author and no title are skipped;
     * an invalid URL aborts the whole save with an error; a URL that already
     * exists is silently skipped. New books start as Unsure.
     */
    public function saveBooks(): void
    {
        foreach ($this->rows as $row) {
            $author = trim($row['author'] ?? '');
            $title  = trim($row['title'] ?? '');
            $url    = trim($row['url'] ?? '');

            if (! $author && ! $title) continue;

            if ($url && ! filter_var($url, FILTER_VALIDATE_URL)) {
                $this->addError('rows', "Invalid URL: $url");
                return;
            }

            if ($url && Book::where('url', $url)->exists()) continue;

            Book::create([
                'author' => $author,
                'title'  => $title,
                'url'    => $url,
                'status' => BookStatus::Unsure,
            ]);
        }

        $this->rows = [['author' => '', 'title' => '', 'url' => '']];
        $this->dispatch('close-add-modal');
    }

    /** Delete a book from the watch list. */
    public function deleteBook(int $id): void
    {
        Book::destroy($id);
    }

    /** Queue an availability check for every book (the "sweep"). */
    public function checkAll(): void
    {
        $books = Book::all();
        Log::info('book_availability.sweep_dispatched', ['count' => $books->count()]);
        $books->each(fn (Book $book) => CheckBookAvailability::dispatch($book));
    }

    /**
     * Manually pin a book's status. Sets `override` so the automated checker
     * leaves it alone until {@see clearOverride()} is called.
     */
    public function setStatus(int $id, string $status): void
    {
        $book = Book::findOrFail($id);
        $book->status = BookStatus::from($status);
        $book->override = true;
        $book->last_checked_at = now();
        $book->save();

        Log::info('book_availability.manual_override_set', ['book_id' => $book->id, 'status' => $status]);
    }

    /** Re-enable automated checking for a book (drops the manual pin). */
    public function clearOverride(int $id): void
    {
        $book = Book::findOrFail($id);
        $book->override = false;
        $book->save();

        Log::info('book_availability.manual_override_cleared', ['book_id' => $book->id]);
    }
}; ?>

<div wire:poll.5s
     x-data="{ showAdd: false }"
     @close-add-modal.window="showAdd = false">

    <main class="mx-auto px-4 pt-6 pb-24 md:px-7 md:pt-10 md:pb-20" style="max-width:1060px;">

        {{-- Page header --}}
        <div class="flex flex-col items-stretch gap-[18px] md:flex-row md:items-end md:justify-between md:gap-6 md:flex-wrap mb-[26px]">
            <div>
                <h1 class="font-serif text-[30px] md:text-[38px] font-medium tracking-[-0.02em] text-ink mb-2">Watch List</h1>
                <p class="text-[14.5px] text-muted">
                    {{ $this->books->count() }} titles
                    &middot; {{ $this->availableCount }} available
                    &middot; last swept {{ $this->lastSwept }}
                </p>
            </div>
            <div class="flex items-center gap-[10px] w-full md:w-auto">
                {{-- Check now --}}
                <button wire:click="checkAll"
                        wire:loading.attr="disabled"
                        class="flex-1 md:flex-none inline-flex items-center justify-center gap-2 px-4 py-[10px] bg-white text-text-soft border border-line-strong rounded-[10px] font-semibold text-[14px] cursor-pointer transition-colors hover:bg-[#F6F4EE] disabled:opacity-60">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 12a9 9 0 0 1-15 6.7L3 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 4v4h-4M3 20v-4h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span wire:loading.remove wire:target="checkAll">Check now</span>
                    <span wire:loading wire:target="checkAll">Dispatching…</span>
                </button>

                {{-- Add book --}}
                <button @click="showAdd = true; $wire.resetRows()"
                        class="flex-1 md:flex-none inline-flex items-center justify-center gap-2 px-4 py-[10px] bg-ink text-ink-cream border-none rounded-[10px] font-semibold text-[14px] cursor-pointer transition-colors hover:bg-ink-hover">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    Add book
                </button>
            </div>
        </div>

        {{-- Empty state --}}
        @if($this->books->isEmpty())
            <div class="text-center py-20 border border-dashed border-[#DEDACE] rounded-[16px] bg-white">
                <p class="font-serif italic text-[21px] text-muted mb-1.5">Your watch list is empty.</p>
                <p class="text-[14px] text-faint">Add a title and we'll start watching for it.</p>
            </div>

        @else

        {{-- ===== LEDGER (desktop) ===== --}}
        {{-- No `overflow-hidden` here: it would clip an open status popover. The
             header and last row round their own outer corners instead. --}}
        <div class="hidden md:block border border-line rounded-[14px] bg-white">
            {{-- Header row --}}
            <div class="grid gap-0 px-[22px] py-[14px] bg-[#FAF9F5] border-b border-line rounded-t-[14px] text-[11.5px] tracking-[0.06em] uppercase text-[#A29E94] font-semibold"
                 style="grid-template-columns:1.1fr 1.4fr 150px 150px 84px;">
                <span>Author</span>
                <span>Title</span>
                <span>Status</span>
                <span>Last checked</span>
                <span></span>
            </div>

            @foreach($this->books as $book)
                @php $s = $book->status->value === 'available' ? 'available' : ($book->status->value === 'unavailable' ? 'unavailable' : 'unsure'); @endphp
                <div class="grid gap-0 items-center px-[22px] py-[16px] border-b border-line-soft last:border-b-0 last:rounded-b-[14px] hover:bg-[#FBFAF6] transition-colors"
                     style="grid-template-columns:1.1fr 1.4fr 150px 150px 84px;">
                    <span class="text-[14.5px] text-[#56524A]">{{ $book->author ?: '—' }}</span>
                    <a href="{{ $book->url }}" target="_blank" rel="noopener"
                       class="font-serif text-[18px] text-ink no-underline hover:underline underline-offset-[3px]">
                        {{ $book->title ?: 'Untitled' }}
                    </a>
                    <span>
                        <span class="status-badge badge-{{ $s }}">
                            <span class="status-dot dot-{{ $s }}"></span>
                            {{ $book->status->label() }}
                        </span>
                    </span>
                    <span class="text-[13px] text-[#9B978D]">
                        {{ $book->last_checked_at?->diffForHumans() ?? 'Never' }}
                    </span>
                    <div class="flex justify-end">
                        @include('livewire.books._status-menu', ['book' => $book, 'offset' => 'top-[38px]'])
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ===== CARD LIST (mobile) ===== --}}
        <div class="md:hidden flex flex-col gap-[10px]">
            @foreach($this->books as $book)
                @php $s = $book->status->value === 'available' ? 'available' : ($book->status->value === 'unavailable' ? 'unavailable' : 'unsure'); @endphp
                <div class="relative bg-white border border-line rounded-[14px]" style="padding:14px 15px 11px;">
                    <div class="text-[11.5px] font-semibold tracking-[0.05em] uppercase text-[#A29E94] mb-[5px]">
                        {{ $book->author ?: '—' }}
                    </div>
                    <a href="{{ $book->url }}" target="_blank" rel="noopener"
                       class="block font-serif text-[21px] leading-[1.22] text-ink no-underline mb-[12px]">
                        {{ $book->title ?: 'Untitled' }}
                    </a>
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col items-start gap-[5px]">
                            <span class="status-badge badge-{{ $s }}">
                                <span class="status-dot dot-{{ $s }}"></span>
                                {{ $book->status->label() }}
                            </span>
                            <span class="text-[12.5px] text-[#A8A49B]">
                                Checked {{ $book->last_checked_at?->diffForHumans() ?? 'never' }}
                            </span>
                        </div>
                        @include('livewire.books._status-menu', ['book' => $book, 'mobile' => true])
                    </div>
                </div>
            @endforeach
        </div>

        @endif

    </main>

    {{-- ===== ADD BOOK MODAL ===== --}}
    <div x-show="showAdd"
         x-cloak
         class="fixed inset-0 z-50 flex items-end md:items-start justify-center overflow-y-auto p-0 md:py-12 md:px-5"
         style="background:rgba(28,25,18,0.32);backdrop-filter:blur(2px);">
        <div @click="showAdd = false" class="fixed inset-0" aria-hidden="true"></div>

        <div class="relative w-full max-w-[560px] bg-white rounded-t-[20px] md:rounded-[18px] shadow-[0_24px_60px_rgba(20,18,12,0.26)] overflow-y-auto max-h-[92vh] md:max-h-none pt-[22px] px-[18px] pb-[calc(26px+env(safe-area-inset-bottom))] md:pt-7 md:px-[30px] md:pb-[26px]">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-1">
                <h2 class="font-serif text-[26px] font-medium tracking-[-0.01em] text-ink">Add to your watch list</h2>
                <button @click="showAdd = false" type="button"
                        class="w-[34px] h-[34px] inline-flex items-center justify-center bg-transparent border-none rounded-[9px] cursor-pointer text-[#9B978D] hover:bg-toolbar hover:text-ink transition-colors -mt-1 -mr-[6px]">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <p class="text-[14px] text-muted mb-[22px]">
                We'll watch each source and tell you the moment it's available. Fill in what you know.
            </p>

            {{-- Book rows --}}
            <div class="flex flex-col gap-4">
                @foreach($rows as $index => $row)
                    <div class="relative border border-line rounded-[14px] p-[18px] bg-card-alt">
                        @if(count($rows) > 1)
                            <button wire:click="removeRow({{ $index }})" type="button"
                                    class="absolute top-3 right-3 w-[28px] h-[28px] inline-flex items-center justify-center bg-transparent border-none rounded-[7px] cursor-pointer text-faint hover:bg-toolbar hover:text-[#A23E28] transition-colors">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            </button>
                        @endif

                        <div class="text-[11px] font-semibold tracking-[0.07em] uppercase text-[#B0ACA2] mb-[14px]">
                            Book {{ $index + 1 }}
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-[14px]">
                            <div>
                                <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Author</label>
                                <input wire:model.blur="rows.{{ $index }}.author"
                                       type="text"
                                       placeholder="Ansel Adams"
                                       class="imprint-input-sm" />
                            </div>
                            <div>
                                <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Title</label>
                                <input wire:model.blur="rows.{{ $index }}.title"
                                       type="text"
                                       placeholder="The Negative"
                                       class="imprint-input-sm" />
                            </div>
                        </div>

                        <div class="mt-[14px]">
                            <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Source URL</label>
                            <input wire:model.blur="rows.{{ $index }}.url"
                                   type="url"
                                   placeholder="https://steidl.de/Books/The-Negative"
                                   class="imprint-input-sm" />
                            <p class="text-[12px] text-[#B0ACA2] mt-[7px]">The page we'll check for availability.</p>
                        </div>
                    </div>
                @endforeach
            </div>

            @error('rows')
                <p class="text-[13px] text-[#A23E28] mt-3">{{ $message }}</p>
            @enderror

            {{-- Add another title --}}
            <button wire:click="addRow" type="button"
                    class="mt-[14px] inline-flex items-center gap-[7px] bg-transparent border border-dashed border-[#D7D3C8] rounded-[10px] px-[14px] py-[10px] font-semibold text-[13.5px] text-[#56524A] cursor-pointer transition-colors hover:bg-[#F6F4EE] hover:border-[#C6C1B4]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Add another title
            </button>

            {{-- Footer --}}
            <div class="flex flex-col-reverse items-stretch md:flex-row md:items-center md:justify-end gap-[10px] mt-[26px] pt-5 border-t border-line-soft">
                <button @click="showAdd = false" type="button"
                        class="px-[18px] py-[11px] bg-transparent border-none font-semibold text-[14px] text-muted cursor-pointer rounded-[10px] hover:bg-toolbar hover:text-ink transition-colors">
                    Cancel
                </button>
                <button wire:click="saveBooks" type="button"
                        class="px-[22px] py-[11px] bg-ink text-ink-cream border-none rounded-[10px] font-semibold text-[14px] cursor-pointer transition-colors hover:bg-ink-hover">
                    Add to list
                </button>
            </div>
        </div>
    </div>

    <x-confirm-modal />

</div>
