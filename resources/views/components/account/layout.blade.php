{{-- Account area shell: sidebar on desktop, scrollable tabs on mobile, page heading and flash messages. --}}
@props([
    'title',
    'subtitle' => null,
])

@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $menu = app(\App\Services\Account\AccountMenu::class)->items($user);
@endphp

<x-layout.app :title="$title">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
        {{ $head ?? '' }}
    </x-slot:head>

    <section class="min-h-[70vh] bg-surface-alt pt-5 pb-14 md:pt-8 lg:pt-10">
        <div class="container-page">
            <div class="lg:grid lg:grid-cols-[256px_minmax(0,1fr)] lg:items-start lg:gap-8">
                {{-- Desktop sidebar --}}
                <aside class="sticky top-[92px] max-lg:hidden">
                    <nav aria-label="{{ __('account.menu.label') }}" class="rounded-panel border border-line bg-white p-3 shadow-card">
                        <div class="flex items-center gap-3 border-b border-line px-2 pt-1 pb-4">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-navy text-[15px] font-semibold text-white">
                                @if ($user->initials() !== '')
                                    {{ $user->initials() }}
                                @else
                                    <x-icon name="user" :size="20" />
                                @endif
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-[15px] font-semibold text-navy">{{ $user->name ?? __('auth.menu.no_name') }}</p>
                                <p class="truncate text-[13px] text-secondary">{{ $user->account_type?->label() }}</p>
                            </div>
                        </div>
                        <ul class="mt-2 flex flex-col gap-0.5">
                            @foreach ($menu as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif
                                       class="flex min-h-11 items-center gap-3 rounded-button px-3 text-[14.5px] font-medium text-navy transition-colors duration-200 hover:bg-surface-alt aria-[current=page]:bg-navy aria-[current=page]:text-white">
                                        <x-icon :name="$item['icon']" :size="18" class="shrink-0" />
                                        <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                        @if ($item['badge'] > 0)
                                            <span class="grid h-5 min-w-5 place-items-center rounded-full bg-coral-pressed px-1.5 text-[11px] font-semibold text-white">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-3 border-t border-line px-1 pt-3">
                            <x-add-listing-button :href="localized_route('listings.create')" class="w-full justify-center" />
                        </div>
                    </nav>
                </aside>

                <div class="min-w-0">
                    {{-- Mobile / tablet tabs --}}
                    <nav aria-label="{{ __('account.menu.label') }}" class="-mx-4 mb-5 lg:hidden xs:-mx-6">
                        <ul class="scrollbar-none flex gap-2 overflow-x-auto px-4 pb-1 xs:px-6" x-data x-init="$el.querySelector('[aria-current=page]')?.scrollIntoView({ block: 'nearest', inline: 'center' })">
                            @foreach ($menu as $item)
                                <li class="shrink-0">
                                    <x-pill :href="$item['url']" :active="$item['active']" class="gap-2">
                                        <x-icon :name="$item['icon']" :size="16" />{{ $item['label'] }}
                                        @if ($item['badge'] > 0)
                                            <span class="grid h-5 min-w-5 place-items-center rounded-full bg-coral-pressed px-1.5 text-[11px] font-semibold text-white">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                                        @endif
                                    </x-pill>
                                </li>
                            @endforeach
                        </ul>
                    </nav>

                    <header class="flex flex-wrap items-end justify-between gap-4">
                        <div class="min-w-0">
                            <h1 class="font-serif text-[26px] leading-tight font-semibold text-navy md:text-[32px]">{{ $title }}</h1>
                            @if ($subtitle)
                                <p class="mt-1.5 text-[15px] leading-[1.6] text-secondary">{{ $subtitle }}</p>
                            @endif
                        </div>
                        @isset($actions)
                            <div class="flex flex-wrap gap-2">{{ $actions }}</div>
                        @endisset
                    </header>

                    <x-auth.flash class="mt-5" />

                    <div class="mt-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layout.app>
