{{-- One of the owner's listings: cover, status, statistics and the actions the owner may take (Livewire). --}}
@props(['row'])

@php
    $tone = match ($row['group']) {
        'active' => 'bg-navy text-white',
        'attention' => 'bg-coral-tint text-coral-pressed',
        default => 'bg-line text-navy',
    };
@endphp

<article wire:key="listing-{{ $row['key'] }}" {{ $attributes->class(['rounded-card border border-line bg-white p-4 shadow-card md:p-5']) }}>
    <div class="flex gap-4">
        <a href="{{ $row['url'] }}" class="relative block size-20 shrink-0 overflow-hidden rounded-tile bg-surface-alt md:size-24" tabindex="-1" aria-hidden="true">
            @if ($row['image'])
                <img src="{{ $row['image'] }}" alt="" loading="lazy" class="size-full object-cover">
            @else
                <span class="grid size-full place-items-center text-line-strong"><x-icon name="image" :size="26" /></span>
            @endif
        </a>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-[3px] text-xs font-semibold {{ $tone }}">
                    <x-icon :name="$row['icon']" :size="13" />{{ $row['status'] }}
                </span>
                <span class="text-[12.5px] font-medium text-secondary">{{ $row['module'] }}</span>
                @if ($row['promotion'] ?? null)
                    <span class="inline-flex items-center gap-1 rounded-full bg-coral-tint px-2.5 py-[3px] text-xs font-semibold text-coral-pressed">
                        <x-icon name="star" :size="12" />{{ $row['promotion'] }}@if ($row['promoted_until']) <span class="font-medium">· {{ $row['promoted_until'] }}</span>@endif
                    </span>
                @endif
                @if ($row['expiring'])
                    <span class="text-[12.5px] font-semibold text-coral-pressed">{{ __('account.listings.expiring_soon') }}</span>
                @endif
            </div>

            <h3 class="mt-1.5 line-clamp-2 text-[16px] leading-snug font-semibold text-navy">
                <a href="{{ $row['url'] }}" class="hover:text-coral-pressed">{{ $row['title'] ?: __('account.listings.untitled') }}</a>
            </h3>

            <p class="mt-1 truncate text-[13.5px] text-secondary">
                @if ($row['price'])<span class="font-semibold text-price">{{ $row['price'] }}</span> · @endif{{ $row['location'] }}
            </p>

            <dl class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-[13px] text-secondary">
                <div class="flex items-center gap-1.5" title="{{ __('account.stats.views') }}"><x-icon name="eye" :size="15" /><dt class="sr-only">{{ __('account.stats.views') }}</dt><dd>{{ $row['views'] }}</dd></div>
                <div class="flex items-center gap-1.5" title="{{ __('account.stats.phone_reveals') }}"><x-icon name="phone" :size="15" /><dt class="sr-only">{{ __('account.stats.phone_reveals') }}</dt><dd>{{ $row['phone_reveals'] }}</dd></div>
                <div class="flex items-center gap-1.5" title="{{ __('account.stats.favorites') }}"><x-icon name="heart" :size="15" /><dt class="sr-only">{{ __('account.stats.favorites') }}</dt><dd>{{ $row['favorites'] }}</dd></div>
                @if ($row['expires'])
                    <div class="flex items-center gap-1.5"><x-icon name="calendar" :size="15" /><dt class="sr-only">{{ __('account.listings.expiry') }}</dt><dd>{{ $row['expires'] }}</dd></div>
                @endif
            </dl>
        </div>
    </div>

    @if ($row['reason'])
        <p class="mt-4 flex items-start gap-2.5 rounded-tile bg-coral-tint px-4 py-3 text-[14px] leading-normal text-coral-pressed">
            <x-icon name="alert" :size="18" class="mt-px shrink-0" />
            <span><span class="font-semibold">{{ __('account.listings.rejection_reason') }}:</span> {{ $row['reason'] }}</span>
        </p>
    @endif

    <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
        @if ($row['can']['renew'])
            <x-button size="sm" icon="refresh" wire:click="renew('{{ $row['type'] }}', {{ $row['id'] }})" wire:loading.attr="disabled">{{ __('account.listings.renew') }}</x-button>
        @endif
        @if ($row['can']['promote'] ?? false)
            <x-button size="sm" variant="secondary" icon="arrow-up" :href="localized_route('account.promote', ['type' => $row['type'], 'id' => $row['id']])">{{ __('billing.promote.button') }}</x-button>
        @endif
        @if ($row['can']['edit'])
            <x-button size="sm" variant="secondary" icon="file" :href="localized_route('listings.edit', ['type' => $row['type'], 'id' => $row['id']])">{{ __('account.listings.edit') }}</x-button>
        @endif
        @if ($row['can']['pause'])
            <x-button size="sm" variant="ghost" icon="minus" wire:click="pause('{{ $row['type'] }}', {{ $row['id'] }})" wire:loading.attr="disabled">{{ __('account.listings.pause') }}</x-button>
        @endif
        @if ($row['can']['resume'])
            <x-button size="sm" variant="ghost" icon="check" wire:click="resume('{{ $row['type'] }}', {{ $row['id'] }})" wire:loading.attr="disabled">{{ __('account.listings.resume') }}</x-button>
        @endif
        <x-button size="sm" variant="ghost" icon="eye" :href="$row['url']">{{ __('account.listings.view') }}</x-button>
        @if ($row['can']['delete'])
            <x-button size="sm" variant="ghost" icon="close" class="ml-auto"
                      wire:click="delete('{{ $row['type'] }}', {{ $row['id'] }})"
                      wire:confirm="{{ __('account.listings.confirm_delete') }}">{{ __('account.listings.delete') }}</x-button>
        @endif
    </div>
</article>
