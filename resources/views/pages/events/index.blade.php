@php
    $query = fn (array $extra) => array_filter(array_merge(['category' => $category?->slug, 'view' => $mode === 'calendar' ? 'calendar' : null, 'month' => $mode === 'calendar' ? $month->format('Y-m') : null], $extra));
@endphp

<x-layout.app :title="__('site.events.title')" :description="__('site.events.subtitle')" :seo="['page' => 'events']">
    <x-page-header :title="__('site.events.title')" :subtitle="__('site.events.subtitle')"
                   :breadcrumbs="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __('nav.guide'), 'url' => localized_route('guide.index')], ['label' => __('nav.events')]]">
        <div role="group" aria-label="{{ __('site.list.view') }}" class="inline-flex rounded-button border border-line bg-white p-1">
            @foreach (['list' => 'list', 'calendar' => 'calendar'] as $option => $icon)
                <a href="{{ localized_route('events.index', $query(['view' => $option === 'calendar' ? 'calendar' : null, 'month' => $option === 'calendar' ? $month->format('Y-m') : null])) }}"
                   @if ($mode === $option) aria-current="page" @endif
                   class="inline-flex min-h-9 items-center gap-1.5 rounded-[9px] px-3 text-[13.5px] font-semibold text-secondary hover:text-navy aria-[current=page]:bg-navy aria-[current=page]:text-white">
                    <x-icon :name="$icon" :size="16" />{{ __('site.events.views.'.$option) }}
                </a>
            @endforeach
        </div>
    </x-page-header>

    <div class="container-page pt-6 pb-[clamp(56px,6vw,96px)]">
        <nav aria-label="{{ __('home.search.fields.category') }}" class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 scrollbar-none">
            <x-pill :active="$category === null" :href="localized_route('events.index', $query(['category' => null]))">{{ __('site.filters.all_categories') }}</x-pill>
            @foreach ($categories as $item)
                <x-pill :active="$category?->is($item)" :href="localized_route('events.index', $query(['category' => $item->slug]))">{{ $item->name }}</x-pill>
            @endforeach
        </nav>

        @if ($mode === 'calendar')
            <div class="mt-6 rounded-card border border-line bg-white p-4 xs:p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <x-icon-button icon="chevron-left" :label="__('site.events.previous_month')" :href="localized_route('events.index', $query(['month' => $month->copy()->subMonth()->format('Y-m')]))" />
                    <h2 class="font-serif text-[22px] font-semibold text-navy">{{ $monthLabel }}</h2>
                    <x-icon-button icon="chevron-right" :label="__('site.events.next_month')" :href="localized_route('events.index', $query(['month' => $month->copy()->addMonth()->format('Y-m')]))" />
                </div>
                <div class="grid grid-cols-7 gap-1.5">
                    @foreach (__('time.weekdays_short') as $weekday)
                        <span class="pb-1 text-center text-[12px] font-semibold text-secondary">{{ $weekday }}</span>
                    @endforeach
                    @for ($i = 0; $i < $offset; $i++)
                        <span></span>
                    @endfor
                    @foreach ($calendar as $day)
                        <div @class([
                            'flex min-h-[64px] flex-col gap-1 rounded-tile border p-1.5 xs:min-h-[104px] xs:p-2',
                            'border-coral bg-coral-tint/40' => $day['today'],
                            'border-line' => ! $day['today'],
                            'opacity-55' => $day['past'],
                        ])>
                            <span class="text-[13px] font-semibold text-navy">{{ $day['day'] }}</span>
                            @foreach (array_slice($day['events'], 0, 3) as $event)
                                <a href="{{ $event['url'] }}" class="truncate rounded-[6px] bg-navy px-1.5 py-0.5 text-[11.5px] font-medium text-white hover:bg-coral-pressed max-xs:size-2 max-xs:p-0 max-xs:text-transparent" title="{{ $event['title'] }}">
                                    @if ($event['time'])<span class="opacity-75">{{ $event['time'] }}</span> @endif{{ $event['title'] }}
                                </a>
                            @endforeach
                            @if (count($day['events']) > 3)
                                <span class="text-[11px] text-secondary">+{{ count($day['events']) - 3 }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @if ($monthCards->isNotEmpty())
                <h2 class="mt-10 mb-4 font-serif text-[22px] font-semibold text-navy">{{ __('site.events.this_month') }}</h2>
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,300px),1fr))] gap-5">
                    @foreach ($monthCards as $event)
                        <x-card.event :tag="$event['tag']" :title="$event['title']" :day="$event['day']" :month="$event['month']" :when="$event['when']" :venue="$event['venue']" :price="$event['price']" :url="$event['url']" :image="$event['image']" />
                    @endforeach
                </div>
            @endif
        @elseif ($cards->isEmpty())
            <x-empty-state class="mt-8" icon="calendar" :title="__('site.events.none')" :text="__('site.events.none_text')" />
        @else
            <div class="mt-6 grid grid-cols-[repeat(auto-fill,minmax(min(100%,300px),1fr))] gap-5">
                @foreach ($cards as $event)
                    <x-card.event :tag="$event['tag']" :title="$event['title']" :day="$event['day']" :month="$event['month']" :when="$event['when']" :venue="$event['venue']" :price="$event['price']" :url="$event['url']" :image="$event['image']" />
                @endforeach
            </div>
            <x-pagination :paginator="$list" class="mt-8" />
        @endif
    </div>
</x-layout.app>
