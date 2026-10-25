<x-layout.app :title="$place->title" :description="$description">
    @if ($preview)
        <x-detail.preview-banner :status="$place->status->getLabel()" />
    @endif

    <div class="container-page pt-5 pb-[clamp(56px,6vw,96px)]">
        <x-breadcrumbs :items="[
            ['label' => __('nav.home'), 'url' => localized_route('home')],
            ['label' => __('nav.guide'), 'url' => localized_route('guide.index')],
            ['label' => $place->type->getLabel(), 'url' => localized_route('guide.index', ['type' => $place->type->value])],
            ['label' => $place->title],
        ]" />

        <div class="mt-4 mb-5 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <x-badge variant="tint" class="mb-2">{{ $place->type->getLabel() }}</x-badge>
                <h1 class="font-serif text-[clamp(28px,3vw,40px)] leading-[1.2] font-semibold text-balance text-navy">{{ $place->title }}</h1>
                <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[14.5px] text-secondary">
                    <span class="flex items-center gap-1.5"><x-icon name="pin" :size="16" class="text-navy" />{{ $address }}</span>
                    @if ($place->rating_avg)
                        <span class="flex items-center gap-1.5 font-semibold text-navy"><x-icon name="star" :size="16" class="fill-sun text-sun" />{{ number_format((float) $place->rating_avg, 1) }}</span>
                    @endif
                </p>
            </div>
            <x-detail.actions type="place" :id="$place->id" :title="$place->title" :saved="$saved" />
        </div>

        <x-detail.gallery :images="$gallery" :title="$place->title" />

        <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="flex min-w-0 flex-col gap-10">
                @if (filled($place->description) || $content !== '')
                    <x-detail.section :title="__('site.detail.about')">
                        <x-detail.text :text="$place->description" />
                        @if ($content !== '')
                            <div class="prose-site mt-4">{!! $content !!}</div>
                        @endif
                    </x-detail.section>
                @endif

                @if ($place->lat !== null)
                    <x-detail.section :title="__('site.detail.location')">
                        <x-detail.map :lat="$place->lat" :lng="$place->lng" :title="$place->title" :nearby="$nearby" :address="$address" />
                    </x-detail.section>
                @endif

                @if ($events->isNotEmpty())
                    <x-detail.section :title="__('site.events.upcoming')">
                        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,260px),1fr))] gap-5">
                            @foreach ($events as $event)
                                <x-card.event :tag="$event['tag']" :title="$event['title']" :day="$event['day']" :month="$event['month']" :when="$event['when']" :venue="$event['venue']" :price="$event['price']" :url="$event['url']" :image="$event['image']" />
                            @endforeach
                        </div>
                    </x-detail.section>
                @endif

                <x-detail.section :title="__('site.reviews.title')">
                    <x-detail.reviews :reviews="$reviews" :rating="$place->rating_avg ? number_format((float) $place->rating_avg, 1) : null" :count="$place->reviews_count" />
                </x-detail.section>
            </div>

            <aside class="flex flex-col gap-5 lg:sticky lg:top-24 lg:self-start">
                <x-detail.facts :facts="$facts" class="!grid-cols-2 lg:!grid-cols-1" />
                @if ($hours)
                    <div class="rounded-card border border-line bg-white p-5">
                        <h2 class="mb-3 flex items-center gap-2 text-[15px] font-semibold text-navy"><x-icon name="clock" :size="18" />{{ __('site.guide.hours') }}</h2>
                        <dl class="flex flex-col gap-1.5 text-[14px]">
                            @foreach ($hours as $row)
                                <div @class(['flex justify-between gap-4', 'font-semibold text-navy' => $row['today'], 'text-secondary' => ! $row['today']])>
                                    <dt>{{ $row['day'] }}</dt>
                                    <dd>{{ $row['text'] ?? '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
                @if ($hasPhone || $place->website)
                    <div class="flex flex-col gap-2.5 rounded-card border border-line bg-white p-5">
                        @if ($hasPhone)
                            <div x-data="phoneReveal('place', {{ $place->id }})">
                                <template x-if="!phone"><x-button class="w-full" icon="phone" x-on:click="reveal()">{{ __('site.owner.show_phone') }}</x-button></template>
                                <template x-if="phone"><a x-bind:href="tel" class="inline-flex min-h-11 w-full items-center justify-center gap-2.5 rounded-button bg-navy px-5 font-semibold text-white"><x-icon name="phone" :size="18" /><span x-text="phone"></span></a></template>
                            </div>
                        @endif
                        @if ($place->website)
                            <x-button variant="secondary" :href="$place->website" icon="globe" target="_blank" rel="noopener nofollow">{{ __('site.guide.website') }}</x-button>
                        @endif
                    </div>
                @endif
            </aside>
        </div>

        @if ($similar->isNotEmpty())
            <x-detail.section :title="__('site.guide.more', ['type' => $place->type->getLabel()])" class="mt-14 border-t pt-10">
                <div class="grid grid-cols-1 gap-5 xs:grid-cols-2 lg:grid-cols-4">
                    @foreach ($similar as $item)
                        <x-card.guide class="min-h-[260px]" :tag="$item['tag']" :title="$item['title']" :meta="$item['meta']" :url="$item['url']" :image="$item['image']" />
                    @endforeach
                </div>
            </x-detail.section>
        @endif
    </div>
</x-layout.app>
