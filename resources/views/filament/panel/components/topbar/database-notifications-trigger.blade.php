{{-- Icon-only bell. The slide-over still belongs to Filament's database notifications component. --}}
@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Icons\Heroicon;
@endphp

<button
    type="button"
    class="ik-sidebar-tool ik-sidebar-notifications-btn"
    aria-label="{{ __('admin.notifications') }}"
    data-tip="{{ __('admin.notifications') }}"
>
    {{ \Filament\Support\generate_icon_html(Heroicon::OutlinedBell, size: IconSize::Small) }}

    @if ($unreadNotificationsCount)
        <span class="ik-sidebar-tool-badge">
            <span class="ik-sidebar-tool-badge-count">{{ $unreadNotificationsCount }}</span>
        </span>
    @endif
</button>
