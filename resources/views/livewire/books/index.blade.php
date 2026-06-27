<?php

use App\Enums\BookStatus;
use App\Jobs\CheckBookAvailability;
use App\Models\Book;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', params: ['title' => 'Watch List'])] class extends Component {
    public array $rows = [['author' => '', 'title' => '', 'url' => '']];

    #[\Livewire\Attributes\Computed]
    public function books(): \Illuminate\Database\Eloquent\Collection
    {
        return Book::orderBy('status')->orderBy('author')->get();
    }

    #[\Livewire\Attributes\Computed]
    public function availableCount(): int
    {
        return $this->books->filter(fn ($b) => $b->status === BookStatus::Available)->count();
    }

    #[\Livewire\Attributes\Computed]
    public function lastSwept(): string
    {
        $latest = $this->books->max('last_checked_at');
        return $latest ? $latest->diffForHumans() : 'never';
    }

    public function resetRows(): void
    {
        $this->rows = [['author' => '', 'title' => '', 'url' => '']];
    }

    public function addRow(): void
    {
        $this->rows[] = ['author' => '', 'title' => '', 'url' => ''];
    }

    public function removeRow(int $index): void
    {
        array_splice($this->rows, $index, 1);
        $this->rows = array_values($this->rows);
    }

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

    public function deleteBook(int $id): void
    {
        Book::destroy($id);
    }

    public function checkAll(): void
    {
        Book::all()->each(fn (Book $book) => CheckBookAvailability::dispatch($book));
    }

    public function setStatus(int $id, string $status): void
    {
        $book = Book::findOrFail($id);
        $book->status = BookStatus::from($status);
        $book->override = true;
        $book->last_checked_at = now();
        $book->save();
    }

    public function clearOverride(int $id): void
    {
        $book = Book::findOrFail($id);
        $book->override = false;
        $book->save();
    }
}; ?>

