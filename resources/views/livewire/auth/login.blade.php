<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('books', absolute: false), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div>
    <h1 class="font-serif text-[30px] font-medium tracking-[-0.015em] text-ink mb-2">Welcome back</h1>
    <p class="text-[15px] text-muted mb-[30px]">Sign in to your watch list.</p>

    <x-auth-session-status :status="session('status')" class="mb-5" />

    <form wire:submit="login" class="flex flex-col gap-5">
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

        {{-- Password --}}
        <div>
            <div class="flex items-baseline justify-between mb-[7px]">
                <label class="font-semibold text-[13px] text-text-soft">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate
                       class="text-[13px] font-medium text-oxblood no-underline hover:underline">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div x-data="{ show: false }" class="relative">
                <input wire:model="password"
                       :type="show ? 'text' : 'password'"
                       placeholder="••••••••"
                       autocomplete="current-password"
                       required
                       class="imprint-input pr-11" />
                <button type="button" @click="show = !show"
                        class="absolute right-[6px] top-1/2 -translate-y-1/2 w-8 h-8 inline-flex items-center justify-center bg-transparent border-none cursor-pointer text-[#9B978D] hover:text-ink transition-colors rounded-[6px]">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.6"/>
                        <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.6"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="text-[13px] text-[#A23E28] mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember me --}}
        <label class="flex items-center gap-[9px] cursor-pointer text-[14px] text-[#4A463E]">
            <span class="relative inline-flex w-[18px] h-[18px] shrink-0">
                <input wire:model="remember" type="checkbox"
                       class="appearance-none w-[18px] h-[18px] border border-[#D7D3C8] rounded-[5px] bg-white checked:bg-ink checked:border-ink cursor-pointer transition-colors" />
                <svg x-cloak class="absolute inset-0 m-auto w-3 h-3 text-white pointer-events-none hidden peer-checked:block" viewBox="0 0 12 12" fill="none">
                    <path d="m2 6 3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            Remember me
        </label>

        <button type="submit"
                class="w-full py-[13px] bg-ink text-ink-cream border-none rounded-[10px] font-semibold text-[15px] cursor-pointer transition-colors hover:bg-ink-hover">
            Sign in
        </button>
    </form>

    @if (Route::has('register'))
        <p class="text-center text-[14px] text-muted mt-[26px]">
            New to Imprint?
            <a href="{{ route('register') }}" wire:navigate
               class="font-semibold text-ink underline underline-offset-[3px]">Create an account</a>
        </p>
    @endif
</div>
