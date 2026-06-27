<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-paper antialiased" style="-webkit-font-smoothing:antialiased;">
        <div class="flex min-h-screen">

            {{-- Brand panel --}}
            <div class="hidden lg:flex grow-0 shrink-0 basis-[44%] max-w-[560px] bg-brand-panel border-r border-[#E7E2D6] flex-col justify-between"
                 style="padding:44px 52px;">
                <div class="flex items-center gap-[11px]">
                    <span class="inline-flex w-[34px] h-[34px] rounded-[8px] bg-ink items-center justify-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 5h8M8 19h8M12 5v14" stroke="#F4F0E6" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span class="font-serif text-[22px] font-medium tracking-[-0.01em] text-ink">Imprint</span>
                </div>

                <div style="max-width:380px;">
                    <p class="font-serif text-[36px] font-normal leading-[1.18] tracking-[-0.015em] text-[#23211C] mb-5">
                        Know the moment a book <em class="italic">returns to print.</em>
                    </p>
                    <p class="text-[15px] leading-relaxed text-[#6F6B61]" style="max-width:330px;">
                        A quiet watch list for fine-art editions. We keep an eye on each title and tell you the instant it becomes available.
                    </p>
                </div>

                <div class="flex items-center gap-[10px] text-[12.5px] text-[#928D80] tracking-[0.02em]">
                    <span class="w-6 h-px bg-[#CFC8B8] inline-block"></span>
                    <span>Imprint — a private edition watch</span>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="flex-1 flex items-center justify-center px-7 py-10">
                <div class="w-full max-w-[368px]">
                    {{ $slot }}
                </div>
            </div>

        </div>
        @fluxScripts
    </body>
</html>
