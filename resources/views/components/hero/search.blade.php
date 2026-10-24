@props(['initial' => 'hotels'])

@php
    $tabs = [
        'hotels' => ['icon' => 'bed', 'hue' => 'var(--color-tab-hotels)', 'columns' => 'lg:grid-cols-4'],
        'real_estate' => ['icon' => 'house', 'hue' => 'var(--color-tab-realestate)', 'columns' => 'lg:grid-cols-4'],
        'classifieds' => ['icon' => 'tag', 'hue' => 'var(--color-tab-classifieds)', 'columns' => 'lg:grid-cols-3'],
        'jobs' => ['icon' => 'briefcase', 'hue' => 'var(--color-tab-jobs)', 'columns' => 'lg:grid-cols-3'],
    ];
    $control = 'block w-full min-w-0 cursor-pointer appearance-none truncate bg-transparent p-0 text-[15px] font-semibold text-navy outline-none placeholder:text-secondary focus-visible:outline-none';
    $options = fn (string $set) => __('home.search.options.'.$set);
@endphp

<div x-data="heroSearch(@js($initial))" {{ $attributes->class(['rounded-panel border border-white/28 bg-white/14 p-2 shadow-overlay backdrop-blur-[18px] backdrop-saturate-[1.4]']) }}>
    <div role="tablist" aria-label="{{ __('home.search.label') }}" class="flex gap-1 overflow-x-auto px-0.5 pt-0.5 pb-2 scrollbar-none">
        @foreach ($tabs as $key => $tab)
            @php($active = $key === $initial)
            <button type="button"
                    role="tab"
                    id="search-tab-{{ $key }}"
                    aria-controls="search-panel-{{ $key }}"
                    aria-selected="{{ $active ? 'true' : 'false' }}"
                    tabindex="{{ $active ? '0' : '-1' }}"
                    data-tab="{{ $key }}"
                    x-bind="tabButton"
                    style="--hue: {{ $tab['hue'] }}"
                    class="group/tab inline-flex items-center gap-2.5 rounded-button bg-navy/28 py-[7px] pr-[18px] pl-[7px] text-[14.5px] font-semibold whitespace-nowrap text-white transition-[background-color,box-shadow,translate] duration-250 aria-selected:bg-(--hue) aria-selected:shadow-[0_8px_20px_-8px_var(--hue)] motion-safe:hover:-translate-y-px">
                <span class="grid size-[30px] place-items-center rounded-chip bg-(--hue) transition-colors duration-250 group-aria-selected/tab:bg-white/20">
                    <x-icon :name="$tab['icon']" :size="17" :stroke="1.6" />
                </span>
                {{ __('home.search.tabs.'.$key) }}
            </button>
        @endforeach
    </div>

    <div class="rounded-tile bg-white/97 p-2.5 transition-[opacity,translate] duration-220 ease-out" x-bind:class="{ 'opacity-0 translate-y-1.5': fading }">
        @foreach ($tabs as $key => $tab)
            <form id="search-panel-{{ $key }}"
                  role="tabpanel"
                  aria-labelledby="search-tab-{{ $key }}"
                  action="#"
                  method="get"
                  data-tab="{{ $key }}"
                  x-bind="tabPanel"
                  @if ($key !== $initial) style="display: none" @endif
                  class="flex flex-col gap-2.5 lg:flex-row">
                <div x-ignore class="grid min-w-0 flex-1 grid-cols-2 gap-2 {{ $tab['columns'] }}">
                    @switch($key)
                        @case('hotels')
                            <x-hero.field icon="pin" :label="__('home.search.fields.destination')">
                                <input type="text" name="destination" value="{{ __('demo.search.destination') }}" class="{{ $control }}">
                            </x-hero.field>
                            <x-hero.field icon="calendar" :label="__('home.search.fields.check_in')">
                                <input type="text" name="check_in" value="{{ __('demo.search.check_in') }}" class="{{ $control }}">
                            </x-hero.field>
                            <x-hero.field icon="calendar" :label="__('home.search.fields.check_out')">
                                <input type="text" name="check_out" value="{{ __('demo.search.check_out') }}" class="{{ $control }}">
                            </x-hero.field>
                            <x-hero.field icon="users" :label="__('home.search.fields.guests')">
                                <input type="text" name="guests" value="{{ __('demo.search.guests') }}" class="{{ $control }}">
                            </x-hero.field>
                            @break

                        @case('real_estate')
                            <div class="flex min-h-16 min-w-0 items-center gap-3 rounded-field bg-surface-alt px-4 py-3">
                                <x-icon name="tag" :size="20" class="text-navy" />
                                <div class="min-w-0 flex-1">
                                    <span id="search-deal-label" class="mb-[3px] block text-xs font-medium text-secondary">{{ __('home.search.fields.deal') }}</span>
                                    <div role="radiogroup" aria-labelledby="search-deal-label" class="flex gap-1">
                                        @foreach (__('home.search.deal') as $deal => $dealLabel)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="deal" value="{{ $deal }}" class="peer sr-only" @checked($loop->first)>
                                                <span class="block rounded-full px-3.5 py-1 text-[13px] font-semibold text-secondary transition-colors duration-200 peer-checked:bg-navy peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-coral">{{ $dealLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <x-hero.field icon="building" :label="__('home.search.fields.type')">
                                <select name="type" class="{{ $control }}">
                                    @foreach ($options('property_types') as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                            </x-hero.field>
                            <x-hero.field icon="tag" :label="__('home.search.fields.price')">
                                <input type="text" name="price" value="{{ __('demo.search.price') }}" class="{{ $control }}">
                            </x-hero.field>
                            <x-hero.field icon="door" :label="__('home.search.fields.rooms')">
                                <select name="rooms" class="{{ $control }}">
                                    @foreach ($options('rooms') as $value => $text)
                                        <option value="{{ $value }}" @selected($value === 2)>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </x-hero.field>
                            @break

                        @case('classifieds')
                            <x-hero.field icon="search" :label="__('home.search.fields.query')">
                                <input type="search" name="q" placeholder="{{ __('home.search.placeholders.query') }}" class="{{ $control }}">
                            </x-hero.field>
                            <x-hero.field icon="grid" :label="__('home.search.fields.category')">
                                <select name="category" class="{{ $control }}">
                                    @foreach ($options('classified_categories') as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                            </x-hero.field>
                            <x-hero.field icon="pin" :label="__('home.search.fields.location')">
                                <input type="text" name="location" value="{{ __('demo.search.location') }}" class="{{ $control }}">
                            </x-hero.field>
                            @break

                        @case('jobs')
                            <x-hero.field icon="search" :label="__('home.search.fields.keyword')">
                                <input type="search" name="q" placeholder="{{ __('home.search.placeholders.keyword') }}" class="{{ $control }}">
                            </x-hero.field>
                            <x-hero.field icon="briefcase" :label="__('home.search.fields.category')">
                                <select name="category" class="{{ $control }}">
                                    @foreach ($options('job_categories') as $value => $text)
                                        <option value="{{ $value }}" @selected($value === 'tourism')>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </x-hero.field>
                            <x-hero.field icon="clock" :label="__('home.search.fields.employment')">
                                <select name="employment" class="{{ $control }}">
                                    @foreach ($options('employment') as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                            </x-hero.field>
                            @break
                    @endswitch
                </div>
                <button type="submit" class="inline-flex min-h-14 items-center justify-center gap-2.5 rounded-button bg-coral-pressed px-8 text-[15px] font-semibold text-white transition-[background-color,translate,box-shadow] duration-300 hover:bg-coral hover:shadow-search-button-hover motion-safe:hover:-translate-y-px">
                    <x-icon name="search" :size="18" :stroke="1.8" />
                    {{ __('home.search.submit') }}
                </button>
            </form>
        @endforeach
    </div>
</div>
