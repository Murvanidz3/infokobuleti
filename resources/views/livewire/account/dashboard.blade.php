<div class="flex flex-col gap-8">
    {{-- Listings per group --}}
    <section aria-labelledby="groups-title">
        <h2 id="groups-title" class="sr-only">{{ __('account.dashboard.groups') }}</h2>
        <ul class="grid grid-cols-2 gap-3 xs:grid-cols-3 xl:grid-cols-5">
            @foreach ($groups as $group)
                <li>
                    <a href="{{ localized_route('account.listings', ['group' => $group->value]) }}"
                       class="card-lift flex h-full flex-col gap-2 rounded-card border border-line bg-white p-4 shadow-card">
                        <span @class([
                            'grid size-9 place-items-center rounded-full',
                            'bg-navy text-white' => $group->value === 'active',
                            'bg-coral-tint text-navy' => $group->value === 'attention',
                            'bg-surface-alt text-navy' => ! in_array($group->value, ['active', 'attention'], true),
                        ])><x-icon :name="$group->icon()" :size="18" /></span>
                        <span class="font-serif text-[28px] leading-none font-semibold text-navy">{{ $counts[$group->value] }}</span>
                        <span class="text-[13.5px] font-medium text-secondary">{{ $group->label() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Statistics --}}
    <section aria-labelledby="stats-title" class="rounded-panel border border-line bg-white p-5 shadow-card md:p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="stats-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('account.dashboard.stats') }}</h2>
            <p class="text-[13px] text-secondary">{{ __('account.dashboard.stats_hint') }}</p>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach (['views' => 'eye', 'phone_reveals' => 'phone', 'favorites' => 'heart', 'messages' => 'message'] as $metric => $icon)
                {{-- dt first for a valid list; flex-col-reverse shows the number above the label. --}}
                <div class="relative flex min-h-11 flex-col-reverse justify-center pl-14">
                    <dt class="text-[13px] text-secondary">
                        <span class="absolute top-1/2 left-0 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-surface-alt text-navy"><x-icon :name="$icon" :size="20" /></span>
                        {{ __('account.stats.'.$metric) }}
                    </dt>
                    <dd class="text-[20px] leading-tight font-semibold text-navy">{{ number_format($totals[$metric], 0, '.', ' ') }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Needs attention --}}
    @if ($attention !== [])
        <section aria-labelledby="attention-title">
            <h2 id="attention-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('account.dashboard.attention') }}</h2>
            <div class="mt-4 flex flex-col gap-3" wire:loading.class="opacity-60" wire:target="pause, resume, renew, delete">
                @foreach ($attention as $row)
                    <x-account.listing-row :row="$row" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Recent --}}
    <section aria-labelledby="recent-title">
        <div class="flex items-center justify-between gap-4">
            <h2 id="recent-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('account.dashboard.recent') }}</h2>
            <x-link-arrow :href="localized_route('account.listings')">{{ __('account.dashboard.all_listings') }}</x-link-arrow>
        </div>
        <div class="mt-4 flex flex-col gap-3" wire:loading.class="opacity-60" wire:target="pause, resume, renew, delete">
            @forelse ($recent as $row)
                <x-account.listing-row :row="$row" />
            @empty
                @if ($attention === [])
                    <x-empty-state icon="list" :title="__('account.listings.empty_title')" :text="__('account.listings.empty_text')">
                        <x-slot:action>
                            <x-button :href="localized_route('listings.create')" icon="plus">{{ __('nav.add_listing') }}</x-button>
                        </x-slot:action>
                    </x-empty-state>
                @endif
            @endforelse
        </div>
    </section>
</div>
