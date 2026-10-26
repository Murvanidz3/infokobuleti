<div>
        {{-- Module tabs --}}
        <div class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 xs:mx-0 xs:px-0" role="tablist" aria-label="{{ __('account.listings.modules') }}">
            @foreach ($types as $value => $label)
                <x-pill :active="$type === $value" wire:click="$set('type', '{{ $value }}')" role="tab" aria-selected="{{ $type === $value ? 'true' : 'false' }}" class="shrink-0">
                    {{ $label }}
                    <span @class(['text-xs', 'text-white/70' => $type === $value, 'text-secondary' => $type !== $value])>{{ $counts[$value]['total'] }}</span>
                </x-pill>
            @endforeach
        </div>

        {{-- Group filter --}}
        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 border-b border-line pb-3 text-[14px]">
            <button type="button" wire:click="$set('group', '')" aria-pressed="{{ $group === '' ? 'true' : 'false' }}"
                    class="hit-44 relative font-semibold text-secondary transition-colors hover:text-navy aria-pressed:text-navy aria-pressed:underline aria-pressed:decoration-coral aria-pressed:decoration-2 aria-pressed:underline-offset-8">
                {{ __('account.listings.all') }} <span class="font-normal">({{ $counts[$type]['total'] }})</span>
            </button>
            @foreach ($groups as $value => $label)
                <button type="button" wire:click="$set('group', '{{ $value }}')" aria-pressed="{{ $group === $value ? 'true' : 'false' }}"
                        class="hit-44 relative font-semibold text-secondary transition-colors hover:text-navy aria-pressed:text-navy aria-pressed:underline aria-pressed:decoration-coral aria-pressed:decoration-2 aria-pressed:underline-offset-8">
                    {{ $label }} <span class="font-normal">({{ $counts[$type][$value] }})</span>
                </button>
            @endforeach
        </div>

    <div class="mt-5 flex flex-col gap-3" wire:loading.class="opacity-60" wire:target="type, group, pause, resume, renew, delete">
        @forelse ($rows as $row)
            <x-account.listing-row :row="$row" />
        @empty
            <x-empty-state icon="list" :title="__('account.listings.empty_title')" :text="__('account.listings.empty_text')">
                <x-slot:action>
                    <x-button :href="localized_route('listings.create')" icon="plus">{{ __('nav.add_listing') }}</x-button>
                </x-slot:action>
            </x-empty-state>
        @endforelse
    </div>

    <x-pagination :paginator="$paginator" class="mt-8" />
</div>
