@props(['initial' => 'real_estate', 'options' => ['classifieds' => [], 'jobs' => []]])

@php
    $tabs = [
        'real_estate' => ['icon' => 'house', 'hue' => 'var(--color-tab-realestate)', 'columns' => 'lg:grid-cols-4', 'action' => localized_route('real-estate.index')],
        'classifieds' => ['icon' => 'tag', 'hue' => 'var(--color-tab-classifieds)', 'columns' => 'lg:grid-cols-3', 'action' => localized_route('classifieds.index')],
        'jobs' => ['icon' => 'briefcase', 'hue' => 'var(--color-tab-jobs)', 'columns' => 'lg:grid-cols-3', 'action' => localized_route('jobs.index')],
    ];
    $locations = \App\Models\Location::query()->active()->whereNotNull('parent_id')->orderBy('sort')->get()->mapWithKeys(fn ($location) => [$location->slug => (string) $location->name])->all();
    $control = 'block w-full min-w-0 cursor-pointer appearance-none truncate bg-transparent p-0 text-[15px] font-semibold text-navy outline-none placeholder:text-secondary focus-visible:outline-none';
    $options = $options + ['classifieds' => [], 'jobs' => []];
    $preset = fn (string $set) => __('home.search.options.'.$set);
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
            <div id="search-panel-{{ $key }}"
                 role="tabpanel"
                 aria-labelledby="search-tab-{{ $key }}"
                 data-tab="{{ $key }}"
                 x-bind="tabPanel"
                 @if ($key !== $initial) style="display: none" @endif>
            <form action="{{ $tab['action'] }}"
                  method="get"
                  x-on:submit="$event.target.querySelectorAll('input, select').forEach((field) => { if (field.value === '') field.disabled = true })"
                  class="flex flex-col gap-2.5 lg:flex-row">
                <div x-ignore class="grid min-w-0 flex-1 grid-cols-2 gap-2 {{ $tab['columns'] }}">
                    @switch($key)
                        @case('real_estate')
                            <x-hero.field icon="tag" :label="__('home.search.fields.deal')">
                                <select name="deal" class="{{ $control }}">
                                    <option value="">{{ __('site.filters.any') }}</option>
                                    @foreach (\App\Enums\PropertyDeal::options() as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                            </x-hero.field>
                            <x-hero.field icon="building" :label="__('home.search.fields.type')">
                                <select name="type[]" class="{{ $control }}">
                                    <option value="">{{ __('site.filters.any') }}</option>
                                    @foreach (\App\Enums\PropertyType::options() as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                            </x-hero.field>
                            <x-hero.field icon="tag" :label="__('site.filters.price_up_to')">
                                <input type="number" name="price_max" min="0" step="1000" inputmode="numeric" placeholder="₾" class="{{ $control }}">
                            </x-hero.field>
                            <x-hero.field icon="door" :label="__('home.search.fields.rooms')">
                                <select name="rooms[]" class="{{ $control }}">
                                    <option value="">{{ __('site.filters.any') }}</option>
                                    @foreach (['1' => '1', '2' => '2', '3' => '3', '4' => '4+'] as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
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
                                    <option value="">{{ __('site.filters.all_categories') }}</option>
                                    @foreach ($options['classifieds'] as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                            </x-hero.field>
                            <x-hero.field icon="pin" :label="__('home.search.fields.location')">
                                <select name="location" class="{{ $control }}">
                                    <option value="">{{ __('site.kobuleti_all') }}</option>
                                    @foreach ($locations as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                            </x-hero.field>
                            @break

                        @case('jobs')
                            <x-hero.field icon="search" :label="__('home.search.fields.keyword')">
                                <input type="search" name="q" placeholder="{{ __('home.search.placeholders.keyword') }}" class="{{ $control }}">
                            </x-hero.field>
                            <x-hero.field icon="briefcase" :label="__('home.search.fields.category')">
                                <select name="category" class="{{ $control }}">
                                    <option value="">{{ __('site.filters.all_categories') }}</option>
                                    @foreach ($options['jobs'] as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                            </x-hero.field>
                            <x-hero.field icon="clock" :label="__('home.search.fields.employment')">
                                <select name="employment[]" class="{{ $control }}">
                                    <option value="">{{ __('site.filters.any') }}</option>
                                    @foreach (\App\Enums\EmploymentType::options() as $value => $text)
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
            </div>
        @endforeach
    </div>
</div>
