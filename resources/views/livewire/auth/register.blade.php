<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirectIntended(route('books', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="font-serif text-[30px] font-medium tracking-[-0.015em] text-ink mb-2">Create your account</h1>
    <p class="text-[15px] text-muted mb-[28px]">Start watching the editions you care about.</p>

    <x-auth-session-status :status="session('status')" class="mb-5" />

    <form wire:submit="register" class="flex flex-col gap-5">
        {{-- Name --}}
        <div>
            <label class="block font-semibold text-[13px] text-text-soft mb-[7px]">Name</label>
            <input wire:model="name"
                   type="text"
                   placeholder="Full name"
                   autocomplete="name"
                   required
                   autofocus
                   class="imprint-input" />
            @error('name')
                <p class="text-[13px] text-[#A23E28] mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label class="block font-semibold text-[13px] text-text-soft mb-[7px]">Email address</label>
            <input wire:model="email"
                   type="email"
                   placeholder="you@example.com"
                   autocomplete="email"
                   required
                   class="imprint-input" />
            @error('email')
                <p class="text-[13px] text-[#A23E28] mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label class="block font-semibold text-[13px] text-text-soft mb-[7px]">Password</label>
            <div x-data="{ show: false }" class="relative">
                <input wire:model="password"
                       :type="show ? 'text' : 'password'"
                       placeholder="At least 8 characters"
                       autocomplete="new-password"
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

        {{-- Confirm Password --}}
        <div>
            <label class="block font-semibold text-[13px] text-text-soft mb-[7px]">Confirm password</label>
            <input wire:model="password_confirmation"
                   type="password"
                   placeholder="Re-enter password"
                   autocomplete="new-password"
                   required
                   class="imprint-input" />
            @error('password_confirmation')
                <p class="text-[13px] text-[#A23E28] mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full mt-1 py-[13px] bg-ink text-ink-cream border-none rounded-[10px] font-semibold text-[15px] cursor-pointer transition-colors hover:bg-ink-hover">
            Create account
        </button>
    </form>

    <p class="text-center text-[14px] text-muted mt-[26px]">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate
           class="font-semibold text-ink underline underline-offset-[3px]">Sign in</a>
    </p>
</div>
