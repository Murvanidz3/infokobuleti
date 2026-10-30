{{-- Step 5: price (or the first room / the salary) and how people get in touch. --}}
@php
    $input = fn (string $key, string $label, string $type = 'text', array $attributes = [], bool $optional = false, ?string $hint = null) => compact('key', 'label', 'type', 'attributes', 'optional', 'hint');
    $fields = [];
@endphp

<div class="flex flex-col gap-7">
    @if ($type === 'project')
        <section class="grid gap-4 md:grid-cols-[1fr_auto]">
            <x-form.field :label="__('account.wizard.fields.price_per_m2')" for="wizard-price-m2" :error="$errors->first('form.price_per_m2')">
                <input id="wizard-price-m2" type="number" inputmode="decimal" min="1" wire:model.blur="form.price_per_m2" class="{{ $control }} {{ $border('form.price_per_m2') }}">
            </x-form.field>
            <div class="flex flex-col gap-2">
                <span class="text-[13.5px] font-semibold text-navy">{{ __('account.wizard.fields.currency') }}</span>
                <x-account.choice-pills model="form.currency" :options="$options['currencies']" :label="__('account.wizard.fields.currency')" class="flex-nowrap" />
            </div>
        </section>
        @php
            $fields = [
            $input('contact_name', __('account.wizard.fields.contact_name'), 'text', ['autocomplete' => 'name', 'maxlength' => 120], true),
            $input('contact_phone', __('account.wizard.fields.contact_phone'), 'tel', ['inputmode' => 'tel', 'autocomplete' => 'tel', 'placeholder' => '+995 5XX XX XX XX']),
        ];
        @endphp
    @elseif ($type === 'property' || $type === 'classified')
        <section class="grid gap-4 md:grid-cols-[1fr_auto]">
            <x-form.field :label="__('account.wizard.fields.price').($type === 'property' && ($form['deal'] ?? '') === 'rent' ? ' ('.__('listing.per_month').')' : '').($type === 'property' && ($form['deal'] ?? '') === 'daily_rent' ? ' ('.__('listing.per_day').')' : '')" for="wizard-price" :error="$errors->first('form.price')"
                          :hint="$type === 'classified' ? __('account.wizard.hints.price_empty') : null" :optional="$type === 'classified'">
                <input id="wizard-price" type="number" inputmode="decimal" min="0" wire:model.blur="form.price" class="{{ $control }} {{ $border('form.price') }}">
            </x-form.field>
            <div class="flex flex-col gap-2">
                <span class="text-[13.5px] font-semibold text-navy">{{ __('account.wizard.fields.currency') }}</span>
                <x-account.choice-pills model="form.currency" :options="$options['currencies']" :label="__('account.wizard.fields.currency')" class="flex-nowrap" />
            </div>
            @if ($type === 'classified')
                <label class="flex min-h-11 cursor-pointer items-center gap-3 text-[15px] text-navy">
                    <input type="checkbox" wire:model.live="form.negotiable" class="size-5 shrink-0 cursor-pointer rounded-[5px] accent-coral">{{ __('account.wizard.fields.negotiable') }}
                </label>
            @endif
        </section>
        @if ($priceInsight ?? null)
            <x-insights.price :insight="$priceInsight" owner />
        @endif
        @php
            $fields = [
            $input('contact_name', __('account.wizard.fields.contact_name'), 'text', ['autocomplete' => 'name', 'maxlength' => 120], true),
            $input('contact_phone', __('account.wizard.fields.contact_phone'), 'tel', ['inputmode' => 'tel', 'autocomplete' => 'tel', 'placeholder' => '+995 5XX XX XX XX']),
        ];
        @endphp
    @elseif ($type === 'vacancy')
        <section class="flex flex-col gap-4">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-[1fr_1fr_auto]">
                <x-form.field :label="__('account.wizard.fields.salary_min')" for="wizard-salary-min" :error="$errors->first('form.salary_min')" optional>
                    <input id="wizard-salary-min" type="number" inputmode="numeric" min="0" wire:model.blur="form.salary_min" class="{{ $control }} {{ $border('form.salary_min') }}">
                </x-form.field>
                <x-form.field :label="__('account.wizard.fields.salary_max')" for="wizard-salary-max" :error="$errors->first('form.salary_max')" optional>
                    <input id="wizard-salary-max" type="number" inputmode="numeric" min="0" wire:model.blur="form.salary_max" class="{{ $control }} {{ $border('form.salary_max') }}">
                </x-form.field>
                <div class="col-span-2 flex flex-col gap-2 md:col-span-1">
                    <span class="text-[13.5px] font-semibold text-navy">{{ __('account.wizard.fields.currency') }}</span>
                    <x-account.choice-pills model="form.salary_currency" :options="$options['currencies']" :label="__('account.wizard.fields.currency')" class="flex-nowrap" />
                </div>
            </div>
            <p class="text-[13px] text-secondary">{{ __('account.wizard.hints.salary') }}</p>
            <div>
                <span class="text-[13.5px] font-semibold text-navy">{{ __('account.wizard.fields.salary_period') }}</span>
                <x-account.choice-pills class="mt-2" model="form.salary_period" :options="$options['periods']" :label="__('account.wizard.fields.salary_period')" />
            </div>
        </section>
        <section>
            <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.apply_method') }}</h2>
            <x-account.choice-pills class="mt-3" model="form.apply_method" :options="$options['applyMethods']" :label="__('account.wizard.fields.apply_method')" />
        </section>
        @php
            $fields = array_values(array_filter([
            ($form['apply_method'] ?? '') === 'phone' ? $input('apply_phone', __('account.wizard.fields.contact_phone'), 'tel', ['inputmode' => 'tel', 'autocomplete' => 'tel', 'placeholder' => '+995 5XX XX XX XX']) : null,
            ($form['apply_method'] ?? '') === 'email' ? $input('apply_email', __('account.wizard.fields.contact_email'), 'email', ['autocomplete' => 'email']) : null,
        ]));
        @endphp
    @endif

    @if ($fields !== [])
        <section class="grid gap-4 md:grid-cols-2" aria-label="{{ __('account.wizard.contact') }}">
            @foreach ($fields as $field)
                <x-form.field :label="$field['label']" for="wizard-{{ $field['key'] }}" :error="$errors->first('form.'.$field['key'])" :optional="$field['optional']" :hint="$field['hint']">
                    <input id="wizard-{{ $field['key'] }}" type="{{ $field['type'] }}" wire:model.blur="form.{{ $field['key'] }}"
                           @foreach ($field['attributes'] as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach
                           class="{{ $control }} {{ $border('form.'.$field['key']) }}">
                </x-form.field>
            @endforeach
        </section>
    @endif
</div>
