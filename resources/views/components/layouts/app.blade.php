<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-paper antialiased" style="-webkit-font-smoothing:antialiased;">

        @php $onLibrary = request()->routeIs('library'); @endphp

        <header class="sticky top-0 z-40 flex items-center justify-between h-16 px-7 border-b border-line"
                style="background:rgba(251,250,247,0.85);backdrop-filter:blur(10px);">
            <a href="{{ route('books') }}" class="flex items-center gap-[11px] no-underline" wire:navigate>
                <x-app-logo />
            </a>

            {{-- Section switcher --}}
            <nav class="flex items-center gap-1 bg-toolbar border border-[#E7E4DB] rounded-[11px] p-[3px]">
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
                        class="flex items-center gap-[9px] bg-transparent border-none cursor-pointer px-[6px] py-[5px] rounded-[10px] transition-colors hover:bg-[#F1EFE8]"
                        title="Sign out">
                    <span class="w-[30px] h-[30px] rounded-full bg-ink text-[#F4F0E6] inline-flex items-center justify-center font-semibold text-[13px] shrink-0">
                        {{ auth()->user()->initials() }}
                    </span>
                    <span class="text-[14px] font-medium text-text-soft hidden sm:block">{{ auth()->user()->name }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" class="text-faint" aria-hidden="true">
                        <path d="m7 9 5-5 5 5M7 15l5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>
        </header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
