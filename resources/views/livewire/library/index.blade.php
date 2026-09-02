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
    public array $vol = ['author' => '', 'title' => '', 'publisher' => '', 'year' => ''];

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
        $latest = LibraryBook::max('acquired_at') ?? LibraryBook::max('created_at');

        return $latest ? Carbon::parse($latest)->format('M Y') : '—';
    }

    /** Reset the modal fields to blank. */
    public function resetVol(): void
    {
        $this->vol = ['author' => '', 'title' => '', 'publisher' => '', 'year' => ''];
    }

    /**
     * Persist the drafted volume. A submit with neither author nor title is
     * ignored. Missing publisher/year fall back to placeholder values; a new
     * record is stamped as acquired now.
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

        $year = trim((string) ($this->vol['year'] ?? ''));

        LibraryBook::create([
            'author' => $author,
            'title' => $title ?: 'Untitled',
            'publisher' => trim($this->vol['publisher'] ?? '') ?: 'Unknown publisher',
            'year' => ctype_digit($year) ? (int) $year : null,
            'edition' => 'Edition not recorded',
            'condition' => 'Unrecorded',
            'acquired_at' => now(),
        ]);

        $this->resetVol();
        $this->dispatch('close-add-volume-modal');
    }

    /** Id of the row currently open for inline editing, or null. */
    public ?int $editingId = null;

    /** Working copy of the row being edited. */
    public array $editRow = ['author' => '', 'title' => '', 'publisher' => '', 'year' => ''];

    /** Open a row for inline editing, seeded with its current values. */
    public function startEdit(int $id): void
    {
        $volume = LibraryBook::find($id);

        if (! $volume) {
            return;
        }

        $this->resetErrorBag('editRow');
        $this->editingId = $id;
        $this->editRow = [
            'author' => (string) $volume->author,
            'title' => (string) $volume->title,
            'publisher' => (string) $volume->publisher,
            'year' => (string) ($volume->year ?? ''),
        ];
    }

    /** Discard the inline edit without saving. */
    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editRow = ['author' => '', 'title' => '', 'publisher' => '', 'year' => ''];
        $this->resetErrorBag('editRow');
    }

    /**
     * Persist the inline edit. Same field rules as {@see saveVolume()}: an
     * author or a title is required; blank publisher/year fall back to their
     * placeholders.
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

        $year = trim((string) ($this->editRow['year'] ?? ''));

        $volume->update([
            'author' => $author,
            'title' => $title ?: 'Untitled',
            'publisher' => trim($this->editRow['publisher'] ?? '') ?: 'Unknown publisher',
            'year' => ctype_digit($year) ? (int) $year : null,
        ]);

        $this->cancelEdit();
    }
}; ?>

<div x-data="{ showAddVolume: false }"
     @close-add-volume-modal.window="showAddVolume = false">

    <main class="mx-auto px-7 pt-10 pb-20" style="max-width:1060px;">

        {{-- Page header --}}
        <div class="flex items-end justify-between gap-6 flex-wrap mb-[26px]">
            <div>
                <h1 class="font-serif text-[38px] font-medium tracking-[-0.02em] text-ink mb-2">Library</h1>
                <p class="text-[14.5px] text-muted">
                    {{ $this->total }} {{ Str::plural('volume', $this->total) }}
                    &middot; {{ $this->publisherCount }} {{ Str::plural('publisher', $this->publisherCount) }}
                    &middot; newest {{ $this->newest }}
                </p>
            </div>
            <button @click="showAddVolume = true; $wire.resetVol()"
                    class="inline-flex items-center gap-2 px-4 py-[10px] bg-ink text-ink-cream border-none rounded-[10px] font-semibold text-[14px] cursor-pointer transition-colors hover:bg-ink-hover">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Add volume
            </button>
        </div>

        {{-- Toolbar: search + sort --}}
        <div class="flex items-center justify-between gap-[14px] flex-wrap mb-[18px]">
            <div class="relative flex-1 min-w-[220px] max-w-[320px]">
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
                       class="w-full py-[10px] pl-9 pr-[13px] border border-line-strong rounded-[10px] bg-white font-sans text-[14px] text-ink focus:outline-none focus:border-ink focus:shadow-[0_0_0_3px_rgba(26,25,22,0.06)]" />
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[12px] tracking-[0.06em] uppercase text-faint font-semibold">Sort</span>
                <div class="inline-flex bg-toolbar border border-[#E7E4DB] rounded-[10px] p-[3px] gap-[2px]">
                    <button wire:click="$set('libSort', 'author')"
                            type="button"
                            @class([
                                'px-[13px] py-[6px] rounded-[7px] font-semibold text-[13px] cursor-pointer transition-colors border-none',
                                'bg-white text-ink shadow-[0_1px_2px_rgba(20,18,12,0.10)]' => $libSort === 'author',
                                'bg-transparent text-[#86837A] hover:text-ink' => $libSort !== 'author',
                            ])>Author</button>
                    <button wire:click="$set('libSort', 'title')"
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

        {{-- ===== LEDGER ===== --}}
        <div class="border border-line rounded-[14px] overflow-hidden bg-white">
            {{-- Header row --}}
            <div class="grid gap-4 px-[22px] py-[14px] bg-[#FAF9F5] border-b border-line text-[11.5px] tracking-[0.06em] uppercase text-[#A29E94] font-semibold"
                 style="grid-template-columns:1fr 1.6fr 1.1fr 80px 48px;">
                <span>Author</span>
                <span>Title</span>
                <span>Publisher</span>
                <span class="text-right">Year</span>
                <span></span>
            </div>

            @foreach($this->books as $volume)
                <div wire:key="volume-{{ $volume->id }}"
                     class="grid gap-4 items-center px-[22px] py-[16px] border-b border-line-soft last:border-b-0 hover:bg-[#FBFAF6] transition-colors"
                     style="grid-template-columns:1fr 1.6fr 1.1fr 80px 48px;">
                    @if($editingId === $volume->id)
                        {{-- Inline edit --}}
                        <input wire:model="editRow.author" type="text" aria-label="Author"
                               class="imprint-input-sm" style="padding:8px 10px;font-size:13.5px;" />
                        <input wire:model="editRow.title" type="text" aria-label="Title"
                               class="imprint-input-sm" style="padding:8px 10px;font-size:13.5px;" />
                        <input wire:model="editRow.publisher" type="text" aria-label="Publisher"
                               class="imprint-input-sm" style="padding:8px 10px;font-size:13.5px;" />
                        <input wire:model="editRow.year" type="text" inputmode="numeric" aria-label="Year"
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
                        <div class="flex justify-end">
                            <button wire:click="startEdit({{ $volume->id }})" type="button" title="Edit"
                                    class="w-8 h-8 inline-flex items-center justify-center bg-transparent border-none rounded-[8px] cursor-pointer text-[#8A867C] hover:bg-toolbar hover:text-ink transition-colors">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 20h4L18.5 9.5a2 2 0 0 0-3-3L5 17v3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @endif

    </main>

    {{-- ===== ADD VOLUME MODAL ===== --}}
    <div x-show="showAddVolume"
         x-cloak
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-16 px-5"
         style="background:rgba(28,25,18,0.32);backdrop-filter:blur(2px);">
        <div @click="showAddVolume = false" class="fixed inset-0" aria-hidden="true"></div>

        <div class="relative w-full bg-white rounded-[18px] shadow-[0_24px_60px_rgba(20,18,12,0.26)]"
             style="max-width:500px;padding:28px 30px 26px;">

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

            <div class="grid grid-cols-2 gap-[14px]">
                <div>
                    <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Author</label>
                    <input wire:model="vol.author" type="text" placeholder="Robert Adams" class="imprint-input-sm" />
                </div>
                <div>
                    <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Title</label>
                    <input wire:model="vol.title" type="text" placeholder="Summer Nights" class="imprint-input-sm" />
                </div>
                <div>
                    <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Publisher</label>
                    <input wire:model="vol.publisher" type="text" placeholder="Steidl" class="imprint-input-sm" />
                </div>
                <div>
                    <label class="block font-semibold text-[12.5px] text-[#46433C] mb-[6px]">Year</label>
                    <input wire:model="vol.year" type="text" inputmode="numeric" placeholder="2009" class="imprint-input-sm" />
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-[10px] mt-[26px] pt-5 border-t border-line-soft">
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

</div>
