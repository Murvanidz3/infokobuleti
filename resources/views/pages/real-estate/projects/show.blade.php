<x-layout.app :title="$project->title" :description="$description" :seo="$seo" :noindex="$preview">
    @if ($preview)
        <x-detail.preview-banner :status="$project->status->getLabel()" />
    @endif

    <div class="container-page pt-5 pb-[clamp(56px,6vw,96px)]">
        <x-breadcrumbs :items="[
            ['label' => __('nav.home'), 'url' => localized_route('home')],
            ['label' => __('nav.real_estate'), 'url' => localized_route('real-estate.index')],
            ['label' => __('nav.projects'), 'url' => localized_route('projects.index')],
            ['label' => $project->title],
        ]" />

        <div class="mt-4 mb-5 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <x-badge variant="navy">{{ __('site.projects.badge') }}</x-badge>
                </div>
                <h1 class="font-serif text-[clamp(26px,2.8vw,38px)] leading-[1.22] font-semibold text-balance text-navy">{{ $project->title }}</h1>
                <p class="mt-2 flex items-center gap-1.5 text-[14.5px] text-secondary"><x-icon name="pin" :size="16" class="text-navy" />{{ $card['location'] }}</p>
            </div>
            <x-detail.actions type="project" :id="$project->id" :title="$project->title" :saved="$saved" />
        </div>

        <x-detail.gallery :images="$gallery" :title="$project->title" />

        <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_380px]">
            <div class="flex min-w-0 flex-col gap-10">
                <x-detail.facts :facts="$facts" />

                @if (filled($project->description))
                    <x-detail.section :title="__('site.detail.description')">
                        <x-detail.text :text="$project->description" />
                    </x-detail.section>
                @endif

                @if ($project->lat !== null)
                    <x-detail.section :title="__('site.detail.location')">
                        <x-detail.map :lat="$project->lat" :lng="$project->lng" :title="$project->title" :address="$card['location']" />
                    </x-detail.section>
                @endif
            </div>

            <aside class="flex flex-col gap-5 lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-card border border-line bg-white p-5 shadow-card-soft">
                    <p class="text-[13px] font-semibold text-secondary">{{ __('site.projects.price_from_label') }}</p>
                    <p class="mt-1 text-[28px] leading-tight font-bold text-price">{{ $card['price'] }}</p>
                    @if ($card['converted'])
                        <p class="mt-1 text-[14px] text-secondary">{{ $card['converted'] }}</p>
                    @endif
                    <p class="mt-3 text-[13px] text-secondary">{{ __('site.detail.published', ['date' => $project->published_at?->format('d.m.Y') ?? '—']) }} · {{ __('site.detail.views', ['count' => $project->views_count]) }}</p>
                </div>
                <x-detail.owner :owner="$owner" type="project" :id="$project->id" :has-phone="$hasPhone" :title="$project->title" />
                <p class="flex gap-2.5 rounded-tile bg-surface-alt p-4 text-[13px] leading-[1.55] text-secondary"><x-icon name="shield" :size="18" class="shrink-0 text-navy" />{{ __('site.detail.safety') }}</p>
            </aside>
        </div>

        @if ($similar->isNotEmpty())
            <x-detail.section :title="__('site.detail.similar')" class="mt-14 border-t pt-10">
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,270px),1fr))] gap-5">
                    @foreach ($similar as $item)
                        <x-card.property :badge="$item['badge']" :price="$item['price']" :converted="$item['converted']" :title="$item['title']" :location="$item['location']" :features="$item['features']" :url="$item['url']" :image="$item['image']" :saved="$item['saved']" :favorite="$item['favorite']" />
                    @endforeach
                </div>
            </x-detail.section>
        @endif
    </div>
</x-layout.app>
