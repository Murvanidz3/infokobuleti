{{-- Step 2: title, description (Georgian required, English and Russian optional) and module fields. --}}
@php
    $number = fn (string $key, string $label, array $attributes = [], bool $optional = true) => ['key' => $key, 'label' => $label, 'attributes' => $attributes, 'optional' => $optional];
    $translations = filled($form['title']['en'] ?? null) || filled($form['title']['ru'] ?? null) || filled($form['description']['en'] ?? null) || filled($form['description']['ru'] ?? null);
@endphp

<div class="flex flex-col gap-7">
    <section class="flex flex-col gap-5" x-data="{ translations: @js($translations), tab: 'en' }">
        <x-form.field :label="__('account.wizard.fields.title')" for="wizard-title" :error="$errors->first('form.title.ka')" :hint="__('account.wizard.hints.title')">
            <input id="wizard-title" type="text" wire:model.blur="form.title.ka" maxlength="120" class="{{ $control }} {{ $border('form.title.ka') }}" placeholder="{{ __('account.wizard.placeholders.title_'.$type) }}">
        </x-form.field>

        <x-form.field :label="__('account.wizard.fields.description')" for="wizard-description" :error="$errors->first('form.description.ka')" :hint="__('account.wizard.hints.description')">
            <textarea id="wizard-description" wire:model.blur="form.description.ka" rows="6" maxlength="5000" class="{{ str_replace('min-h-12', 'min-h-36 py-3', $control) }} {{ $border('form.description.ka') }}" placeholder="{{ __('account.wizard.placeholders.description') }}"></textarea>
        </x-form.field>

        <div>
            <button type="button" x-on:click="translations = ! translations" x-bind:aria-expanded="translations" class="hit-44 relative inline-flex items-center gap-2 text-left text-[14px] font-semibold text-navy hover:text-coral-pressed">
                <x-icon name="globe" :size="17" />{{ __('account.wizard.translations') }}
                <x-icon name="chevron-down" :size="16" class="transition-transform" x-bind:class="translations && 'rotate-180'" />
            </button>
            <div x-show="translations" x-cloak class="mt-4 rounded-tile bg-surface-alt p-4 md:p-5">
                <div class="flex w-max gap-1 rounded-full border border-line bg-white p-1" role="tablist">
                    @foreach (['en', 'ru'] as $code)
                        <button type="button" role="tab" x-on:click="tab = '{{ $code }}'" x-bind:aria-selected="tab === '{{ $code }}'" class="hit-44 relative rounded-full px-4 py-1.5 text-[13px] font-semibold text-secondary aria-selected:bg-navy aria-selected:text-white">{{ __('locales.'.$code) }}</button>
                    @endforeach
                </div>
                @foreach (['en', 'ru'] as $code)
                    <div x-show="tab === '{{ $code }}'" @if ($code !== 'en') x-cloak @endif class="mt-4 flex flex-col gap-4">
                        <x-form.field :label="__('account.wizard.fields.title').' ('.strtoupper($code).')'" for="wizard-title-{{ $code }}" :error="$errors->first('form.title.'.$code)" optional>
                            <input id="wizard-title-{{ $code }}" type="text" wire:model.blur="form.title.{{ $code }}" maxlength="120" class="{{ $control }} {{ $border('form.title.'.$code) }}">
                        </x-form.field>
                        <x-form.field :label="__('account.wizard.fields.description').' ('.strtoupper($code).')'" for="wizard-description-{{ $code }}" :error="$errors->first('form.description.'.$code)" optional>
                            <textarea id="wizard-description-{{ $code }}" wire:model.blur="form.description.{{ $code }}" rows="4" maxlength="5000" class="{{ str_replace('min-h-12', 'min-h-28 py-3', $control) }} {{ $border('form.description.'.$code) }}"></textarea>
                        </x-form.field>
                    </div>
                @endforeach
                <p class="mt-3 text-[13px] text-secondary">{{ __('account.wizard.hints.translations') }}</p>
            </div>
        </div>
    </section>

    @php
        $candidates = match ($type) {
            'property' => [
                $number('area_m2', __('account.wizard.fields.area_m2'), ['min' => 1, 'step' => '0.1']),
                $number('land_area_m2', __('account.wizard.fields.land_area_m2'), ['min' => 1]),
                $number('rooms', __('account.wizard.fields.rooms'), ['min' => 1, 'max' => 50]),
                $number('bedrooms', __('account.wizard.fields.bedrooms'), ['min' => 0, 'max' => 50]),
                $number('bathrooms', __('account.wizard.fields.bathrooms'), ['min' => 0, 'max' => 20]),
                $number('floor', __('account.wizard.fields.floor'), ['min' => -3, 'max' => 100]),
                $number('total_floors', __('account.wizard.fields.total_floors'), ['min' => 1, 'max' => 100]),
                $number('guests', __('account.wizard.fields.guests'), ['min' => 1, 'max' => 100]),
                $number('distance_to_sea_m', __('account.wizard.fields.distance_to_sea_m'), ['min' => 0, 'max' => 100000]),
                $number('rooms_count', __('account.wizard.fields.rooms_count'), ['min' => 1, 'max' => 1000]),
            ],
            default => [],
        };
        $numbers = array_values(array_filter($candidates, fn (array $field): bool => $this->shows($field['key'])));
    @endphp

    @if ($numbers !== [])
        <section class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($numbers as $field)
                <x-form.field :label="$field['label']" for="wizard-{{ $field['key'] }}" :error="$errors->first('form.'.$field['key'])">
                    <input id="wizard-{{ $field['key'] }}" type="number" inputmode="decimal" wire:model.blur="form.{{ $field['key'] }}"
                           @foreach ($field['attributes'] as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach
                           class="{{ $control }} {{ $border('form.'.$field['key']) }}">
                </x-form.field>
            @endforeach
        </section>
    @endif

    @if ($type === 'property' && ($this->shows('condition') || $this->shows('building_status') || $this->shows('listed_by') || $this->shows('cadastral_code') || $this->shows('features') || $this->shows('purpose') || $this->shows('stars') || $this->shows('check_in')))
        <section class="flex flex-col gap-6">
            @if ($this->shows('purpose'))
                <div>
                    <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.purpose') }}</h2>
                    <x-account.choice-pills class="mt-3" model="form.purpose" :options="$options['purposes']" :label="__('account.wizard.fields.purpose')" />
                    @error('form.purpose')<p class="mt-2 text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
                </div>
            @endif
            @if ($this->shows('utilities'))
                <div>
                    <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.utilities') }} <span class="text-xs font-medium text-secondary">({{ __('components.optional') }})</span></h2>
                    <x-account.check-pills class="mt-3" model="form.utilities" :options="$options['utilities']" :label="__('account.wizard.fields.utilities')" />
                </div>
            @endif
            @if ($this->shows('condition'))
                <div>
                    <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.condition') }} <span class="text-xs font-medium text-secondary">({{ __('components.optional') }})</span></h2>
                    <x-account.choice-pills class="mt-3" model="form.condition" :options="$options['conditions']" :label="__('account.wizard.fields.condition')" />
                </div>
            @endif
            @if ($this->shows('building_status') || $this->shows('listed_by'))
                <div class="grid gap-6 md:grid-cols-2">
                    @if ($this->shows('building_status'))
                        <div>
                            <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.building_status') }}</h2>
                            <x-account.choice-pills class="mt-3" model="form.building_status" :options="$options['buildingStatuses']" :label="__('account.wizard.fields.building_status')" />
                        </div>
                    @endif
                    @if ($this->shows('listed_by'))
                        <div>
                            <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.listed_by') }}</h2>
                            <x-account.choice-pills class="mt-3" model="form.listed_by" :options="$options['listedBy']" :label="__('account.wizard.fields.listed_by')" />
                        </div>
                    @endif
                </div>
            @endif
            @if ($this->shows('stars'))
                <div>
                    <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.stars') }} <span class="text-xs font-medium text-secondary">({{ __('components.optional') }})</span></h2>
                    <x-account.choice-pills class="mt-3" model="form.stars" :options="$options['stars']" :label="__('account.wizard.fields.stars')" />
                </div>
            @endif
            @if ($this->shows('check_in') || $this->shows('check_out'))
                <div class="grid gap-4 md:grid-cols-2 md:max-w-[480px]">
                    <x-form.field :label="__('account.wizard.fields.check_in')" for="wizard-check-in" :error="$errors->first('form.check_in')" optional>
                        <input id="wizard-check-in" type="time" wire:model.blur="form.check_in" class="{{ $control }} {{ $border('form.check_in') }}">
                    </x-form.field>
                    <x-form.field :label="__('account.wizard.fields.check_out')" for="wizard-check-out" :error="$errors->first('form.check_out')" optional>
                        <input id="wizard-check-out" type="time" wire:model.blur="form.check_out" class="{{ $control }} {{ $border('form.check_out') }}">
                    </x-form.field>
                </div>
            @endif
            @if ($this->shows('cadastral_code'))
                <x-form.field class="md:max-w-[360px]" :label="__('account.wizard.fields.cadastral_code')" for="wizard-cadastral" :error="$errors->first('form.cadastral_code')" :hint="__('account.wizard.hints.cadastral')" optional>
                    <input id="wizard-cadastral" type="text" inputmode="numeric" wire:model.blur="form.cadastral_code" maxlength="32" class="{{ $control }} {{ $border('form.cadastral_code') }}" placeholder="20.40.01.123">
                </x-form.field>
            @endif
            @if ($this->shows('features'))
                <div>
                    <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.features') }}</h2>
                    <x-account.check-pills class="mt-3" model="form.features" :options="$options['features']" :label="__('account.wizard.fields.features')" />
                </div>
            @endif
        </section>
    @elseif ($type === 'classified')
        <section class="flex flex-col gap-5">
            @if ($this->shows('condition'))
                <div>
                    <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.condition') }} <span class="text-xs font-medium text-secondary">({{ __('components.optional') }})</span></h2>
                    <x-account.choice-pills class="mt-3" model="form.condition" :options="$options['conditions']" :label="__('account.wizard.fields.condition')" />
                </div>
            @endif
            @if ($schemaFields !== [])
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($schemaFields as $field)
                        @php($key = 'form.attribute_values.'.$field['key'])
                        @if ($field['type'] === 'multiselect')
                            <div class="md:col-span-2">
                                <h2 class="text-[13.5px] font-semibold text-navy">{{ $field['label'] }}</h2>
                                <x-account.check-pills class="mt-2" :model="$key" :options="$field['choices']" :label="$field['label']" />
                                @error($key)<p class="mt-2 text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
                            </div>
                        @elseif ($field['type'] === 'boolean')
                            <label class="flex min-h-12 cursor-pointer items-center gap-3 self-end text-[15px] text-navy">
                                <input type="checkbox" wire:model.live="{{ $key }}" class="size-5 shrink-0 cursor-pointer rounded-[5px] accent-coral">{{ $field['label'] }}
                            </label>
                        @else
                            <x-form.field :label="$field['label'].($field['unit'] ? ' ('.$field['unit'].')' : '')" for="wizard-attr-{{ $field['key'] }}" :error="$errors->first($key)" :optional="! $field['required']">
                                @if ($field['type'] === 'select')
                                    <x-form.select name="attr_{{ $field['key'] }}" id="wizard-attr-{{ $field['key'] }}" wire:model.live="{{ $key }}" :options="$field['choices']" />
                                @else
                                    <input id="wizard-attr-{{ $field['key'] }}" type="{{ $field['type'] === 'number' ? 'number' : 'text' }}" @if ($field['type'] === 'number') inputmode="decimal" @endif wire:model.blur="{{ $key }}" class="{{ $control }} {{ $border($key) }}">
                                @endif
                            </x-form.field>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>
    @elseif ($type === 'vacancy')
        <section class="flex flex-col gap-6">
            <div>
                <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.experience') }} <span class="text-xs font-medium text-secondary">({{ __('components.optional') }})</span></h2>
                <x-account.choice-pills class="mt-3" model="form.experience" :options="$options['experience']" :label="__('account.wizard.fields.experience')" />
            </div>
            <div>
                <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.languages') }}</h2>
                <x-account.check-pills class="mt-3" model="form.languages" :options="$options['languages']" :label="__('account.wizard.fields.languages')" />
            </div>
            <div>
                <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.benefits') }}</h2>
                <x-account.check-pills class="mt-3" model="form.benefits" :options="$options['benefits']" :label="__('account.wizard.fields.benefits')" />
            </div>
            <x-form.field class="md:max-w-[280px]" :label="__('account.wizard.fields.deadline')" for="wizard-deadline" :error="$errors->first('form.deadline')" optional>
                <input id="wizard-deadline" type="date" wire:model.blur="form.deadline" min="{{ today()->toDateString() }}" max="{{ today()->addYear()->toDateString() }}" class="{{ $control }} {{ $border('form.deadline') }}">
            </x-form.field>
        </section>
    @endif
</div>
