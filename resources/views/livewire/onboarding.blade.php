<form wire:submit="save" class="flex flex-col gap-5" novalidate>
    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field :label="__('auth.onboarding.first_name')" name="firstName">
            <x-form.input name="firstName" wire:model="firstName" autocomplete="given-name" :placeholder="__('auth.onboarding.first_name_placeholder')" autofocus />
        </x-form.field>

        <x-form.field :label="__('auth.onboarding.last_name')" name="lastName">
            <x-form.input name="lastName" wire:model="lastName" autocomplete="family-name" :placeholder="__('auth.onboarding.last_name_placeholder')" />
        </x-form.field>
    </div>

    @if ($needsEmail)
        <x-form.field :label="__('auth.onboarding.email')" name="email" :hint="__('auth.onboarding.email_hint')">
            <x-form.input name="email" type="email" wire:model="email" autocomplete="email" inputmode="email" placeholder="name@example.com" />
        </x-form.field>
    @endif

    <x-form.field :label="__('auth.onboarding.account_type')" name="accountType">
        <div role="radiogroup" aria-label="{{ __('auth.onboarding.account_type') }}" class="grid grid-cols-2 gap-2.5">
            @foreach ($accountTypes as $value => $label)
                <label class="cursor-pointer">
                    <input type="radio" wire:model.live="accountType" value="{{ $value }}" class="peer sr-only">
                    <span class="flex min-h-[72px] flex-col justify-center gap-1 rounded-tile border border-line bg-white px-4 py-3 transition-colors duration-200 hover:border-line-strong peer-checked:border-navy peer-checked:bg-navy peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral [&>small]:peer-checked:text-white/75">
                        <span class="text-[15px] font-semibold">{{ $label }}</span>
                        <small class="text-[12.5px] leading-snug text-secondary">{{ __('auth.onboarding.account_type_hint.'.$value) }}</small>
                    </span>
                </label>
            @endforeach
        </div>
    </x-form.field>

    @if ($accountType === 'business')
        <x-form.field :label="__('auth.onboarding.business_type')" name="businessType">
            <x-form.select name="businessType" wire:model="businessType" :options="$businessTypes" />
        </x-form.field>

        <x-form.field :label="__('auth.onboarding.business_name')" name="businessName">
            <x-form.input name="businessName" wire:model="businessName" autocomplete="organization" />
        </x-form.field>
    @endif

    <x-button type="submit" size="lg" class="mt-1 w-full" wire:loading.attr="disabled">{{ __('auth.onboarding.submit') }}</x-button>
</form>
