<x-layout.app :title="__('auth.staff.challenge_title')">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <x-auth.card :title="__('auth.staff.challenge_title')" :subtitle="__('auth.staff.challenge_subtitle')" icon="smartphone">
        <form method="POST"
              action="{{ localized_route('staff.two-factor.store') }}"
              x-data="{ recovery: @js($errors->has('recovery_code')) }"
              class="flex flex-col gap-5">
            @csrf
            <div x-show="!recovery">
                <x-form.field :label="__('auth.staff.code')" name="code">
                    <input type="text" name="code" id="code" inputmode="numeric" autocomplete="one-time-code" pattern="\d{6}" maxlength="6" placeholder="••••••"
                           x-bind:disabled="recovery" autofocus
                           @class([
                               'block min-h-14 w-full rounded-field border bg-white px-4 text-center text-[24px] font-semibold tracking-[0.5em] text-navy transition-colors duration-200 placeholder:text-secondary/40 hover:border-line-strong focus:border-navy',
                               'border-coral-pressed' => $errors->has('code'),
                               'border-line' => ! $errors->has('code'),
                           ])>
                </x-form.field>
            </div>

            <div x-show="recovery" x-cloak>
                <x-form.field :label="__('auth.staff.recovery_code')" name="recovery_code">
                    <x-form.input name="recovery_code" autocomplete="off" placeholder="XXXXX-XXXXX" x-bind:disabled="!recovery" class="uppercase tracking-[0.1em]" />
                </x-form.field>
            </div>

            <x-button type="submit" size="lg" class="w-full">{{ __('auth.staff.sign_in') }}</x-button>

            <button type="button" x-on:click="recovery = !recovery" class="hit-44 relative self-center text-[14px] font-semibold text-navy underline-offset-4 hover:underline">
                <span x-show="!recovery">{{ __('auth.staff.use_recovery') }}</span>
                <span x-show="recovery" x-cloak>{{ __('auth.staff.use_app') }}</span>
            </button>
        </form>
    </x-auth.card>
</x-layout.app>
