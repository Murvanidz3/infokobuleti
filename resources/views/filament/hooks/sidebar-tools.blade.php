{{-- Search and the notification bell, sitting on the sidebar profile block. --}}
@php
    use Filament\Livewire\GlobalSearch;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Icons\Heroicon;
@endphp

<div
    class="ik-sidebar-tools"
    x-data="{
        openSearch() {
            this.$dispatch('open-modal', { id: 'ik-admin-search' })
            this.$nextTick(() => this.$nextTick(() => {
                document.querySelector('#ik-admin-search input[type=search]')?.focus()
            }))
        },
    }"
    x-on:keydown.window="
        if ((event.metaKey || event.ctrlKey) && ! event.altKey && ! event.shiftKey && event.key?.toLowerCase() === 'k') {
            event.preventDefault()
            openSearch()
        }
    "
    x-on:livewire:navigate.window="$dispatch('close-modal', { id: 'ik-admin-search' })"
>
    <x-filament::modal
        id="ik-admin-search"
        width="2xl"
        :heading="__('admin.search')"
        teleport="body"
        class="ik-admin-search"
    >
        <x-slot name="trigger">
            <button
                type="button"
                class="ik-sidebar-tool ik-sidebar-search-btn"
                aria-label="{{ __('admin.search') }}"
                data-tip="{{ __('admin.search') }} · ⌘K / Ctrl+K"
            >
                {{ \Filament\Support\generate_icon_html(Heroicon::OutlinedMagnifyingGlass, size: IconSize::Small) }}
            </button>
        </x-slot>

        @livewire(GlobalSearch::class)
    </x-filament::modal>

    <div class="ik-sidebar-notifications">
        @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
            'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
        ])
    </div>
</div>
