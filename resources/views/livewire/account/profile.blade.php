<section class="rounded-panel border border-line bg-white p-5 shadow-card md:p-7" aria-labelledby="profile-title">
    <h2 id="profile-title" class="font-serif text-[21px] font-semibold text-navy">{{ __('account.profile.personal') }}</h2>
    <p class="mt-1.5 text-[14px] text-secondary">{{ __('account.profile.personal_hint') }}</p>

    <form wire:submit="save" class="mt-5 grid gap-5 md:grid-cols-2">
        <x-form.field :label="__('auth.onboarding.name')" for="profile-name" :error="$errors->first('name')">
            <x-form.input name="name" id="profile-name" wire:model="name" autocomplete="name" maxlength="80" required />
        </x-form.field>

        <x-form.field :label="__('account.profile.email')" for="profile-email" :error="$errors->first('email')" :hint="__('account.profile.email_hint')" optional>
            <x-form.input name="email" id="profile-email" type="email" wire:model="email" autocomplete="email" maxlength="190" />
        </x-form.field>

        <x-form.field :label="__('account.profile.locale')" for="profile-locale" :error="$errors->first('locale')" :hint="__('account.profile.locale_hint')">
            <x-form.select name="locale" id="profile-locale" wire:model="locale" :options="$locales" :placeholder="false" />
        </x-form.field>

        <div class="flex items-end md:col-span-2">
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('account.common.save') }}</x-button>
        </div>
    </form>
</section>
