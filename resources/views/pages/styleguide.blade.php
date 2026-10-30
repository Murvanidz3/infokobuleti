@php
    $colors = [
        'navy' => '#0a1a2f', 'navy-hover' => '#1b304d', 'secondary' => '#3a4a5f', 'line' => '#e8edf3',
        'line-strong' => '#c9d3df', 'surface-alt' => '#f5f7fa', 'white' => '#ffffff', 'coral' => '#d4602a',
        'coral-hover' => '#e06d36', 'coral-pressed' => '#bf5323', 'price' => '#b44f1e', 'coral-tint' => '#fcece2',
        'sea' => '#2f6f9f', 'tab-hotels' => '#2f6f9f', 'tab-realestate' => '#2e7d6b', 'tab-classifieds' => '#b5622f',
        'tab-jobs' => '#6a4f9e',
    ];
    $radii = ['chip' => 'rounded-chip', 'field' => 'rounded-field', 'button' => 'rounded-button', 'tile' => 'rounded-tile', 'card' => 'rounded-card', 'panel' => 'rounded-panel', 'pill' => 'rounded-full'];
    $shadows = ['card-soft' => 'shadow-card-soft', 'card-hover' => 'shadow-card-hover', 'feature-hover' => 'shadow-feature-hover', 'overlay' => 'shadow-overlay', 'cta' => 'shadow-cta', 'device' => 'shadow-device'];
    $sections = __('styleguide.sections');
    $heading = 'mb-6 font-serif text-[28px] font-semibold text-navy';
    $caption = 'mb-3 text-xs font-semibold tracking-[0.06em] text-secondary uppercase';
    $property = $properties[0];
    $breadcrumbs = [
        ['label' => __('nav.home'), 'url' => localized_route('home')],
        ['label' => __('nav.real_estate'), 'url' => '#'],
        ['label' => $property['title'], 'url' => null],
    ];
@endphp

