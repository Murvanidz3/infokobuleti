<x-layout.app :title="__('admin.maintenance.title')">
    <x-slot:head>
        <meta name="robots" content="noindex">
    </x-slot:head>

    <x-auth.card :title="__('admin.maintenance.title')" :subtitle="$message ?: __('admin.maintenance.text')" icon="wrench" />
</x-layout.app>
