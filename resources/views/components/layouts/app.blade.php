<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-paper antialiased overflow-x-clip" style="-webkit-font-smoothing:antialiased;" x-data="{ scrolled: false }" @scroll.window="scrolled = window.scrollY > 48">

        @php $onLibrary = request()->routeIs('library'); @endphp

        <header class="sticky top-0 z-40 flex items-center justify-between h-16 px-4 md:px-7 border-b border-line"
                style="background:rgba(251,250,247,0.85);backdrop-filter:blur(10px);">
            <a href="{{ route('books') }}" class="flex items-center gap-[11px] no-underline min-w-0" wire:navigate>
                <x-app-logo />
                <span class="text-[15px] font-medium text-muted whitespace-nowrap overflow-hidden transition-all duration-200"
                      x-cloak
                      x-show="scrolled"
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0 -translate-x-1"
                      x-transition:enter-end="opacity-100 translate-x-0"
                      x-transition:leave="transition ease-in duration-150"
                      x-transition:leave-start="opacity-100 translate-x-0"
                      x-transition:leave-end="opacity-0 -translate-x-1">— {{ $title ?? '' }}</span>
            </a>

            {{-- Section switcher (desktop only — moves to the bottom tab bar on mobile) --}}
            <nav class="hidden md:flex items-center gap-1 bg-toolbar border border-[#E7E4DB] rounded-[11px] p-[3px]">
                <a href="{{ route('books') }}" wire:navigate
                   @class([
                       'inline-flex items-center gap-[7px] px-[15px] py-[7px] rounded-[8px] font-semibold text-[13.5px] no-underline transition-colors',
                       'bg-white text-ink shadow-[0_1px_2px_rgba(20,18,12,0.10)]' => ! $onLibrary,
                       'bg-transparent text-[#86837A] hover:text-ink' => $onLibrary,
                   ])>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.6"/>
                        <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.6"/>
                    </svg>
                    Watch
                </a>
                <a href="{{ route('library') }}" wire:navigate
                   @class([
                       'inline-flex items-center gap-[7px] px-[15px] py-[7px] rounded-[8px] font-semibold text-[13.5px] no-underline transition-colors',
                       'bg-white text-ink shadow-[0_1px_2px_rgba(20,18,12,0.10)]' => $onLibrary,
                       'bg-transparent text-[#86837A] hover:text-ink' => ! $onLibrary,
                   ])>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 5h6v14H4zM14 5h6v14h-6" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        <path d="M4 9h6M14 9h6" stroke="currentColor" stroke-width="1.6"/>
                    </svg>
                    Library
                </a>
            </nav>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-[9px] bg-transparent border-none cursor-pointer px-[6px] py-[5px] rounded-[10px] transition-colors hover:bg-[#F1EFE8] min-w-11 min-h-11 md:min-w-0 md:min-h-0 justify-center"
                        title="Sign out">
                    <span class="w-[30px] h-[30px] rounded-full bg-ink text-[#F4F0E6] inline-flex items-center justify-center font-semibold text-[13px] shrink-0">
                        {{ auth()->user()->initials() }}
                    </span>
                    <span class="text-[14px] font-medium text-text-soft hidden md:block">{{ auth()->user()->name }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" class="text-faint hidden md:block" aria-hidden="true">
                        <path d="m7 9 5-5 5 5M7 15l5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>
        </header>

        {{ $slot }}

        {{-- Bottom tab bar (mobile only) — replaces the segmented switcher below md --}}
        <nav class="md:hidden fixed left-0 right-0 bottom-0 z-50 flex gap-[6px] border-t border-line"
             style="background:rgba(251,250,247,0.95);backdrop-filter:blur(12px);padding:6px 12px calc(10px + env(safe-area-inset-bottom));">
            <a href="{{ route('books') }}" wire:navigate
               @class([
                   'flex-1 flex flex-col items-center justify-center gap-1 rounded-[12px] no-underline transition-colors',
                   'bg-white text-ink shadow-[0_1px_2px_rgba(20,18,12,0.10)]' => ! $onLibrary,
                   'bg-transparent text-[#ADA89D]' => $onLibrary,
               ])
               style="min-height:52px;">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="{{ $onLibrary ? '1.4' : '1.8' }}"/>
                    <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="{{ $onLibrary ? '1.4' : '1.8' }}"/>
                </svg>
                <span @class(['uppercase', 'font-bold' => ! $onLibrary, 'font-medium' => $onLibrary]) style="font-size:11.5px;letter-spacing:0.03em;">Watch</span>
            </a>
            <a href="{{ route('library') }}" wire:navigate
               @class([
                   'flex-1 flex flex-col items-center justify-center gap-1 rounded-[12px] no-underline transition-colors',
                   'bg-white text-ink shadow-[0_1px_2px_rgba(20,18,12,0.10)]' => $onLibrary,
                   'bg-transparent text-[#ADA89D]' => ! $onLibrary,
               ])
               style="min-height:52px;">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 5h6v14H4zM14 5h6v14h-6" stroke="currentColor" stroke-width="{{ $onLibrary ? '1.8' : '1.4' }}" stroke-linejoin="round"/>
                    <path d="M4 9h6M14 9h6" stroke="currentColor" stroke-width="{{ $onLibrary ? '1.8' : '1.4' }}"/>
                </svg>
                <span @class(['uppercase', 'font-bold' => $onLibrary, 'font-medium' => ! $onLibrary]) style="font-size:11.5px;letter-spacing:0.03em;">Library</span>
            </a>
        </nav>

        @fluxScripts
    </body>
</html>
