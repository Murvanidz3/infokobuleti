<x-layout.app :title="$profile->name" :description="$description" :seo="$seo">
    <div class="border-b border-line bg-surface-alt">
        <div class="container-page py-[clamp(24px,3.5vw,44px)]">
            <x-breadcrumbs :items="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => $profile->name]]" class="mb-5" />
            <div class="flex flex-wrap items-center gap-5">
                <span class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-card border border-line bg-white text-[30px] font-semibold text-navy">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="{{ $profile->name }}" class="size-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr($profile->name, 0, 1)) }}
                    @endif
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="font-serif text-[clamp(26px,3vw,38px)] leading-[1.2] font-semibold text-navy">{{ $profile->name }}</h1>
                        @if ($profile->isVerified())
                            <x-badge variant="tint" class="gap-1"><x-icon name="check-circle" :size="14" class="text-sea" />{{ __('site.owner.verified_business') }}</x-badge>
                        @endif
                    </div>
                    <p class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-[14.5px] text-secondary">
                        <span>{{ $profile->type->getLabel() }}</span>
                        <span class="flex items-center gap-1.5"><x-icon name="pin" :size="16" class="text-navy" />{{ $address }}</span>
                        @if ($profile->rating_avg)
                            <span class="flex items-center gap-1.5 font-semibold text-navy"><x-icon name="star" :size="16" class="fill-sun text-sun" />{{ number_format((float) $profile->rating_avg, 1) }} <span class="font-normal text-secondary">· {{ trans_choice('ui.counts.reviews', $profile->reviews_count, ['count' => $profile->reviews_count]) }}</span></span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container-page grid gap-10 py-10 pb-[clamp(56px,6vw,96px)] lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="flex min-w-0 flex-col gap-12">
            @if (filled($profile->description))
                <x-detail.section :title="__('site.business.about')">
                    <x-detail.text :text="$profile->description" />
                </x-detail.section>
            @endif

            @foreach ($sections as $module => $items)
                <x-detail.section :title="__('site.business.sections.'.$module)">
                    @switch($module)
                        @case('hotels')
                            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,260px),1fr))] gap-5">
                                @foreach ($items as $item)
                                    <x-card.hotel :name="$item['name']" :address="$item['address']" :sea="$item['sea']" :rating="$item['rating']" :reviews="$item['reviews']" :price="$item['price']" :url="$item['url']" :image="$item['image']" :saved="$item['saved']" :favorite="$item['favorite']" :promotion="$item['promotion'] ?? null" :logo="$item['logo'] ?? null" />
                                @endforeach
                            </div>
                            @break
                        @case('properties')
                            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,270px),1fr))] gap-5">
                                @foreach ($items as $item)
                                    <x-card.property :badge="$item['badge']" :price="$item['price']" :converted="$item['converted']" :title="$item['title']" :location="$item['location']" :features="$item['features']" :url="$item['url']" :image="$item['image']" :saved="$item['saved']" :favorite="$item['favorite']" :promotion="$item['promotion'] ?? null" :logo="$item['logo'] ?? null" :owner-verified="$item['ownerVerified'] ?? false" />
                                @endforeach
                            </div>
                            @break
                        @case('vacancies')
                            <x-list-panel>
                                @foreach ($items as $job)
                                    <x-card.job :title="$job['title']" :employer="$job['employer']" :salary="$job['salary']" :period="$job['period']" :type="$job['type']" :seasonal="$job['seasonal']" :posted="$job['posted']" :url="$job['url']" :promotion="$job['promotion'] ?? null" :logo="$job['logo'] ?? null" />
                                @endforeach
                            </x-list-panel>
                            @break
                        @case('places')
                            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,260px),1fr))] gap-5">
                                @foreach ($items as $place)
                                    <x-card.guide class="min-h-[260px]" :tag="$place['tag']" :title="$place['title']" :meta="$place['meta']" :url="$place['url']" :image="$place['image']" />
                                @endforeach
                            </div>
                            @break
                    @endswitch
                </x-detail.section>
            @endforeach

            @if (! $sections)
                <x-empty-state icon="inbox" :title="__('site.business.no_listings')" :text="__('site.business.no_listings_text')" />
            @endif

            <x-detail.section :title="__('site.reviews.title')">
                <x-detail.reviews :reviews="$reviews" />
            </x-detail.section>
        </div>

        <aside class="flex flex-col gap-5 lg:sticky lg:top-24 lg:self-start">
            <x-detail.owner :owner="$owner" type="business_profile" :id="$profile->id" :has-phone="filled($profile->phone)" :title="$profile->name">
                @if ($profile->website || $profile->email || $socials)
                    <ul class="mt-5 flex flex-col gap-2.5 border-t border-line pt-4 text-[14px]">
                        @if ($profile->website)
                            <li><a href="{{ $profile->website }}" target="_blank" rel="noopener nofollow" class="flex items-center gap-2 text-navy hover:text-coral-pressed"><x-icon name="globe" :size="16" />{{ parse_url($profile->website, PHP_URL_HOST) ?: $profile->website }}</a></li>
                        @endif
                        @if ($profile->email)
                            <li><a href="mailto:{{ $profile->email }}" class="flex items-center gap-2 text-navy hover:text-coral-pressed"><x-icon name="mail" :size="16" />{{ $profile->email }}</a></li>
                        @endif
                        @foreach ($socials as $network => $url)
                            <li><a href="{{ $url }}" target="_blank" rel="noopener nofollow" class="flex items-center gap-2 text-navy hover:text-coral-pressed"><x-icon :name="\App\View\Components\Icon::has($network) ? $network : 'external'" :size="16" />{{ ucfirst($network) }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </x-detail.owner>
            @if ($profile->lat !== null)
                <x-detail.map :lat="$profile->lat" :lng="$profile->lng" :title="$profile->name" />
            @endif
        </aside>
    </div>
</x-layout.app>
