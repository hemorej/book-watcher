<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $email = '';

    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('A reset link will be sent if the account exists.'));
    }
}; ?>

<div>
    <h1 class="font-serif text-[30px] font-medium tracking-[-0.015em] text-ink mb-2">Reset your password</h1>
    <p class="text-[15px] text-muted mb-[28px]">Enter your email and we'll send a reset link.</p>

    <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-5">
        {{-- Email --}}
        <div>
            <label class="block font-semibold text-[13px] text-text-soft mb-[7px]">Email address</label>
            <input wire:model="email"
                   type="email"
                   placeholder="you@example.com"
                   autocomplete="email"
                   required
                   autofocus
                   class="imprint-input" />
            @error('email')
                <p class="text-[13px] text-[#A23E28] mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Success status --}}
        @if (session('status'))
            <div class="flex items-center gap-[9px] px-[14px] py-[11px] bg-[#E7F0E9] rounded-[10px] text-[13.5px] text-[#2C6B4F]">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m5 12 5 5 9-11" stroke="#2C6B4F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ session('status') }}
            </div>
        @endif

        <button type="submit"
                class="w-full py-[13px] bg-ink text-ink-cream border-none rounded-[10px] font-semibold text-[15px] cursor-pointer transition-colors hover:bg-ink-hover">
            Email password reset link
        </button>
    </form>

    <p class="text-center text-[14px] text-muted mt-[26px]">
        Or, return to
        <a href="{{ route('login') }}" wire:navigate
           class="font-semibold text-ink underline underline-offset-[3px]">sign in</a>
    </p>
</div>
