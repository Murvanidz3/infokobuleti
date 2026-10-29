<x-layout.app :title="$title" :description="$subtitle" :seo="['page' => 'real_estate']">
    <x-page-header :title="$title" :subtitle="$subtitle" :breadcrumbs="$breadcrumbs" />
    <livewire:listings.property-index />
</x-layout.app>
