{{-- Filament's collapse buttons live in the top bar. This one sits beside the logo and uses the same store. --}}
<a
    {{ \Filament\Support\generate_href_html(filament()->getHomeUrl() ?? filament()->getUrl()) }}
    class="ik-sidebar-mark"
    x-cloak
    x-show="! $store.sidebar.isOpen"
    aria-label="{{ filament()->getBrandName() }}"
>
    <span class="ik-sidebar-mark-dot" aria-hidden="true"></span>
</a>
<button
    type="button"
    class="ik-sidebar-pin"
    aria-controls="fi-main-sidebar"
    x-bind:aria-expanded="$store.sidebar.isOpen"
    x-bind:aria-label="$store.sidebar.isOpen ? @js(__('admin.sidebar.collapse')) : @js(__('admin.sidebar.expand'))"
    x-bind:data-tip="$store.sidebar.isOpen ? @js(__('admin.sidebar.collapse')) : @js(__('admin.sidebar.expand'))"
    x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open($event.currentTarget)"
>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="ik-sidebar-pin-icon" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="m18.75 4.5-7.5 7.5 7.5 7.5m-6-15L5.25 12l7.5 7.5" />
    </svg>
</button>
