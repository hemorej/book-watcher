<?php

use App\Enums\BookStatus;
use App\Jobs\CheckBookAvailability;
use App\Models\Book;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', params: ['title' => 'Watch List'])] class extends Component {
    public string $urls = '';

    #[\Livewire\Attributes\Computed]
    public function books(): \Illuminate\Database\Eloquent\Collection
    {
        return Book::orderBy('status')->orderBy('author')->get();
    }

    public function addBooks(): void
    {
        $this->validate(['urls' => 'required|string']);

        $lines = preg_split('/\r\n|\r|\n/', trim($this->urls));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = explode(':', $line, 3);

            if (count($parts) < 3) {
                $this->addError('urls', 'Each line must be: author:title:url');
                return;
            }

            [$author, $title, $url] = array_map('trim', $parts);

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                $this->addError('urls', "Invalid URL: $url");
                return;
            }

            if (Book::where('url', $url)->exists()) {
                continue;
            }

            Book::create(compact('author', 'title', 'url'));
        }

        $this->urls = '';
        $this->modal('add-book')->close();
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
        $book->save();
    }

    public function clearOverride(int $id): void
    {
        $book = Book::findOrFail($id);
        $book->override = false;
        $book->save();
    }
}; ?>

<div wire:poll.5s>
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <flux:heading size="xl">Watch List</flux:heading>
            <div class="flex items-center gap-3">
                <flux:button wire:click="checkAll" wire:loading.attr="disabled" icon="arrow-path" variant="filled">
                    <span wire:loading.remove wire:target="checkAll">Check Now</span>
                    <span wire:loading wire:target="checkAll">Dispatching…</span>
                </flux:button>

                <flux:modal.trigger name="add-book">
                    <flux:button icon="plus" variant="primary">Add Book</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        {{-- Book list --}}
        @if($this->books->isEmpty())
            <flux:text>No books on the watch list yet.</flux:text>
        @else
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900 text-left text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">Author</th>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Last checked</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->books as $book)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                                <td class="px-4 py-3">
                                    <a href="{{ $book->url }}" target="_blank"
                                       class="hover:underline text-zinc-800 dark:text-zinc-200">
                                        {{ $book->author ?: '—' }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ $book->url }}" target="_blank"
                                       class="hover:underline text-zinc-800 dark:text-zinc-200">
                                        {{ $book->title ?: '—' }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <flux:badge color="{{ $book->status->color() }}" size="sm">
                                            {{ $book->status->label() }}
                                        </flux:badge>
                                        @if($book->override)
                                            <flux:badge color="purple" size="sm" title="Manually overridden">manual</flux:badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-xs">
                                    {{ $book->last_checked_at?->diffForHumans() ?? 'Never' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        {{-- Manual override dropdown --}}
                                        <flux:dropdown>
                                            <flux:button size="sm" variant="ghost" icon="pencil-square" title="Override status" />
                                            <flux:menu>
                                                <flux:menu.group heading="Set status manually">
                                                    <flux:menu.item
                                                        wire:click="setStatus({{ $book->id }}, 'available')"
                                                        icon="check-circle"
                                                    >Available</flux:menu.item>
                                                    <flux:menu.item
                                                        wire:click="setStatus({{ $book->id }}, 'unavailable')"
                                                        icon="x-circle"
                                                    >Not Available</flux:menu.item>
                                                    <flux:menu.item
                                                        wire:click="setStatus({{ $book->id }}, 'unsure')"
                                                        icon="question-mark-circle"
                                                    >Unsure</flux:menu.item>
                                                </flux:menu.group>
                                                @if($book->override)
                                                    <flux:menu.separator />
                                                    <flux:menu.item
                                                        wire:click="clearOverride({{ $book->id }})"
                                                        icon="arrow-path"
                                                    >Resume auto-check</flux:menu.item>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>

                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="trash"
                                            wire:click="deleteBook({{ $book->id }})"
                                            wire:confirm="Remove '{{ addslashes($book->title) }}' from the watch list?"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Add book modal --}}
    <flux:modal name="add-book" class="md:w-xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add Books</flux:heading>
                <flux:text class="mt-1">One entry per line — format: <code class="font-mono">author:title:url</code></flux:text>
            </div>

            <flux:textarea
                wire:model="urls"
                placeholder="Ansel Adams:The Negative:https://steidl.de/Books/..."
                rows="8"
                autofocus
            />

            @error('urls')
                <flux:text class="text-red-500">{{ $message }}</flux:text>
            @enderror

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="addBooks" variant="primary">Save</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
