<div>
    @if ($total > 0)
        <div class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 xs:mx-0 xs:px-0">
            <x-pill :active="$type === ''" wire:click="$set('type', '')" class="shrink-0">
                {{ __('account.listings.all') }} <span @class(['text-xs', 'text-white/70' => $type === '', 'text-secondary' => $type !== ''])>{{ $total }}</span>
            </x-pill>
            @foreach ($types as $value => $label)
                <x-pill :active="$type === $value" wire:click="$set('type', '{{ $value }}')" class="shrink-0">
                    {{ $label }} <span @class(['text-xs', 'text-white/70' => $type === $value, 'text-secondary' => $type !== $value])>{{ $counts[$value] ?? 0 }}</span>
                </x-pill>
            @endforeach
        </div>
    @endif

    <ul class="mt-5 grid gap-3 md:grid-cols-2" wire:loading.class="opacity-60" wire:target="type, remove">
        @forelse ($items as $item)
            <li wire:key="favorite-{{ $item['id'] }}" @class(['group relative flex gap-4 rounded-card border border-line bg-white p-3.5 shadow-card', 'card-lift' => $item['available']])>
                <div class="relative size-20 shrink-0 overflow-hidden rounded-tile bg-surface-alt">
                    @if ($item['image'])
                        <img src="{{ $item['image'] }}" alt="" loading="lazy" @class(['size-full object-cover', 'opacity-50 grayscale' => ! $item['available']])>
                    @else
                        <span class="grid size-full place-items-center text-line-strong"><x-icon name="image" :size="24" /></span>
                    @endif
                </div>
                <div class="flex min-w-0 flex-1 flex-col">
                    <span class="text-[12.5px] font-medium text-secondary">{{ $item['type'] }}</span>
                    @if ($item['url'])
                        <a href="{{ $item['url'] }}" class="mt-0.5 line-clamp-2 text-[15px] leading-snug font-semibold text-navy after:absolute after:inset-0 hover:text-coral-pressed">{{ $item['title'] }}</a>
                    @else
                        <span class="mt-0.5 line-clamp-2 text-[15px] leading-snug font-semibold text-secondary">{{ $item['title'] }}</span>
                    @endif
                    @if ($item['subtitle'])
                        <span class="mt-1 truncate text-[13px] text-secondary">{{ $item['subtitle'] }}</span>
                    @elseif (! $item['available'])
                        <span class="mt-1 text-[13px] text-secondary">{{ __('account.favorites.unavailable') }}</span>
                    @endif
                </div>
                <button type="button" wire:click="remove({{ $item['id'] }})"
                        aria-label="{{ __('account.favorites.remove') }}" title="{{ __('account.favorites.remove') }}"
                        class="relative z-10 grid size-11 shrink-0 place-items-center self-center rounded-full text-coral transition-colors hover:bg-coral-tint">
                    <x-icon name="heart" :size="20" class="fill-current" />
                </button>
            </li>
        @empty
            <li class="md:col-span-2">
                <x-empty-state icon="heart" :title="__('account.favorites.empty_title')" :text="__('account.favorites.empty_text')">
                    <x-slot:action>
                        <x-button variant="secondary" :href="localized_route('real-estate.index')">{{ __('account.favorites.browse') }}</x-button>
                    </x-slot:action>
                </x-empty-state>
            </li>
        @endforelse
    </ul>

    <x-pagination :paginator="$paginator" class="mt-8" />
</div>
