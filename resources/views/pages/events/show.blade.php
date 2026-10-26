<x-layout.app :title="$event->title" :description="$description" :seo="$seo" :noindex="$preview">
    @if ($preview)
        <x-detail.preview-banner :status="$event->status->getLabel()" />
    @endif

    <div class="container-page pt-5 pb-[clamp(56px,6vw,96px)]">
        <x-breadcrumbs :items="[
            ['label' => __('nav.home'), 'url' => localized_route('home')],
            ['label' => __('nav.events'), 'url' => localized_route('events.index')],
            ['label' => $event->title],
        ]" />

        <div class="mt-4 mb-5 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <x-badge variant="tint" class="mb-2">{{ $card['tag'] }}</x-badge>
                <h1 class="font-serif text-[clamp(28px,3vw,40px)] leading-[1.2] font-semibold text-balance text-navy">{{ $event->title }}</h1>
            </div>
            <x-detail.actions type="event" :id="$event->id" :title="$event->title" :saved="$saved" />
        </div>

        <x-detail.gallery :images="$gallery" :title="$event->title" />

        <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="flex min-w-0 flex-col gap-10">
                @if ($body !== '')
                    <x-detail.section :title="__('site.detail.about')">
                        <div class="prose-site">{!! $body !!}</div>
                    </x-detail.section>
                @endif
                @if ($event->lat !== null)
                    <x-detail.section :title="__('site.detail.location')">
                        <x-detail.map :lat="$event->lat" :lng="$event->lng" :title="$event->title" :nearby="$nearby" :address="$card['venue']" />
                    </x-detail.section>
                @endif
            </div>
            <aside class="flex flex-col gap-5 lg:sticky lg:top-24 lg:self-start">
                <div class="flex flex-col gap-4 rounded-card border border-line bg-white p-5 shadow-card-soft">
                    <p class="flex items-start gap-3 text-[15px] text-navy"><x-icon name="calendar" :size="20" class="mt-0.5 shrink-0 text-coral" /><span class="font-semibold">{{ $card['when'] }}@if ($event->ends_at && ! $event->ends_at->isSameDay($event->starts_at)) – {{ $event->ends_at->format('d.m') }}@endif</span></p>
                    <p class="flex items-start gap-3 text-[15px] text-navy"><x-icon name="pin" :size="20" class="mt-0.5 shrink-0 text-coral" />{{ $card['venue'] }}</p>
                    @if ($card['price'])
                        <p class="flex items-start gap-3 text-[15px] text-navy"><x-icon name="ticket" :size="20" class="mt-0.5 shrink-0 text-coral" />{{ $card['price'] }}</p>
                    @endif
                    @if ($event->organizer)
                        <p class="flex items-start gap-3 text-[15px] text-navy"><x-icon name="users" :size="20" class="mt-0.5 shrink-0 text-coral" />{{ $event->organizer }}</p>
                    @endif
                    <div class="flex flex-col gap-2.5 border-t border-line pt-4">
                        @if ($event->ticket_url)
                            <x-button :href="$event->ticket_url" icon="ticket" target="_blank" rel="noopener nofollow">{{ __('site.events.tickets') }}</x-button>
                        @endif
                        <x-button variant="secondary" :href="$calendarUrl" icon="calendar" target="_blank" rel="noopener">{{ __('site.events.add_to_calendar') }}</x-button>
                    </div>
                </div>
                @if ($event->place)
                    <a href="{{ localized_route('guide.show', [$event->place]) }}" class="flex items-center justify-between gap-3 rounded-card border border-line bg-white p-4 text-[14.5px] font-semibold text-navy hover:border-line-strong">
                        <span class="flex items-center gap-2"><x-icon name="compass" :size="18" />{{ $event->place->title }}</span><x-icon name="arrow-right" :size="16" />
                    </a>
                @endif
            </aside>
        </div>

        @if ($more->isNotEmpty())
            <x-detail.section :title="__('site.events.more')" class="mt-14 border-t pt-10">
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-5">
                    @foreach ($more as $item)
                        <x-card.event :tag="$item['tag']" :title="$item['title']" :day="$item['day']" :month="$item['month']" :when="$item['when']" :venue="$item['venue']" :price="$item['price']" :url="$item['url']" :image="$item['image']" />
                    @endforeach
                </div>
            </x-detail.section>
        @endif
    </div>
</x-layout.app>
