{{-- Status menu partial: expects $book (Book model) and $offset (e.g. 'top-[38px]').
     Pass $mobile = true for the card-list variant: 44px touch targets and the
     popover anchored above the buttons (into the card) instead of below. --}}
@php $mobile = $mobile ?? false; @endphp
{{-- `z-50` while open lifts this row's actions above the rows painted after it,
     so the popover is never covered by a later row. --}}
<div x-data="{ open: false }" class="flex gap-[2px] {{ $mobile ? '' : 'relative' }}" :class="{ 'z-50': open }">
    <button @click.stop="open = !open"
            type="button"
            title="Set status"
            class="{{ $mobile ? 'w-11 h-11 rounded-[10px]' : 'w-8 h-8 rounded-[8px]' }} inline-flex items-center justify-center bg-transparent border-none cursor-pointer text-[#8A867C] hover:bg-toolbar hover:text-ink transition-colors">
        <svg width="{{ $mobile ? '18' : '16' }}" height="{{ $mobile ? '18' : '16' }}" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 20h4L18.5 9.5a2 2 0 0 0-3-3L5 17v3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
        </svg>
    </button>

    <button type="button"
            title="Remove"
            @click="$dispatch('confirm-action', {
                message: @js('Remove \''.($book->title ?: 'this book').'\' from the watch list?'),
                confirmLabel: 'Remove',
                onConfirm: () => $wire.deleteBook({{ $book->id }}),
            })"
            class="{{ $mobile ? 'w-11 h-11 rounded-[10px]' : 'w-8 h-8 rounded-[8px]' }} inline-flex items-center justify-center bg-transparent border-none cursor-pointer text-[#8A867C] hover:bg-[#F6E7E1] hover:text-[#A23E28] transition-colors">
        <svg width="{{ $mobile ? '18' : '16' }}" height="{{ $mobile ? '18' : '16' }}" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M5 7h14M10 7V5h4v2M6 7l1 13h10l1-13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>

    <div x-show="open"
         @click.outside="open = false"
         x-cloak
         class="absolute {{ $mobile ? 'right-[12px] bottom-[56px]' : 'right-0 '.$offset }} z-30 w-[214px] bg-white border border-line rounded-[12px] shadow-[0_14px_34px_rgba(20,18,12,0.14)] p-[7px]">

        <div class="text-[11px] font-semibold tracking-[0.07em] uppercase text-faint px-[10px] pt-[7px] pb-[5px]">
            Set status manually
        </div>

        <button @click="open = false"
                wire:click="setStatus({{ $book->id }}, 'available')"
                type="button"
                class="w-full flex items-center gap-[10px] px-[10px] {{ $mobile ? 'py-[10px]' : 'py-[8px]' }} bg-transparent border-none rounded-[8px] cursor-pointer text-[14px] font-medium text-[#2C2A25] text-left hover:bg-[#F4F2EB] transition-colors">
            <span class="w-[18px] h-[18px] rounded-full chip-available inline-flex items-center justify-center shrink-0">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m5 12 5 5 9-11" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            Available
        </button>

        <button @click="open = false"
                wire:click="setStatus({{ $book->id }}, 'unavailable')"
                type="button"
                class="w-full flex items-center gap-[10px] px-[10px] {{ $mobile ? 'py-[10px]' : 'py-[8px]' }} bg-transparent border-none rounded-[8px] cursor-pointer text-[14px] font-medium text-[#2C2A25] text-left hover:bg-[#F4F2EB] transition-colors">
            <span class="w-[18px] h-[18px] rounded-full chip-unavailable inline-flex items-center justify-center shrink-0">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18" stroke="#fff" stroke-width="2.6" stroke-linecap="round"/>
                </svg>
            </span>
            Not Available
        </button>

        <button @click="open = false"
                wire:click="setStatus({{ $book->id }}, 'unsure')"
                type="button"
                class="w-full flex items-center gap-[10px] px-[10px] {{ $mobile ? 'py-[10px]' : 'py-[8px]' }} bg-transparent border-none rounded-[8px] cursor-pointer text-[14px] font-medium text-[#2C2A25] text-left hover:bg-[#F4F2EB] transition-colors">
            <span class="w-[18px] h-[18px] rounded-full chip-unsure inline-flex items-center justify-center font-bold text-[12px] shrink-0">?</span>
            Unsure
        </button>

        @if($book->override && ! $mobile)
            <div class="h-px bg-line-soft mx-1 my-[6px]"></div>
            <button @click="open = false"
                    wire:click="clearOverride({{ $book->id }})"
                    type="button"
                    class="w-full flex items-center gap-[10px] px-[10px] py-[8px] bg-transparent border-none rounded-[8px] cursor-pointer text-[13.5px] font-medium text-muted text-left hover:bg-[#F4F2EB] transition-colors">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 4v4h-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Resume auto-check
            </button>
        @endif
    </div>
</div>