<x-layout.app :noindex="true" :title="__('styleguide.title')">
    <x-section spacing="compact" background="alt" class="border-b border-line" :reveal="false">
        <x-breadcrumbs class="mb-4" :items="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __('styleguide.title'), 'url' => null]]" />
        <h1 class="font-serif text-[clamp(32px,4vw,48px)] leading-[1.15] font-semibold text-navy">{{ __('styleguide.title') }}</h1>
        <p class="mt-3 max-w-[640px] text-base leading-[1.6] text-secondary">{{ __('styleguide.intro') }}</p>
        <nav class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('styleguide.title') }}">
            @foreach ($sections as $key => $label)
                <x-pill :href="'#sg-'.$key">{{ $label }}</x-pill>
            @endforeach
        </nav>
    </x-section>

    {{-- Tokens --}}
    <x-section spacing="compact" id="sg-colors">
        <h2 class="{{ $heading }}">{{ $sections['colors'] }}</h2>
        <ul class="grid grid-cols-2 gap-4 xs:grid-cols-3 lg:grid-cols-6">
            @foreach ($colors as $token => $hex)
                <li class="overflow-hidden rounded-tile border border-line bg-white">
                    <div class="h-20 border-b border-line" style="background: {{ $hex }}"></div>
                    <div class="px-3 py-2.5">
                        <p class="text-sm font-semibold text-navy">{{ $token }}</p>
                        <p class="text-xs text-secondary">{{ $hex }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-section>

    <x-section spacing="compact" background="alt" id="sg-typography">
        <h2 class="{{ $heading }}">{{ $sections['typography'] }}</h2>
        <div class="flex flex-col gap-6 rounded-card border border-line bg-white p-6 md:p-8">
            <div>
                <p class="{{ $caption }}">Noto Serif Georgian 600 · clamp(36px, 5.2vw, 72px)</p>
                <p class="font-serif text-[clamp(36px,5.2vw,72px)] leading-[1.08] font-semibold text-navy">{{ __('styleguide.type.display') }}</p>
            </div>
            <div>
                <p class="{{ $caption }}">Noto Serif Georgian 600 · clamp(28px, 2.6vw, 40px)</p>
                <p class="font-serif text-[clamp(28px,2.6vw,40px)] leading-[1.2] font-semibold text-navy">{{ __('styleguide.type.heading') }}</p>
            </div>
            <div>
                <p class="{{ $caption }}">Noto Sans Georgian 600 · 17px</p>
                <p class="text-[17px] font-semibold text-navy">{{ __('styleguide.type.title') }}</p>
            </div>
            <div>
                <p class="{{ $caption }}">Noto Sans Georgian 400 · 16px / 1.6</p>
                <p class="text-base leading-[1.6] text-secondary">{{ __('styleguide.type.body') }}</p>
            </div>
            <div>
                <p class="{{ $caption }}">Noto Sans Georgian 400 · 13px</p>
                <p class="text-[13px] text-secondary">{{ __('styleguide.type.small') }}</p>
            </div>
            <div>
                <p class="{{ $caption }}">Outfit 300 / 700</p>
                <div class="flex flex-wrap items-center gap-4">
                    <div class="rounded-tile bg-navy px-5 py-4"><x-logo /></div>
                    <div class="rounded-tile bg-navy px-5 py-4"><x-logo size="footer" /></div>
                </div>
            </div>
        </div>
    </x-section>

    <x-section spacing="compact" id="sg-shape">
        <h2 class="{{ $heading }}">{{ $sections['shape'] }}</h2>
        <div class="grid gap-8 lg:grid-cols-2">
            <ul class="grid grid-cols-4 gap-4 xs:grid-cols-7 lg:grid-cols-4">
                @foreach ($radii as $name => $class)
                    <li class="text-center">
                        <div class="aspect-square border-2 border-navy bg-surface-alt {{ $class }}"></div>
                        <p class="mt-2 text-xs font-medium text-secondary">{{ $name }}</p>
                    </li>
                @endforeach
            </ul>
            <ul class="grid grid-cols-2 gap-6 xs:grid-cols-3">
                @foreach ($shadows as $name => $class)
                    <li class="text-center">
                        <div class="h-20 rounded-card bg-white {{ $class }}"></div>
                        <p class="mt-3 text-xs font-medium text-secondary">{{ $name }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-section>

    <x-section spacing="compact" background="alt" id="sg-icons">
        <h2 class="{{ $heading }}">{{ $sections['icons'] }}</h2>
        <ul class="grid grid-cols-3 gap-3 xs:grid-cols-6 lg:grid-cols-10">
            @foreach (array_keys(\App\View\Components\Icon::PATHS) as $icon)
                <li class="flex flex-col items-center gap-2 rounded-tile border border-line bg-white px-2 py-4 text-navy">
                    <x-icon :name="$icon" :size="22" />
                    <span class="text-[11px] text-secondary">{{ $icon }}</span>
                </li>
            @endforeach
        </ul>
    </x-section>

    {{-- Actions --}}
    <x-section spacing="compact" id="sg-buttons">
        <h2 class="{{ $heading }}">{{ $sections['buttons'] }}</h2>
        <div class="flex flex-col gap-6">
            @foreach (['primary', 'secondary', 'tertiary', 'ghost'] as $variant)
                <div>
                    <p class="{{ $caption }}">{{ $variant }}</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-button :variant="$variant" size="sm">{{ __('styleguide.buttons.small') }}</x-button>
                        <x-button :variant="$variant">{{ __('styleguide.buttons.medium') }}</x-button>
                        <x-button :variant="$variant" size="lg" icon-right="arrow-right">{{ __('styleguide.buttons.large') }}</x-button>
                        <x-button :variant="$variant" icon="plus">{{ __('styleguide.buttons.'.$variant) }}</x-button>
                        <x-button :variant="$variant" disabled>{{ __('styleguide.buttons.disabled') }}</x-button>
                    </div>
                </div>
            @endforeach
            <div>
                <p class="{{ $caption }}">icon-button · add-listing · link-arrow</p>
                <div class="flex flex-wrap items-center gap-4">
                    <x-icon-button icon="chevron-left" :label="__('ui.previous')" />
                    <x-icon-button icon="chevron-right" :label="__('ui.next')" />
                    <x-icon-button icon="filter" :label="__('styleguide.overlays.sheet_title')" variant="ghost" />
                    <div class="flex items-center gap-4 rounded-tile bg-navy p-3">
                        <x-icon-button icon="menu" :label="__('nav.menu')" variant="inverse" />
                        <x-add-listing-button />
                        <x-add-listing-button size="lg" />
                    </div>
                    <x-link-arrow href="#">{{ __('ui.view_all') }}</x-link-arrow>
                    <x-link-arrow href="#" size="sm">{{ __('ui.view_all') }}</x-link-arrow>
                </div>
            </div>
        </div>
    </x-section>

    <x-section spacing="compact" background="alt" id="sg-badges">
        <h2 class="{{ $heading }}">{{ $sections['badges'] }}</h2>
        <div class="flex flex-col gap-6">
            <div class="flex flex-wrap items-center gap-3">
                <x-badge variant="navy">{{ __('listing.deal.sale') }}</x-badge>
                <x-badge variant="new">{{ __('ui.new') }}</x-badge>
                <x-badge variant="tint">{{ $news[0]['tag'] }}</x-badge>
                <x-badge variant="outline">{{ __('listing.employment.seasonal') }}</x-badge>
                <x-badge variant="outline-tint">{{ __('listing.employment.full_time') }}</x-badge>
                <span class="rounded-tile bg-navy p-3"><x-badge variant="glass">{{ $guide[0]['tag'] }}</x-badge></span>
                <span class="inline-flex rounded-chip bg-navy px-2 py-1 text-[13px] font-semibold text-white">9.4</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-pill active>{{ __('home.real_estate.filters.all') }}</x-pill>
                <x-pill>{{ __('home.real_estate.filters.sale') }}</x-pill>
                <x-pill>{{ __('home.real_estate.filters.rent') }}</x-pill>
                <div x-data="segmented('all')">
                    <x-segmented :options="__('home.real_estate.filters')" :label="__('home.real_estate.filter_label')" selected="all" />
                </div>
                <div class="grid size-14 place-items-center rounded-tile bg-line">
                    <x-favorite-button class="relative" />
                </div>
                <div class="grid size-14 place-items-center rounded-tile bg-line">
                    <x-favorite-button class="relative" :saved="true" />
                </div>
            </div>
        </div>
    </x-section>

    <x-section spacing="compact" id="sg-headers">
        <h2 class="{{ $heading }}">{{ $sections['headers'] }}</h2>
        <div class="flex flex-col gap-10">
            <x-section-header :title="__('home.real_estate.title')" :subtitle="__('home.real_estate.subtitle')" href="#" :link-label="__('home.real_estate.all')" />
            <x-section-header size="sm" :title="__('home.classifieds.title')" href="#" />
        </div>
    </x-section>

    {{-- Cards --}}
    <x-section spacing="compact" background="alt" id="sg-cards">
        <h2 class="{{ $heading }}">{{ $sections['cards'] }}</h2>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-6">
            <x-card.property :badge="$property['badge']" :price="$property['price']" :converted="$property['converted']" :title="$property['title']" :location="$property['location']" :features="$property['features']" :url="$property['url']" :image="$property['image']" :saved="$property['saved']" />
            <x-card.news :tag="$news[0]['tag']" :date="$news[0]['date']" :title="$news[0]['title']" :url="$news[0]['url']" :image="$news[0]['image']" />
        </div>
    </x-section>

    <x-section spacing="compact" id="sg-lists">
        <h2 class="{{ $heading }}">{{ $sections['lists'] }}</h2>
        <div class="grid gap-8 lg:grid-cols-2">
            <x-list-panel>
                @foreach (array_slice($classifieds, 0, 3) as $classified)
                    <x-card.classified :title="$classified['title']" :meta="$classified['meta']" :price="$classified['price']" :time="$classified['time']" :icon="$classified['icon']" :is-new="$classified['isNew']" :url="$classified['url']" />
                @endforeach
            </x-list-panel>
            <x-list-panel>
                @foreach (array_slice($jobs, 0, 2) as $job)
                    <x-card.job :title="$job['title']" :employer="$job['employer']" :salary="$job['salary']" :period="$job['period']" :type="$job['type']" :seasonal="$job['seasonal']" :posted="$job['posted']" :url="$job['url']" />
                @endforeach
            </x-list-panel>
        </div>
    </x-section>

    <x-section spacing="compact" background="alt" id="sg-guide">
        <h2 class="{{ $heading }}">{{ $sections['guide'] }}</h2>
        <div class="grid gap-5 md:grid-cols-[1.3fr_1fr]">
            <x-card.guide feature class="min-h-[440px]" :tag="$guide[0]['tag']" :title="$guide[0]['title']" :description="$guide[0]['description']" :meta="$guide[0]['meta']" :url="$guide[0]['url']" :image="$guide[0]['image']" />
            <x-card.guide class="min-h-[280px]" :tag="$guide[1]['tag']" :title="$guide[1]['title']" :meta="$guide[1]['meta']" :url="$guide[1]['url']" :image="$guide[1]['image']" />
        </div>
    </x-section>

    <x-section spacing="compact" id="sg-widgets">
        <h2 class="{{ $heading }}">{{ $sections['widgets'] }}</h2>
        <div class="flex flex-col gap-6">
            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,250px),1fr))] gap-4">
                <x-info-widget icon="sun" :label="__('home.info.weather')">
                    <div class="flex items-end gap-5">
                        <x-info-widget.stat size="xl" :value="$info['weather']['air']" :caption="$info['weather']['condition']" />
                        <x-info-widget.stat size="xl" tone="sea" class="border-l border-line pl-5" :value="$info['weather']['sea']" :caption="__('home.info.sea_water')" />
                    </div>
                </x-info-widget>
                <x-info-widget icon="plane" :label="__('home.info.airport')">
                    <x-info-widget.stat :value="$info['airport']['distance']" :caption="$info['airport']['note']" />
                </x-info-widget>
            </div>
            <ul class="grid grid-cols-2 gap-3.5 xs:grid-cols-4">
                @foreach (array_slice($categories, 0, 4) as $category)
                    <li><x-category-tile :name="$category['name']" :count="$category['count']" :icon="$category['icon']" :url="$category['url']" /></li>
                @endforeach
            </ul>
        </div>
    </x-section>

    <x-section spacing="compact" background="alt" id="sg-carousel">
        <div x-data="carousel">
            <x-section-header :title="$sections['carousel']" href="#" :link-label="__('home.real_estate.title')">
                <x-slot:actions>
                    <x-carousel.arrows />
                </x-slot:actions>
            </x-section-header>
            <x-carousel :label="$sections['carousel']">
                @foreach ($properties as $item)
                    <x-carousel.item>
                        <x-card.property class="h-full" :badge="$item['badge']" :price="$item['price']" :converted="$item['converted']" :title="$item['title']" :location="$item['location']" :features="$item['features']" :url="$item['url']" :image="$item['image']" :saved="$item['saved']" />
                    </x-carousel.item>
                @endforeach
            </x-carousel>
        </div>
    </x-section>

    <section id="sg-hero" aria-label="{{ $sections['hero'] }}">
        <x-hero />
    </section>

    {{-- Forms --}}
    <x-section spacing="compact" id="sg-forms">
        <h2 class="{{ $heading }}">{{ $sections['forms'] }}</h2>
        <form action="#" method="get" class="grid gap-6 md:grid-cols-2" x-on:submit.prevent>
            <x-form.field :label="__('styleguide.forms.name')" name="sg_name">
                <x-form.input name="sg_name" :placeholder="__('styleguide.forms.name_placeholder')" />
            </x-form.field>
            <x-form.field :label="__('styleguide.forms.phone')" name="sg_phone" :hint="__('styleguide.forms.phone_hint')" optional>
                <x-form.input name="sg_phone" type="tel" icon="phone" placeholder="+995 5XX XX XX XX" aria-describedby="sg_phone-hint" />
            </x-form.field>
            <x-form.field :label="__('styleguide.forms.email')" name="sg_email" :error="__('styleguide.forms.email_error')">
                <x-form.input name="sg_email" type="email" icon="mail" value="nino@" :invalid="true" />
            </x-form.field>
            <x-form.field :label="__('styleguide.forms.category')" name="sg_category">
                <x-form.select name="sg_category" icon="grid" :options="__('home.search.options.classified_categories')" />
            </x-form.field>
            <x-form.field :label="__('styleguide.forms.dates')" for="sg_from">
                <x-form.date-range from="sg_from" to="sg_to" :label="__('styleguide.forms.dates')" />
            </x-form.field>
            <x-form.field :label="__('styleguide.forms.price')" for="sg_price_from">
                <x-form.number-range from="sg_price_from" to="sg_price_to" prefix="₾" :step="100" :label="__('styleguide.forms.price')" />
            </x-form.field>
            <x-form.field :label="__('styleguide.forms.deal')">
                <x-form.radio-pills name="sg_deal" :options="__('home.search.deal')" selected="buy" :label="__('styleguide.forms.deal')" />
            </x-form.field>
            <x-form.field :label="__('styleguide.forms.amenities')">
                <div class="flex flex-wrap gap-x-6">
                    @foreach (__('styleguide.forms.amenity_options') as $value => $text)
                        <x-form.checkbox name="sg_amenities[]" :value="$value" :checked="$loop->first">{{ $text }}</x-form.checkbox>
                    @endforeach
                </div>
            </x-form.field>
            <x-form.field :label="__('styleguide.forms.photos')" for="sg_photos" class="md:col-span-2">
                <x-form.dropzone name="sg_photos" />
            </x-form.field>
        </form>
    </x-section>

    <x-section spacing="compact" background="alt" id="sg-navigation">
        <h2 class="{{ $heading }}">{{ $sections['navigation'] }}</h2>
        <div class="flex flex-col gap-8">
            <x-breadcrumbs :items="$breadcrumbs" />
            <div class="rounded-tile bg-navy p-4"><x-breadcrumbs :items="$breadcrumbs" tone="dark" /></div>
            <x-pagination :paginator="$paginator" />
            <div class="flex flex-wrap items-center gap-4 rounded-tile bg-navy p-4">
                <x-language-switcher />
                <x-language-switcher size="lg" />
            </div>
        </div>
    </x-section>

    <x-section spacing="compact" id="sg-feedback">
        <h2 class="{{ $heading }}">{{ $sections['feedback'] }}</h2>
        <div class="grid gap-6 lg:grid-cols-2">
            <x-empty-state>
                <x-slot:action>
                    <x-button variant="secondary" href="#">{{ __('ui.view_all') }}</x-button>
                </x-slot:action>
            </x-empty-state>
            <div class="grid gap-6 xs:grid-cols-2">
                <x-skeleton variant="card" />
                <div class="flex flex-col gap-6">
                    <x-skeleton />
                    <div class="rounded-card border border-line bg-white"><x-skeleton variant="list-item" /></div>
                    <x-skeleton variant="block" class="flex-1" />
                </div>
            </div>
        </div>
    </x-section>

    <x-section spacing="compact" background="alt" id="sg-overlays">
        <h2 class="{{ $heading }}">{{ $sections['overlays'] }}</h2>
        <div class="flex flex-wrap gap-3">
            <x-button variant="tertiary" x-data x-on:click="$dispatch('open-dialog', 'sg-modal')">{{ __('styleguide.overlays.open_modal') }}</x-button>
            <x-button variant="tertiary" x-data x-on:click="$dispatch('open-dialog', 'sg-sheet')">{{ __('styleguide.overlays.open_sheet') }}</x-button>
            @foreach (['success', 'info', 'error'] as $type)
                <x-button variant="secondary" x-data x-on:click="$dispatch('toast', { message: {{ Js::from(__('styleguide.overlays.toast_'.$type.'_message')) }}, type: '{{ $type }}' })">{{ __('styleguide.overlays.toast_'.$type) }}</x-button>
            @endforeach
        </div>

        <x-modal name="sg-modal" :title="__('styleguide.overlays.modal_title')" size="sm">
            <p>{{ __('styleguide.overlays.modal_text') }}</p>
            <x-slot:footer>
                <x-button variant="ghost" x-on:click="$dispatch('close-dialog', 'sg-modal')">{{ __('styleguide.overlays.cancel') }}</x-button>
                <x-button x-on:click="$dispatch('close-dialog', 'sg-modal')">{{ __('styleguide.overlays.confirm') }}</x-button>
            </x-slot:footer>
        </x-modal>

        <x-bottom-sheet name="sg-sheet" :title="__('styleguide.overlays.sheet_title')">
            <div class="flex flex-col gap-5">
                <x-form.field :label="__('styleguide.forms.deal')">
                    <x-form.radio-pills name="sheet_deal" :options="__('home.search.deal')" selected="rent" :label="__('styleguide.forms.deal')" />
                </x-form.field>
                <x-form.field :label="__('styleguide.forms.price')" for="sheet_price_from">
                    <x-form.number-range from="sheet_price_from" to="sheet_price_to" prefix="₾" :label="__('styleguide.forms.price')" />
                </x-form.field>
            </div>
            <x-slot:footer>
                <x-button variant="ghost" class="flex-1" x-on:click="$dispatch('close-dialog', 'sg-sheet')">{{ __('styleguide.overlays.cancel') }}</x-button>
                <x-button class="flex-1" x-on:click="$dispatch('close-dialog', 'sg-sheet')">{{ __('styleguide.overlays.apply') }}</x-button>
            </x-slot:footer>
        </x-bottom-sheet>
    </x-section>
</x-layout.app>
