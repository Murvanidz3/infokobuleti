{{-- Review form for hotel guests (trust layer: after a booking request or a message thread). --}}
<div class="mt-6 rounded-card border border-line bg-surface-alt p-5">
    @if ($sent)
        <p class="flex items-center gap-2 text-[14.5px] font-semibold text-navy"><x-icon name="check-circle" :size="18" />{{ __('site.reviews.form.sent') }}</p>
    @elseif ($guest)
        <p class="text-[14.5px] text-secondary">{{ __('site.reviews.form.guest') }}
            <a href="{{ localized_route('login') }}" class="font-semibold text-navy underline" x-on:click.prevent="$dispatch('require-login')">{{ __('site.reviews.form.sign_in') }}</a>
        </p>
    @elseif ($reason !== null)
        <p class="flex items-start gap-2 text-[14px] text-secondary"><x-icon name="shield" :size="17" class="mt-0.5 shrink-0" />{{ __('site.reviews.form.blocked.'.$reason) }}</p>
    @else
        <form wire:submit="submit" class="flex flex-col gap-4">
            <h3 class="font-serif text-[18px] font-semibold text-navy">{{ __('site.reviews.form.title') }}</h3>
            <fieldset>
                <legend class="mb-2 text-[13.5px] font-semibold text-navy">{{ __('site.reviews.form.rating') }}</legend>
                <div role="radiogroup" class="flex flex-wrap gap-1.5">
                    @foreach (range(1, 10) as $score)
                        <label class="cursor-pointer">
                            <input type="radio" value="{{ $score }}" wire:model.live="rating" class="peer sr-only">
                            <span class="grid size-10 place-items-center rounded-full border border-line bg-white text-[14px] font-semibold text-navy transition-colors duration-200 hover:border-line-strong peer-checked:border-navy peer-checked:bg-navy peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-coral">{{ $score }}</span>
                        </label>
                    @endforeach
                </div>
                @error('rating')<p class="mt-2 text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
            </fieldset>
            <x-form.field :label="__('site.reviews.form.body')" name="body" :optional="true">
                <textarea id="body" wire:model="body" rows="4" maxlength="2000" class="block w-full rounded-field border border-line bg-white px-4 py-3 text-[15px] text-navy hover:border-line-strong focus:border-navy"></textarea>
            </x-form.field>
            <p class="text-[12.5px] text-secondary">{{ __('site.reviews.form.moderated') }}</p>
            <x-button type="submit" class="self-start" icon="send" wire:loading.attr="disabled">{{ __('site.reviews.form.submit') }}</x-button>
        </form>
    @endif
</div>