<div wire:poll.5s
     x-data="{
         layout: localStorage.getItem('imprint-layout') || 'shelf',
         setLayout(v) { this.layout = v; localStorage.setItem('imprint-layout', v); },
         showAdd: false,
     }"
     @close-add-modal.window="showAdd = false">

    <main class="mx-auto px-7 pt-10 pb-20" style="max-width:1060px;">

        {{-- Page header --}}
        <div class="flex items-end justify-between gap-6 flex-wrap mb-[26px]">
            <div>
                <h1 class="font-serif text-[38px] font-medium tracking-[-0.02em] text-ink mb-2">Watch List</h1>
                <p class="text-[14.5px] text-muted">
                    {{ $this->books->count() }} titles
                    &middot; {{ $this->availableCount }} available
                    &middot; last swept {{ $this->lastSwept }}
                </p>
            </div>
            <div class="flex items-center gap-[10px]">
                {{-- Check now --}}
                <button wire:click="checkAll"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-4 py-[10px] bg-white text-text-soft border border-line-strong rounded-[10px] font-semibold text-[14px] cursor-pointer transition-colors hover:bg-[#F6F4EE] disabled:opacity-60">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 12a9 9 0 0 1-15 6.7L3 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 4v4h-4M3 20v-4h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span wire:loading.remove wire:target="checkAll">Check now</span>
                    <span wire:loading wire:target="checkAll">Dispatching…</span>
                </button>

                {{-- Add book --}}
                <button @click="showAdd = true; $wire.resetRows()"
                        class="inline-flex items-center gap-2 px-4 py-[10px] bg-ink text-ink-cream border-none rounded-[10px] font-semibold text-[14px] cursor-pointer transition-colors hover:bg-ink-hover">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    Add book
                </button>
            </div>
        </div>

        {{-- View switcher --}}
        <div class="flex items-center justify-end gap-3 mb-[18px]">
            <span class="text-[12px] tracking-[0.06em] uppercase text-faint font-semibold">View</span>
            <div class="inline-flex bg-toolbar border border-[#E7E4DB] rounded-[10px] p-[3px] gap-[2px]">
                <button @click="setLayout('ledger')"
                        :class="layout === 'ledger' ? 'bg-white text-ink shadow-[0_1px_2px_rgba(20,18,12,0.10)]' : 'bg-transparent text-[#86837A]'"
                        class="inline-flex items-center px-[13px] py-[6px] rounded-[7px] font-semibold text-[13px] border-none cursor-pointer transition-all">
                    Ledger
                </button>
                <button @click="setLayout('shelf')"
                        :class="layout === 'shelf' ? 'bg-white text-ink shadow-[0_1px_2px_rgba(20,18,12,0.10)]' : 'bg-transparent text-[#86837A]'"
                        class="inline-flex items-center px-[13px] py-[6px] rounded-[7px] font-semibold text-[13px] border-none cursor-pointer transition-all">
                    Shelf
                </button>
                <button @click="setLayout('index')"
                        :class="layout === 'index' ? 'bg-white text-ink shadow-[0_1px_2px_rgba(20,18,12,0.10)]' : 'bg-transparent text-[#86837A]'"
                        class="inline-flex items-center px-[13px] py-[6px] rounded-[7px] font-semibold text-[13px] border-none cursor-pointer transition-all">
                    Index
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

        {{-- ===== LEDGER ===== --}}
        <div x-show="layout === 'ledger'" x-cloak>
            <div class="border border-line rounded-[14px] overflow-hidden bg-white">
                {{-- Header row --}}
                <div class="grid gap-0 px-[22px] py-[14px] bg-[#FAF9F5] border-b border-line text-[11.5px] tracking-[0.06em] uppercase text-[#A29E94] font-semibold"
                     style="grid-template-columns:1.1fr 1.4fr 150px 150px 84px;">
                    <span>Author</span>
                    <span>Title</span>
                    <span>Status</span>
                    <span>Last checked</span>
                    <span></span>
                </div>

                @foreach($this->books as $book)
                    @php $s = $book->status->value === 'available' ? 'available' : ($book->status->value === 'unavailable' ? 'unavailable' : 'unsure'); @endphp
                    <div class="grid gap-0 items-center px-[22px] py-[16px] border-b border-line-soft last:border-b-0 hover:bg-[#FBFAF6] transition-colors"
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
        </div>

        {{-- ===== SHELF ===== --}}
        <div x-show="layout === 'shelf'" x-cloak class="flex flex-col gap-3">
            @foreach($this->books as $book)
                @php $s = $book->status->value === 'available' ? 'available' : ($book->status->value === 'unavailable' ? 'unavailable' : 'unsure'); @endphp
                <div class="flex items-stretch bg-white border border-line rounded-[14px] overflow-hidden hover:border-[#DED9CC] transition-colors">
                    <span class="w-[5px] shrink-0 spine-{{ $s }}"></span>
                    <div class="flex-1 flex items-center justify-between gap-5 px-[22px] py-[18px]">
                        <div class="min-w-0">
                            <div class="text-[11px] tracking-[0.08em] uppercase text-faint font-semibold mb-[5px]">
                                {{ $book->author ?: '—' }}
                            </div>
                            <a href="{{ $book->url }}" target="_blank" rel="noopener"
                               class="font-serif text-[22px] leading-[1.2] text-ink no-underline hover:underline underline-offset-[3px]">
                                {{ $book->title ?: 'Untitled' }}
                            </a>
                        </div>
                        <div class="flex items-center gap-[18px] shrink-0">
                            <span class="status-badge status-badge-lg badge-{{ $s }}">
                                <span class="status-dot dot-{{ $s }}"></span>
                                {{ $book->status->label() }}
                            </span>
                            <span class="text-[12.5px] text-faint w-[84px] text-right">
                                {{ $book->last_checked_at?->diffForHumans() ?? 'Never' }}
                            </span>
                            @include('livewire.books._status-menu', ['book' => $book, 'offset' => 'top-[38px]'])
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ===== INDEX ===== --}}
        <div x-show="layout === 'index'" x-cloak
             class="grid gap-[14px]"
             style="grid-template-columns:repeat(3,1fr);">
            @foreach($this->books as $book)
                @php $s = $book->status->value === 'available' ? 'available' : ($book->status->value === 'unavailable' ? 'unavailable' : 'unsure'); @endphp
                <div class="relative flex flex-col bg-white border border-line rounded-[14px] p-[18px] min-h-[172px] hover:border-[#DED9CC] hover:shadow-[0_6px_20px_rgba(20,18,12,0.05)] transition-all">
                    <div class="flex items-center justify-between mb-[14px]">
                        <span class="status-badge status-badge-sm badge-{{ $s }}">
                            <span class="status-dot dot-{{ $s }}"></span>
                            {{ $book->status->label() }}
                        </span>
                        @include('livewire.books._status-menu', ['book' => $book, 'offset' => 'top-[34px]'])
                    </div>
                    <a href="{{ $book->url }}" target="_blank" rel="noopener"
                       class="font-serif text-[22px] leading-[1.22] text-ink no-underline hover:underline underline-offset-[3px] mb-[6px]">
                        {{ $book->title ?: 'Untitled' }}
                    </a>
                    <div class="text-[13.5px] text-muted">{{ $book->author ?: '—' }}</div>
                    <div class="mt-auto pt-[14px] flex items-center gap-[7px] text-[12px] text-[#B0ACA2]">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Swept {{ $book->last_checked_at?->diffForHumans() ?? 'never' }}
                    </div>
                </div>
            @endforeach
        </div>

        @endif

    </main>

    {{-- ===== ADD BOOK MODAL ===== --}}
    <div x-show="showAdd"
         x-cloak
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-12 px-5"
         style="background:rgba(28,25,18,0.32);backdrop-filter:blur(2px);">
        <div @click="showAdd = false" class="fixed inset-0" aria-hidden="true"></div>

        <div class="relative w-full bg-white rounded-[18px] shadow-[0_24px_60px_rgba(20,18,12,0.26)] px-[30px] py-7"
             style="max-width:560px;">

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

                        <div class="grid grid-cols-2 gap-[14px]">
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
            <div class="flex items-center justify-end gap-[10px] mt-[26px] pt-5 border-t border-line-soft">
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

</div>
