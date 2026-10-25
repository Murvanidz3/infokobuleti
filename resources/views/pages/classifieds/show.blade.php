<x-layout.app :title="$classified->title" :description="$description">
    @if ($preview)
        <x-detail.preview-banner :status="$classified->status->getLabel()" />
    @endif

    <div class="container-page pt-5 pb-[clamp(56px,6vw,96px)]">
        @php
            $crumbs = [['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __('nav.classifieds'), 'url' => localized_route('classifieds.index')]];
            if ($classified->category?->parent) {
                $crumbs[] = ['label' => $classified->category->parent->name, 'url' => localized_route('classifieds.index', ['category' => $classified->category->parent->slug])];
            }
            if ($classified->category) {
                $crumbs[] = ['label' => $classified->category->name, 'url' => localized_route('classifieds.index', ['category' => $classified->category->slug])];
            }
            $crumbs[] = ['label' => $classified->title];
        @endphp
        <x-breadcrumbs :items="$crumbs" />

        <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1fr)_380px]">
            <div class="flex min-w-0 flex-col gap-8">
                <x-detail.gallery :images="$gallery" :title="$classified->title" />

                <div>
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <h1 class="min-w-0 font-serif text-[clamp(25px,2.6vw,34px)] leading-[1.25] font-semibold text-balance text-navy">{{ $classified->title }}</h1>
                        <x-detail.actions type="classified" :id="$classified->id" :title="$classified->title" :saved="$saved" />
                    </div>
                    <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[14px] text-secondary">
                        <span class="flex items-center gap-1.5"><x-icon name="pin" :size="16" class="text-navy" />{{ $address }}</span>
                        @if ($posted)
                            <span class="flex items-center gap-1.5"><x-icon name="clock" :size="16" class="text-navy" />{{ $posted->format('d.m.Y') }}</span>
                        @endif
                        <span class="flex items-center gap-1.5"><x-icon name="eye" :size="16" class="text-navy" />{{ $classified->views_count }}</span>
                    </p>
                </div>

                @if ($facts)
                    <x-detail.facts :facts="$facts" />
                @endif

                @if (filled($classified->description))
                    <x-detail.section :title="__('site.detail.description')">
                        <x-detail.text :text="$classified->description" />
                    </x-detail.section>
                @endif
            </div>

            <aside class="flex flex-col gap-5 lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-card border border-line bg-white p-5 shadow-card-soft">
                    <p class="text-[28px] leading-tight font-bold text-price">{{ $price }}</p>
                    @if ($classified->negotiable)
                        <p class="mt-1 text-[14px] text-secondary">{{ __('site.classifieds.negotiable') }}</p>
                    @endif
                </div>
                <x-detail.owner :owner="$owner" type="classified" :id="$classified->id" :has-phone="$hasPhone" :title="$classified->title" />
                <p class="flex gap-2.5 rounded-tile bg-surface-alt p-4 text-[13px] leading-[1.55] text-secondary"><x-icon name="shield" :size="18" class="shrink-0 text-navy" />{{ __('site.detail.safety') }}</p>
            </aside>
        </div>

        @if ($similar->isNotEmpty())
            <x-detail.section :title="__('site.detail.similar')" class="mt-14 border-t pt-10">
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,220px),1fr))] gap-4">
                    @foreach ($similar as $item)
                        <x-card.classified-tile :title="$item['title']" :meta="$item['meta']" :price="$item['price']" :time="$item['time']" :icon="$item['icon']" :is-new="$item['isNew']" :url="$item['url']" :image="$item['image']" :saved="$item['saved']" :favorite="$item['favorite']" />
                    @endforeach
                </div>
            </x-detail.section>
        @endif
    </div>
</x-layout.app>
