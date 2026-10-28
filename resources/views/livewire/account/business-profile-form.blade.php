<section class="rounded-panel border border-line bg-white p-5 shadow-card md:p-7" aria-labelledby="business-title">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 id="business-title" class="font-serif text-[21px] font-semibold text-navy">{{ __('account.business.title') }}</h2>
            <p class="mt-1.5 text-[14px] text-secondary">{{ $profile ? __('account.business.hint') : __('account.business.create_hint') }}</p>
        </div>
        @if ($profile)
            <div class="flex flex-wrap items-center gap-2">
                @if ($profile->verified_at)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-navy px-3 py-1.5 text-[12.5px] font-semibold text-white"><x-icon name="check-circle" :size="15" />{{ __('account.business.verified') }}</span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-line px-3 py-1.5 text-[12.5px] font-semibold text-navy">{{ __('account.business.not_verified') }}</span>
                @endif
                <x-link-arrow size="sm" :href="$publicUrl">{{ __('account.business.public_page') }}</x-link-arrow>
            </div>
        @endif
    </div>

    @if (! $open)
        <x-button variant="secondary" class="mt-5" icon="building" wire:click="$set('open', true)">{{ __('account.business.create') }}</x-button>
    @else
        <form wire:submit="save" class="mt-6 grid gap-5 md:grid-cols-2">
            <x-form.field :label="__('auth.onboarding.business_type')" for="business-type" :error="$errors->first('type')">
                <x-form.select name="type" id="business-type" wire:model="type" :options="$typeOptions" />
            </x-form.field>

            <x-form.field :label="__('auth.onboarding.business_name')" for="business-name" :error="$errors->first('name')">
                <x-form.input name="name" id="business-name" wire:model="name" maxlength="120" required />
            </x-form.field>

            <div class="md:col-span-2" x-data="{ tab: 'ka' }">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <span class="text-[13.5px] font-semibold text-navy">{{ __('account.business.description') }}</span>
                    <div class="flex gap-1 rounded-full border border-line bg-surface-alt p-1" role="tablist">
                        @foreach (['ka', 'en', 'ru'] as $code)
                            <button type="button" role="tab" x-on:click="tab = '{{ $code }}'" x-bind:aria-selected="tab === '{{ $code }}'"
                                    class="hit-44 relative rounded-full px-3.5 py-1.5 text-[13px] font-semibold text-secondary aria-selected:bg-navy aria-selected:text-white">{{ strtoupper($code) }}</button>
                        @endforeach
                    </div>
                </div>
                @foreach (['ka', 'en', 'ru'] as $code)
                    <div x-show="tab === '{{ $code }}'" @if ($code !== 'ka') x-cloak @endif>
                        <textarea wire:model="description.{{ $code }}" rows="4" maxlength="3000" aria-label="{{ __('account.business.description') }} ({{ strtoupper($code) }})"
                                  class="block w-full rounded-field border border-line bg-white px-4 py-3 text-[15px] text-navy hover:border-line-strong focus:border-navy"></textarea>
                    </div>
                @endforeach
                @if ($errors->has('description.*'))
                    <p class="mt-2 text-[13px] font-medium text-coral-pressed">{{ $errors->first('description.*') }}</p>
                @endif
            </div>

            <x-form.field :label="__('account.business.phone')" for="business-phone" :error="$errors->first('phone')" optional>
                <x-form.input name="phone" id="business-phone" type="tel" wire:model="phone" inputmode="tel" placeholder="+995 5XX XX XX XX" />
            </x-form.field>

            <x-form.field :label="__('account.profile.email')" for="business-email" :error="$errors->first('email')" optional>
                <x-form.input name="email" id="business-email" type="email" wire:model="email" />
            </x-form.field>

            <x-form.field :label="__('account.business.website')" for="business-website" :error="$errors->first('website')" optional>
                <x-form.input name="website" id="business-website" type="url" wire:model="website" placeholder="https://" />
            </x-form.field>

            <x-form.field :label="__('account.business.location')" for="business-location" :error="$errors->first('locationId')" optional>
                <x-form.select name="locationId" id="business-location" wire:model="locationId" :options="$locationOptions" />
            </x-form.field>

            <x-form.field class="md:col-span-2" :label="__('account.business.address')" for="business-address" :error="$errors->first('address')" optional>
                <x-form.input name="address" id="business-address" wire:model="address" maxlength="190" />
            </x-form.field>

            <div class="flex items-center gap-4 md:col-span-2">
                <span class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-tile border border-line bg-surface-alt text-line-strong">
                    @if ($logo && method_exists($logo, 'isPreviewable') && $logo->isPreviewable())
                        <img src="{{ $logo->temporaryUrl() }}" alt="" class="size-full object-cover">
                    @elseif ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="" class="size-full object-cover">
                    @else
                        <x-icon name="image" :size="24" />
                    @endif
                </span>
                <div class="flex flex-col gap-1">
                    <label for="business-logo" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-button border border-coral bg-white px-4 text-[14px] font-semibold text-navy transition-colors hover:bg-coral-tint">
                        {{ __('account.business.logo') }}
                    </label>
                    <input type="file" id="business-logo" wire:model="logo" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    <span class="text-[12.5px] text-secondary" wire:loading.remove wire:target="logo">{{ __('account.business.logo_hint') }}</span>
                    <span class="text-[12.5px] text-secondary" wire:loading wire:target="logo">{{ __('account.common.uploading') }}</span>
                    @error('logo')<span class="text-[13px] font-medium text-coral-pressed">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="md:col-span-2">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="save, logo">{{ $profile ? __('account.common.save') : __('account.business.create') }}</x-button>
            </div>
        </form>
    @endif
</section>
