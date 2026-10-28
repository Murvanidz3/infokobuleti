<section class="rounded-panel border border-line bg-white p-5 shadow-card md:p-7" aria-labelledby="privacy-title">
    <h2 id="privacy-title" class="font-serif text-[21px] font-semibold text-navy">{{ __('privacy.title') }}</h2>
    <p class="mt-1.5 text-[14px] text-secondary">{{ __('privacy.subtitle') }}</p>

    <ul class="mt-5 divide-y divide-line">
        <li class="flex flex-wrap items-center gap-4 py-4 first:pt-0">
            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-surface-alt text-navy"><x-icon name="download" :size="20" /></span>
            <div class="min-w-0 flex-1">
                <p class="text-[15px] font-semibold text-navy">{{ __('privacy.export.title') }}</p>
                <p class="text-[14px] text-secondary">{{ __('privacy.export.text') }}</p>
            </div>
            <x-button variant="secondary" size="sm" wire:click="requestExport" wire:loading.attr="disabled" wire:target="requestExport">{{ __('privacy.export.button') }}</x-button>
        </li>

        <li class="py-4 last:pb-0">
            <div class="flex flex-wrap items-center gap-4">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-coral-tint text-coral-pressed"><x-icon name="alert" :size="20" /></span>
                <div class="min-w-0 flex-1">
                    <p class="text-[15px] font-semibold text-navy">{{ __('privacy.delete.title') }}</p>
                    <p class="text-[14px] text-secondary">{{ $staff ? __('privacy.delete.staff') : __('privacy.delete.text') }}</p>
                </div>
                @unless ($staff || $confirming)
                    <x-button variant="ghost" size="sm" wire:click="$set('confirming', true)">{{ __('privacy.delete.button') }}</x-button>
                @endunless
            </div>

            @if ($confirming && ! $staff)
                <form wire:submit="deleteAccount" class="mt-5 rounded-tile bg-surface-alt p-4 md:p-5">
                    <p class="text-[14px] leading-normal text-navy">{{ __('privacy.delete.warning') }}</p>
                    <label for="delete-confirmation" class="mt-4 block text-[14px] font-medium text-navy">{{ __('privacy.delete.confirm_label', ['word' => $word]) }}</label>
                    <input id="delete-confirmation" type="text" wire:model="confirmation" autocomplete="off"
                           class="mt-1.5 block min-h-11 w-full max-w-xs rounded-field border border-line bg-white px-3.5 text-[15px] text-navy hover:border-line-strong focus:border-navy"
                           @error('confirmation') aria-invalid="true" aria-describedby="delete-confirmation-error" @enderror>
                    @error('confirmation')
                        <p id="delete-confirmation-error" class="mt-2 text-[13.5px] text-coral-pressed">{{ $message }}</p>
                    @enderror
                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-button type="submit" size="sm" variant="tertiary" wire:loading.attr="disabled">{{ __('privacy.delete.submit') }}</x-button>
                        <x-button type="button" size="sm" variant="ghost" wire:click="$set('confirming', false)">{{ __('privacy.delete.cancel') }}</x-button>
                    </div>
                </form>
            @endif
        </li>
    </ul>
</section>
