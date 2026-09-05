<?php

use App\Models\LibraryBook;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Library page (Volt single-file component).
 *
 * A plain record of books the user owns — nothing here is watched or checked.
 * Volumes are added by hand through the "Add volume" modal (or bulk-loaded via
 * `php artisan library:ingest`). Search and sort run server-side over the whole
 * (small) collection; the counts in the header are always totals, not the
 * filtered view.
 */
new #[Layout('components.layouts.app', params: ['title' => 'Library'])] class extends Component
{
    /** Draft fields for the "Add volume" modal. */
    public array $vol = ['author' => '', 'title' => '', 'publisher' => '', 'year' => '', 'isbn' => '', 'acquired_at' => ''];

    /** How the "acquired at" field is typed and displayed, e.g. "Jan 05 2025". */
    protected const ACQUIRED_AT_FORMAT = 'M d Y';

    /**
     * Validation rules shared by the "Add volume" modal and the inline edit
     * row. $prefix is the Livewire property the fields live under ('vol' or
     * 'editRow').
     */
    protected function volumeRules(string $prefix): array
    {
        return [
            "$prefix.author" => ['nullable', 'string', 'max:255'],
            "$prefix.title" => ['nullable', 'string', 'max:255'],
            "$prefix.publisher" => ['nullable', 'string', 'max:255'],
            "$prefix.year" => ['nullable', 'integer', 'min:1450', 'max:'.(now()->year + 1)],
            "$prefix.isbn" => ['nullable', 'regex:/^[A-Za-z0-9-]+$/', 'max:32'],
            "$prefix.acquired_at" => ['nullable', 'date_format:'.self::ACQUIRED_AT_FORMAT],
        ];
    }

    /** Friendlier messages for the rules above, keyed for the given $prefix. */
    protected function volumeMessages(string $prefix): array
    {
        return [
            "$prefix.year.integer" => 'Enter a valid four-digit year.',
            "$prefix.year.min" => 'Enter a valid four-digit year.',
            "$prefix.year.max" => 'Enter a valid four-digit year.',
            "$prefix.isbn.regex" => 'ISBN may only contain letters, numbers and dashes.',
            "$prefix.acquired_at.date_format" => 'Enter a date like "Jan 05 2025".',
        ];
    }

    /** Client-typed search string; matches author, title or publisher. */
    public string $libQuery = '';

    /** Sort key: 'author' or 'title'. */
    public string $libSort = 'author';

    /** All volumes, filtered by {@see $libQuery} and ordered by {@see $libSort}. */
    #[Computed]
    public function books(): Collection
    {
        $q = trim(mb_strtolower($this->libQuery));
        $sort = in_array($this->libSort, ['author', 'title'], true) ? $this->libSort : 'author';

        return LibraryBook::all()
            ->filter(function (LibraryBook $v) use ($q) {
                if ($q === '') {
                    return true;
                }

                return str_contains(mb_strtolower($v->author.' '.$v->title.' '.$v->publisher), $q);
            })
            ->sortBy(fn (LibraryBook $v) => mb_strtolower((string) $v->{$sort}), SORT_NATURAL)
            ->values();
    }

    /** Total volumes owned (ignores the search filter). */
    #[Computed]
    public function total(): int
    {
        return LibraryBook::count();
    }

    /** Distinct publisher count across the whole collection. */
    #[Computed]
    public function publisherCount(): int
    {
        return LibraryBook::query()
            ->whereNotNull('publisher')
            ->where('publisher', '!=', '')
            ->distinct()
            ->count('publisher');
    }

    /** "Mar 2026" — when the most recently acquired volume entered the collection. */
    #[Computed]
    public function newest(): string
    {
        $latest = LibraryBook::whereNotNull('acquired_at')->max('acquired_at');

        return $latest ? Carbon::parse($latest)->format('M Y') : '—';
    }

    /** Reset the modal fields to blank. */
    public function resetVol(): void
    {
        $this->vol = ['author' => '', 'title' => '', 'publisher' => '', 'year' => '', 'isbn' => '', 'acquired_at' => ''];
        $this->resetErrorBag(['vol', 'vol.*']);
    }

    /**
     * Persist the drafted volume. A submit with neither author nor title is
     * ignored. Missing publisher/year fall back to placeholder values; a new
     * record is stamped as acquired now unless an "acquired at" date was given.
     */
    public function saveVolume(): void
    {
        $author = trim($this->vol['author'] ?? '');
        $title = trim($this->vol['title'] ?? '');

        if (! $author && ! $title) {
            $this->resetVol();
            $this->dispatch('close-add-volume-modal');

            return;
        }

        $this->validate($this->volumeRules('vol'), $this->volumeMessages('vol'));

        $year = trim((string) ($this->vol['year'] ?? ''));
        $isbn = trim($this->vol['isbn'] ?? '');
        $acquiredAt = trim($this->vol['acquired_at'] ?? '');

        LibraryBook::create([
            'author' => $author,
            'title' => $title ?: 'Untitled',
            'publisher' => trim($this->vol['publisher'] ?? '') ?: 'Unknown publisher',
            'year' => ctype_digit($year) ? (int) $year : null,
            'isbn' => $isbn ?: null,
            'edition' => 'Edition not recorded',
            'condition' => 'Unrecorded',
            'acquired_at' => $acquiredAt
                ? Carbon::createFromFormat(self::ACQUIRED_AT_FORMAT, $acquiredAt)
                : now(),
        ]);

        $this->resetVol();
        $this->dispatch('close-add-volume-modal');
    }

    /** Id of the row currently open for inline editing, or null. */
    public ?int $editingId = null;

    /** Working copy of the row being edited. */
    public array $editRow = ['author' => '', 'title' => '', 'publisher' => '', 'year' => '', 'isbn' => '', 'acquired_at' => ''];

    /** Open a row for inline editing, seeded with its current values. */
    public function startEdit(int $id): void
    {
        $volume = LibraryBook::find($id);

        if (! $volume) {
            return;
        }

        $this->resetErrorBag(['editRow', 'editRow.*']);
        $this->editingId = $id;
        $this->editRow = [
            'author' => (string) $volume->author,
            'title' => (string) $volume->title,
            'publisher' => (string) $volume->publisher,
            'year' => (string) ($volume->year ?? ''),
            'isbn' => (string) ($volume->isbn ?? ''),
            'acquired_at' => $volume->acquired_at ? $volume->acquired_at->format(self::ACQUIRED_AT_FORMAT) : '',
        ];
    }

    /** Remove a volume from the library. */
    public function deleteVolume(int $id): void
    {
        LibraryBook::destroy($id);

        if ($this->editingId === $id) {
            $this->cancelEdit();
        }
    }

    /** Discard the inline edit without saving. */
    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editRow = ['author' => '', 'title' => '', 'publisher' => '', 'year' => '', 'isbn' => '', 'acquired_at' => ''];
        $this->resetErrorBag(['editRow', 'editRow.*']);
    }

    /**
     * Persist the inline edit. Same field rules as {@see saveVolume()}: an
     * author or a title is required; blank publisher/year/isbn/acquired_at
     * fall back to their placeholders (acquired_at falls back to null).
     */
    public function saveEdit(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $volume = LibraryBook::find($this->editingId);

        if (! $volume) {
            $this->cancelEdit();

            return;
        }

        $author = trim($this->editRow['author'] ?? '');
        $title = trim($this->editRow['title'] ?? '');

        if (! $author && ! $title) {
            $this->addError('editRow', 'Give the volume an author or a title.');

            return;
        }

        $this->validate($this->volumeRules('editRow'), $this->volumeMessages('editRow'));

        $year = trim((string) ($this->editRow['year'] ?? ''));
        $isbn = trim($this->editRow['isbn'] ?? '');
        $acquiredAt = trim($this->editRow['acquired_at'] ?? '');

        $volume->update([
            'author' => $author,
            'title' => $title ?: 'Untitled',
            'publisher' => trim($this->editRow['publisher'] ?? '') ?: 'Unknown publisher',
            'year' => ctype_digit($year) ? (int) $year : null,
            'isbn' => $isbn ?: null,
            'acquired_at' => $acquiredAt
                ? Carbon::createFromFormat(self::ACQUIRED_AT_FORMAT, $acquiredAt)
                : null,
        ]);

        $this->cancelEdit();
    }
}; ?>

