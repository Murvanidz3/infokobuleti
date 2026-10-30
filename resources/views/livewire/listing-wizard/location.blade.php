{{-- Step 3: area, street address and a pin on the map (distance to the sea is calculated from it). --}}
@php($pinRequired = \App\Support\Listings\ListingForm::requiresPin($type))

<div class="flex flex-col gap-6">
    <div class="grid gap-4 md:grid-cols-2">
        <x-form.field :label="__('account.wizard.fields.location_id')" for="wizard-location" :error="$errors->first('form.location_id')">
            <x-form.select name="location" id="wizard-location" wire:model.live="form.location_id" :options="$options['locations']" :invalid="$errors->has('form.location_id')" />
        </x-form.field>
        <x-form.field :label="__('account.wizard.fields.address')" for="wizard-address" :error="$errors->first('form.address')" optional>
            <input id="wizard-address" type="text" wire:model.blur="form.address" maxlength="190" autocomplete="street-address" class="{{ $control }} {{ $border('form.address') }}" placeholder="{{ __('account.wizard.placeholders.address') }}">
        </x-form.field>
    </div>

    <section aria-labelledby="pin-title" class="flex flex-col gap-3">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="pin-title" class="text-[15px] font-semibold text-navy">
                {{ __('account.wizard.location.pin') }}
                @unless ($pinRequired)<span class="text-xs font-medium text-secondary">({{ __('components.optional') }})</span>@endunless
            </h2>
            @if ($sea !== null && in_array($type, ['property', 'project'], true))
                <p class="inline-flex items-center gap-1.5 text-[14px] font-semibold text-navy"><x-icon name="waves" :size="17" class="text-secondary" />{{ __('account.wizard.location.sea', ['distance' => number_format($sea, 0, '.', ' ')]) }}</p>
            @endif
        </div>

        {{-- Address search (OpenStreetMap) --}}
        <form wire:submit="searchAddress" class="flex gap-2" role="search">
            <label for="wizard-address-search" class="sr-only">{{ __('account.wizard.location.search') }}</label>
            <div class="relative min-w-0 flex-1">
                <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-secondary" />
                <input id="wizard-address-search" type="search" wire:model="addressQuery" maxlength="120" class="{{ $control }} {{ $border('addressQuery') }} pl-11" placeholder="{{ __('account.wizard.location.search_placeholder') }}">
            </div>
            <x-button type="submit" variant="tertiary" wire:loading.attr="disabled" wire:target="searchAddress">{{ __('account.wizard.location.find') }}</x-button>
        </form>
        @error('addressQuery')<p class="text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror

        @if ($addressResults !== [])
            <ul class="overflow-hidden rounded-tile border border-line bg-white">
                @foreach ($addressResults as $index => $result)
                    <li wire:key="address-{{ $index }}">
                        <button type="button" wire:click="pickAddress({{ $index }})" class="flex min-h-12 w-full items-center gap-3 border-b border-line px-4 py-2.5 text-left text-[14px] text-navy last:border-b-0 hover:bg-surface-alt">
                            <x-icon name="pin" :size="17" class="shrink-0 text-secondary" />{{ $result['label'] }}
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif

        <div wire:ignore x-data="pinMap(@js(is_numeric($form['lat'] ?? null) ? (float) $form['lat'] : null), @js(is_numeric($form['lng'] ?? null) ? (float) $form['lng'] : null))"
             x-on:pin-moved.window="moveTo($event.detail)"
             class="relative h-[340px] overflow-hidden rounded-card border border-line bg-surface-alt md:h-[400px]">
            <div x-ref="map" class="absolute inset-0"></div>
            <div x-show="loading" class="absolute inset-0 grid place-items-center text-secondary"><x-icon name="map" :size="36" :stroke="1.25" /></div>
        </div>
        <p class="text-[13px] text-secondary">{{ __('account.wizard.location.hint') }}</p>
        @if ($errors->has('form.lat') || $errors->has('form.lng'))
            <p class="text-[13px] font-medium text-coral-pressed">{{ $errors->first('form.lat') ?: $errors->first('form.lng') }}</p>
        @endif
    </section>
</div>
