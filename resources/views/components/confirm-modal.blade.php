{{--
    App-wide confirmation dialog, styled to match the Imprint modals (Add book /
    Add volume) rather than the browser's native `window.confirm()`.

    Mount it ONCE inside a Livewire component's root element (so `$wire` resolves
    in the trigger's scope):

        <x-confirm-modal />

    Then trigger it from any element within the same Alpine/Livewire tree:

        <button type="button"
                @click="$dispatch('confirm-action', {
                    message: @js(\"Remove 'The Negative' from the watch list?\"),
                    confirmLabel: 'Remove',
                    onConfirm: () => $wire.deleteBook(1),
                })">…</button>
--}}
<div x-data="{
        open: false,
        message: '',
        confirmLabel: 'Confirm',
        onConfirm: null,
        show(detail) {
            this.message = detail?.message ?? 'This can\'t be undone.';
            this.confirmLabel = detail?.confirmLabel ?? 'Confirm';
            this.onConfirm = detail?.onConfirm ?? null;
            this.open = true;
            this.$nextTick(() => this.$refs.confirmBtn?.focus());
        },
        cancel() {
            this.open = false;
            this.onConfirm = null;
        },
        accept() {
            const fn = this.onConfirm;
            this.cancel();
            if (fn) fn();
        },
     }"
     x-on:confirm-action.window="show($event.detail)"
     x-on:keydown.escape.window="open && cancel()">

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
         class="fixed inset-0 z-[60] flex items-center justify-center px-5"
         style="background:rgba(28,25,18,0.32);backdrop-filter:blur(2px);">
        <div @click="cancel()" class="fixed inset-0" aria-hidden="true"></div>

        <div x-show="open"
             x-transition.duration.150ms
             role="alertdialog" aria-modal="true" aria-labelledby="confirm-modal-title"
             class="relative w-full bg-white rounded-[18px] shadow-[0_24px_60px_rgba(20,18,12,0.26)] p-7"
             style="max-width:400px;">
            <h2 id="confirm-modal-title" class="font-serif text-[21px] text-ink leading-[1.3] mb-2">Are you sure?</h2>
            <p class="text-[14px] text-muted mb-6" x-text="message"></p>
            <div class="flex items-center justify-end gap-[10px]">
                <button type="button" @click="cancel()"
                        class="px-[18px] py-[10px] bg-transparent border-none font-semibold text-[14px] text-muted cursor-pointer rounded-[10px] hover:bg-toolbar hover:text-ink transition-colors">
                    Cancel
                </button>
                <button type="button" x-ref="confirmBtn" @click="accept()" x-text="confirmLabel"
                        class="px-[20px] py-[10px] bg-oxblood text-white border-none rounded-[10px] font-semibold text-[14px] cursor-pointer transition-colors hover:bg-[#733023]">
                </button>
            </div>
        </div>
    </div>
</div>