<div x-data="{ showAddVolume: false }"
     x-init="(() => {
        try {
            const s = localStorage.getItem('imprint.library.sort');
            if (s && s !== $wire.libSort) $wire.set('libSort', s);
        } catch (e) {}
     })()"
     @close-add-volume-modal.window="showAddVolume = false">

    <main class="mx-auto px-4 pt-6 pb-24 md:px-7 md:pt-10 md:pb-20" style="max-width:1060px;">

        {{-- Page header --}}
        <div class="flex flex-col items-stretch gap-[18px] md:flex-row md:items-end md:justify-between md:gap-6 md:flex-wrap mb-[26px]">
            <div>
                <h1 class="font-serif text-[30px] md:text-[38px] font-medium tracking-[-0.02em] text-ink mb-2">Library</h1>
                <p class="text-[14.5px] text-muted">
                    {{ $this->total }} {{ Str::plural('volume', $this->total) }}
                    &middot; {{ $this->publisherCount }} {{ Str::plural('publisher', $this->publisherCount) }}
                    &middot; newest {{ $this->newest }}
                </p>
            </div>
            <button @click="showAddVolume = true; $wire.resetVol()"
                    class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-[10px] bg-ink text-ink-cream border-none rounded-[10px] font-semibold text-[14px] cursor-pointer transition-colors hover:bg-ink-hover">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Add volume
            </button>
        </div>

        {{-- Toolbar: search + sort — one row at every width --}}
        <div class="flex items-center justify-between flex-nowrap gap-[8px] md:gap-[14px] mb-[18px]">
            <div class="relative flex-1 min-w-0 md:min-w-[220px] max-w-none md:max-w-[320px]">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#B0ACA2] inline-flex" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.6"/>
                        <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                </span>
                <input wire:model.live="libQuery"
                       type="search"
                       placeholder="Search author, title, publisher"
                       aria-label="Search the library"
                       class="w-full py-[10px] pl-9 pr-[13px] border border-line-strong rounded-[10px] bg-white font-sans text-[16px] text-ink focus:outline-none focus:border-ink focus:shadow-[0_0_0_3px_rgba(26,25,22,0.06)]" />
            </div>
            <div class="flex-none flex items-center gap-3">
                <span class="hidden md:inline text-[12px] tracking-[0.06em] uppercase text-faint font-semibold">Sort</span>
                <div class="inline-flex bg-toolbar border border-[#E7E4DB] rounded-[10px] p-[3px] gap-[2px]">
                    <button wire:click="$set('libSort', 'author')"
                            x-on:click="(() => { try { localStorage.setItem('imprint.library.sort', 'author') } catch (e) {} })()"
                            type="button"
                            @class([
                                'px-[13px] py-[6px] rounded-[7px] font-semibold text-[13px] cursor-pointer transition-colors border-none',
                                'bg-white text-ink shadow-[0_1px_2px_rgba(20,18,12,0.10)]' => $libSort === 'author',
                                'bg-transparent text-[#86837A] hover:text-ink' => $libSort !== 'author',
                            ])>Author</button>
                    <button wire:click="$set('libSort', 'title')"
                            x-on:click="(() => { try { localStorage.setItem('imprint.library.sort', 'title') } catch (e) {} })()"
                            type="button"
                            @class([
                                'px-[13px] py-[6px] rounded-[7px] font-semibold text-[13px] cursor-pointer transition-colors border-none',
                                'bg-white text-ink shadow-[0_1px_2px_rgba(20,18,12,0.10)]' => $libSort === 'title',
                                'bg-transparent text-[#86837A] hover:text-ink' => $libSort !== 'title',
                            ])>Title</button>
                </div>
            </div>
        </div>

        {{-- Empty state (also shown when the search matches nothing) --}}
        @if($this->books->isEmpty())
            <div class="text-center py-20 border border-dashed border-[#DEDACE] rounded-[16px] bg-white">
                <p class="font-serif italic text-[21px] text-muted mb-1.5">Nothing on these shelves yet.</p>
                <p class="text-[14px] text-faint">Add the editions you already own.</p>
            </div>
        @else

        @error('editRow')
            <p class="text-[13px] text-[#A23E28] mb-2">{{ $message }}</p>
        @enderror

        {{-- ===== LEDGER (desktop) ===== --}}
        <div class="hidden md:block border border-line rounded-[14px] overflow-hidden bg-white">
            {{-- Header row --}}
            <div class="grid gap-4 px-[22px] py-[14px] bg-[#FAF9F5] border-b border-line text-[11.5px] tracking-[0.06em] uppercase text-[#A29E94] font-semibold"
                 style="grid-template-columns:1fr 1.6fr 1.1fr 80px 72px;">
                <span>Author</span>
                <span>Title</span>
                <span>Publisher</span>
                <span class="text-right">Year</span>
                <span></span>
            </div>

            @foreach($this->books as $volume)
                <div wire:key="volume-{{ $volume->id }}"
                     class="border-b border-line-soft last:border-b-0 hover:bg-[#FBFAF6] transition-colors">
                    <div class="grid gap-4 items-center px-[22px] py-[16px]"
                         style="grid-template-columns:1fr 1.6fr 1.1fr 80px 72px;">
                        @if($editingId === $volume->id)
                            {{-- Inline edit --}}
                            <input wire:model="editRow.author" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" type="text" aria-label="Author"
                                   class="imprint-input-sm" style="padding:8px 10px;font-size:13.5px;" />
                            <input wire:model="editRow.title" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" type="text" aria-label="Title"
                                   class="imprint-input-sm" style="padding:8px 10px;font-size:13.5px;" />
                            <input wire:model="editRow.publisher" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" type="text" aria-label="Publisher"
                                   class="imprint-input-sm" style="padding:8px 10px;font-size:13.5px;" />
                            <input wire:model="editRow.year" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" type="text" inputmode="numeric" aria-label="Published year"
                                   class="imprint-input-sm" style="padding:8px 10px;font-size:13.5px;text-align:right;" />
                            <div class="flex justify-end gap-[2px]">
                                <button wire:click="saveEdit" type="button" title="Save"
                                        class="w-8 h-8 inline-flex items-center justify-center bg-transparent border-none rounded-[8px] cursor-pointer text-[#8A867C] hover:bg-[#E7F0E9] hover:text-[#2C6B4F] transition-colors">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m5 12 5 5 9-11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <button wire:click="cancelEdit" type="button" title="Cancel"
                                        class="w-8 h-8 inline-flex items-center justify-center bg-transparent border-none rounded-[8px] cursor-pointer text-[#8A867C] hover:bg-toolbar hover:text-ink transition-colors">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>
                        @else
                            <span class="text-[14.5px] text-[#56524A]">{{ $volume->author ?: '—' }}</span>
                            <span class="font-serif text-[18px] leading-[1.25] text-ink">{{ $volume->title ?: 'Untitled' }}</span>
                            <span class="text-[14px] text-muted">{{ $volume->publisher ?: 'Unknown publisher' }}</span>
                            <span class="text-[14px] text-muted text-right" style="font-variant-numeric:tabular-nums;">{{ $volume->year ?: '—' }}</span>
                            <div class="flex justify-end gap-[2px]">
                                <button wire:click="startEdit({{ $volume->id }})" type="button" title="Edit"
                                        class="w-8 h-8 inline-flex items-center justify-center bg-transparent border-none rounded-[8px] cursor-pointer text-[#8A867C] hover:bg-toolbar hover:text-ink transition-colors">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 20h4L18.5 9.5a2 2 0 0 0-3-3L5 17v3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <button type="button" title="Remove"
                                        @click="$dispatch('confirm-action', {
                                            message: @js('Remove \''.($volume->title ?: 'this volume').'\' from the library?'),
                                            confirmLabel: 'Remove',
                                            onConfirm: () => $wire.deleteVolume({{ $volume->id }}),
                                        })"
                                        class="w-8 h-8 inline-flex items-center justify-center bg-transparent border-none rounded-[8px] cursor-pointer text-[#8A867C] hover:bg-[#F6E7E1] hover:text-[#A23E28] transition-colors">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M5 7h14M10 7V5h4v2M6 7l1 13h10l1-13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>

                    @if($editingId === $volume->id)
                        {{-- Inline edit: extra fields, same 2-column layout as the "Add volume" modal --}}
                        <div class="px-[22px] pb-[16px] -mt-1">
                            <div class="grid grid-cols-2 gap-[14px]" style="max-width:420px;">
                                <div>
                                    <label class="block font-semibold text-[11.5px] text-[#46433C] mb-[4px]">ISBN</label>
                                    <input wire:model="editRow.isbn" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" type="text" aria-label="ISBN"
                                           class="imprint-input-sm" style="padding:8px 10px;font-size:13.5px;" />
                                </div>
                                <div>
                                    <label class="block font-semibold text-[11.5px] text-[#46433C] mb-[4px]">Acquired</label>
                                    <input wire:model="editRow.acquired_at" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" type="text" placeholder="Jan 05 2025" aria-label="Acquired at"
                                           class="imprint-input-sm" style="padding:8px 10px;font-size:13.5px;" />
                                </div>
                            </div>
                            @error('editRow.year')
                                <p class="text-[12px] text-[#A23E28] mt-[7px]">{{ $message }}</p>
                            @enderror
                            @error('editRow.isbn')
                                <p class="text-[12px] text-[#A23E28] mt-[7px]">{{ $message }}</p>
                            @enderror
                            @error('editRow.acquired_at')
                                <p class="text-[12px] text-[#A23E28] mt-[7px]">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- ===== CARD LIST (mobile) ===== --}}
        <div class="md:hidden flex flex-col gap-[8px]">
            @foreach($this->books as $volume)
                <div wire:key="volume-card-{{ $volume->id }}" class="bg-white border border-line rounded-[14px]" style="padding:10px 14px 11px;">
                    {{-- Row 1: author · publisher · year --}}
                    <div class="flex items-baseline gap-[7px] mb-[2px] whitespace-nowrap">
                        <span class="text-[11.5px] font-semibold tracking-[0.05em] uppercase text-[#A29E94] shrink-0">{{ $volume->author ?: '—' }}</span>
                        <span class="text-[11.5px] font-normal text-[#CFC9BC] shrink-0">&middot;</span>
                        <span class="text-[11.5px] font-semibold tracking-[0.05em] uppercase text-[#B4B0A6] overflow-hidden text-ellipsis min-w-0">{{ $volume->publisher ?: 'Unknown publisher' }}</span>
                        <span class="text-[11.5px] font-normal text-[#CFC9BC] shrink-0">&middot;</span>
                        <span class="text-[11.5px] font-semibold tracking-[0.05em] uppercase text-[#B4B0A6] shrink-0" style="font-variant-numeric:tabular-nums;">{{ $volume->year ?: '—' }}</span>
                    </div>

                    {{-- Row 2: title --}}
                    <div class="font-serif text-[20px] leading-[1.2] text-ink">
                        {{ $volume->title ?: 'Untitled' }}
                    </div>
                </div>
            @endforeach
        </div>

        @endif

    </main>

    {{-- ===== ADD VOLUME MODAL ===== --}}
    <div x-show="showAddVolume"
         x-cloak
         class="fixed inset-0 z-50 flex items-end md:items-start justify-center overflow-y-auto p-0 md:py-16 md:px-5"
         style="background:rgba(28,25,18,0.32);backdrop-filter:blur(2px);">
        <div @click="showAddVolume = false" class="fixed inset-0" aria-hidden="true"></div>

        <div class="relative w-full max-w-[500px] bg-white rounded-t-[20px] md:rounded-[18px] shadow-[0_24px_60px_rgba(20,18,12,0.26)] overflow-y-auto max-h-[92vh] md:max-h-none pt-[22px] px-[18px] pb-[calc(26px+env(safe-area-inset-bottom))] md:pt-7 md:px-[30px] md:pb-[26px]">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-1">
                <h2 class="font-serif text-[26px] font-medium tracking-[-0.01em] text-ink">Add a volume</h2>
                <button @click="showAddVolume = false" type="button"
                        class="w-[34px] h-[34px] inline-flex items-center justify-center bg-transparent border-none rounded-[9px] cursor-pointer text-[#9B978D] hover:bg-toolbar hover:text-ink transition-colors -mt-1 -mr-[6px]">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <p class="text-[14px] text-muted mb-[22px]">A book you already own. Nothing here is watched.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-[14px]">
                <div>
                    <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Author</label>
                    <input wire:model="vol.author" wire:keydown.enter="saveVolume" @keydown.escape="showAddVolume = false" type="text" placeholder="Robert Adams" class="imprint-input-sm" />
                    @error('vol.author') <p class="text-[12px] text-[#A23E28] mt-[7px]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Title</label>
                    <input wire:model="vol.title" wire:keydown.enter="saveVolume" @keydown.escape="showAddVolume = false" type="text" placeholder="Summer Nights" class="imprint-input-sm" />
                    @error('vol.title') <p class="text-[12px] text-[#A23E28] mt-[7px]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Publisher</label>
                    <input wire:model="vol.publisher" wire:keydown.enter="saveVolume" @keydown.escape="showAddVolume = false" type="text" placeholder="Steidl" class="imprint-input-sm" />
                    @error('vol.publisher') <p class="text-[12px] text-[#A23E28] mt-[7px]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Published</label>
                    <input wire:model="vol.year" wire:keydown.enter="saveVolume" @keydown.escape="showAddVolume = false" type="text" inputmode="numeric" placeholder="2009" class="imprint-input-sm" />
                    @error('vol.year') <p class="text-[12px] text-[#A23E28] mt-[7px]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">ISBN <span class="font-normal text-[#B0ACA2]">(optional)</span></label>
                    <input wire:model="vol.isbn" wire:keydown.enter="saveVolume" @keydown.escape="showAddVolume = false" type="text" placeholder="978-3-86930-163-9" class="imprint-input-sm" />
                    @error('vol.isbn') <p class="text-[12px] text-[#A23E28] mt-[7px]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Acquired <span class="font-normal text-[#B0ACA2]">(optional)</span></label>
                    <input wire:model="vol.acquired_at" wire:keydown.enter="saveVolume" @keydown.escape="showAddVolume = false" type="text" placeholder="Jan 05 2025" class="imprint-input-sm" />
                    @error('vol.acquired_at') <p class="text-[12px] text-[#A23E28] mt-[7px]">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex flex-col-reverse items-stretch md:flex-row md:items-center md:justify-end gap-[10px] mt-[26px] pt-5 border-t border-line-soft">
                <button @click="showAddVolume = false" type="button"
                        class="px-[18px] py-[11px] bg-transparent border-none font-semibold text-[14px] text-muted cursor-pointer rounded-[10px] hover:bg-toolbar hover:text-ink transition-colors">
                    Cancel
                </button>
                <button wire:click="saveVolume" type="button"
                        class="px-[22px] py-[11px] bg-ink text-ink-cream border-none rounded-[10px] font-semibold text-[14px] cursor-pointer transition-colors hover:bg-ink-hover">
                    Add to library
                </button>
            </div>
        </div>
    </div>

    <x-confirm-modal />

</div>
