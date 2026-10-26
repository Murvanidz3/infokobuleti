@props(['facts' => []])

{{-- Key facts: list of ['icon' => ..., 'label' => ..., 'value' => ...]. --}}
<dl {{ $attributes->class(['grid grid-cols-2 gap-2.5 md:grid-cols-3 md:gap-3']) }}>
    @foreach ($facts as $fact)
        {{-- dt / dd sit directly in the item (valid description list); the icon is placed in the left padding. --}}
        <div class="relative min-w-0 rounded-tile border border-line bg-white p-3.5 xs:min-h-[68px] xs:pl-[66px]">
            <dt class="text-[12.5px] text-secondary">
                <span class="absolute top-3.5 left-3.5 grid size-10 place-items-center rounded-full bg-surface-alt text-navy max-xs:hidden"><x-icon :name="$fact['icon']" :size="19" /></span>
                {{ $fact['label'] }}
            </dt>
            <dd class="mt-0.5 text-[15px] font-semibold text-navy [overflow-wrap:anywhere]">{{ $fact['value'] }}</dd>
        </div>
    @endforeach
</dl>
