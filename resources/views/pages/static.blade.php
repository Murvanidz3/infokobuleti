<x-layout.app :title="$page->title" :description="$description">
    <x-page-header :title="$page->title" :breadcrumbs="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => $page->title]]" />
    <div class="container-page max-w-[840px] py-10 pb-[clamp(56px,6vw,96px)]">
        @if ($body !== '')
            <div class="prose-site">{!! $body !!}</div>
        @else
            <x-empty-state icon="file" :title="__('site.pages.empty_title')" :text="__('site.pages.empty_text')">
                <x-slot:action>
                    <x-button variant="secondary" :href="localized_route('contact')">{{ __('site.contact.title') }}</x-button>
                </x-slot:action>
            </x-empty-state>
        @endif
        <p class="mt-10 text-[13px] text-secondary">{{ __('site.pages.updated', ['date' => $page->updated_at?->format('d.m.Y')]) }}</p>
    </div>
</x-layout.app>
