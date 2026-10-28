{{-- Header account control: "Sign in" for guests, the account menu for signed-in users. --}}
@props(['drawer' => false])

@php
    $user = auth()->user();
    $links = [
        ['account', 'grid', __('account.menu.dashboard')],
        ['account.listings', 'list', __('account.menu.listings')],
        ['account.messages', 'message', __('account.menu.messages')],
        ['account.favorites', 'heart', __('account.menu.favorites')],
        ['account.notifications', 'inbox', __('account.menu.notifications')],
        ['account.security', 'shield', __('auth.menu.security')],
    ];
    // The menu renders twice (header and mobile drawer): count once per request.
    $unreadMessages = $user ? once(fn () => app(\App\Services\Account\Inbox::class)->unreadCount($user)) : 0;
    $unreadNotifications = $user ? once(fn () => $user->unreadNotifications()->count()) : 0;
    $unread = $unreadMessages + $unreadNotifications;
@endphp

@if ($drawer)
    <div class="mt-2 flex flex-col">
        @if ($user)
            @foreach ($links as [$route, $icon, $label])
                <a href="{{ localized_route($route) }}" class="flex items-center gap-3 border-b border-white/8 py-3.5 text-[17px] font-medium text-white">
                    <x-icon :name="$icon" :size="20" class="text-white/70" />{{ $label }}
                    @if ($route === 'account.messages' && $unreadMessages > 0)<span class="ml-auto grid h-5 min-w-5 place-items-center rounded-full bg-coral-pressed px-1.5 text-[11px] font-semibold text-white">{{ $unreadMessages }}</span>@endif
                    @if ($route === 'account.notifications' && $unreadNotifications > 0)<span class="ml-auto grid h-5 min-w-5 place-items-center rounded-full bg-coral-pressed px-1.5 text-[11px] font-semibold text-white">{{ $unreadNotifications }}</span>@endif
                </a>
            @endforeach
            <form method="POST" action="{{ localized_route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 border-b border-white/8 py-3.5 text-left text-[17px] font-medium text-white">
                    <x-icon name="logout" :size="20" class="text-white/70" />{{ __('auth.menu.logout') }}
                </button>
            </form>
        @else
            <a href="{{ localized_route('login') }}" class="flex items-center gap-3 border-b border-white/8 py-3.5 text-[17px] font-medium text-white">
                <x-icon name="user" :size="20" class="text-white/70" />{{ __('auth.menu.login') }}
            </a>
        @endif
    </div>
@elseif ($user)
    <div x-data="userMenu" x-bind="root" {{ $attributes->class(['relative']) }}>
        <button type="button"
                x-bind="button"
                aria-expanded="false"
                aria-haspopup="true"
                aria-label="{{ __('auth.menu.account') }}"
                class="relative grid size-11 place-items-center rounded-full border border-white/20 text-[14px] font-semibold text-white transition-colors duration-200 hover:border-white/50">
            @if ($user->initials() !== '')
                <span class="grid size-8 place-items-center rounded-full bg-white text-navy">{{ $user->initials() }}</span>
            @else
                <x-icon name="user" :size="20" />
            @endif
            @if ($unread > 0)
                <span class="absolute -top-0.5 -right-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-coral-pressed px-1 text-[11px] font-semibold text-white ring-2 ring-navy">{{ $unread > 9 ? '9+' : $unread }}</span>
            @endif
        </button>

        <div x-bind="panel" x-cloak
             x-transition:enter="transition duration-200 ease-soft"
             x-transition:enter-start="-translate-y-1 opacity-0"
             class="absolute top-[calc(100%+10px)] right-0 w-[260px] overflow-hidden rounded-tile border border-line bg-white text-navy shadow-overlay">
            <div class="border-b border-line px-4 py-3.5">
                <p class="truncate text-[15px] font-semibold">{{ $user->name ?? __('auth.menu.no_name') }}</p>
                <p class="truncate text-[13px] text-secondary">{{ $user->phone ? \App\Support\GeorgianPhone::format($user->phone) : $user->email }}</p>
            </div>
            @foreach ($links as [$route, $icon, $label])
                <a href="{{ localized_route($route) }}" class="flex min-h-11 items-center gap-3 px-4 text-[14.5px] font-medium transition-colors hover:bg-surface-alt">
                    <x-icon :name="$icon" :size="18" class="text-secondary" />{{ $label }}
                    @if ($route === 'account.messages' && $unreadMessages > 0)<span class="ml-auto grid h-5 min-w-5 place-items-center rounded-full bg-coral-pressed px-1.5 text-[11px] font-semibold text-white">{{ $unreadMessages }}</span>@endif
                    @if ($route === 'account.notifications' && $unreadNotifications > 0)<span class="ml-auto grid h-5 min-w-5 place-items-center rounded-full bg-coral-pressed px-1.5 text-[11px] font-semibold text-white">{{ $unreadNotifications }}</span>@endif
                </a>
            @endforeach
            <form method="POST" action="{{ localized_route('logout') }}" class="border-t border-line">
                @csrf
                <button type="submit" class="flex min-h-11 w-full items-center gap-3 px-4 text-left text-[14.5px] font-medium transition-colors hover:bg-surface-alt">
                    <x-icon name="logout" :size="18" class="text-secondary" />{{ __('auth.menu.logout') }}
                </button>
            </form>
        </div>
    </div>
@else
    <a href="{{ localized_route('login') }}"
       x-data="loginTrigger"
       x-bind="trigger"
       {{ $attributes->class(['inline-flex min-h-11 items-center gap-2 rounded-full border border-white/20 px-4 text-sm font-semibold whitespace-nowrap text-white transition-colors duration-200 hover:border-white/50 hover:text-white']) }}>
        <x-icon name="user" :size="18" />{{ __('auth.menu.login') }}
    </a>
@endif
