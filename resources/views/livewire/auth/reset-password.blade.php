<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PasswordReset) {
            $this->addError('email', __($status));
            return;
        }

        Session::flash('status', __($status));
        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <h1 class="font-serif text-[30px] font-medium tracking-[-0.015em] text-ink mb-2">Set a new password</h1>
    <p class="text-[15px] text-muted mb-[28px]">Choose a strong password for your account.</p>

    <x-auth-session-status :status="session('status')" class="mb-5" />

    <form wire:submit="resetPassword" class="flex flex-col gap-5">
        {{-- Email --}}
        <div>
            <label class="block font-semibold text-[13px] text-text-soft mb-[7px]">Email address</label>
            <input wire:model="email"
                   type="email"
                   autocomplete="email"
                   required
                   readonly
                   class="imprint-input opacity-70 cursor-not-allowed" />
            @error('email')
                <p class="text-[13px] text-[#A23E28] mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label class="block font-semibold text-[13px] text-text-soft mb-[7px]">New password</label>
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
                class="w-full py-[13px] bg-ink text-ink-cream border-none rounded-[10px] font-semibold text-[15px] cursor-pointer transition-colors hover:bg-ink-hover">
            Reset password
        </button>
    </form>
</div>
