@props(['facts' => []])

{{-- Key facts: list of ['icon' => ..., 'label' => ..., 'value' => ...]. --}}
<dl {{ $attributes->class(['grid grid-cols-2 gap-2.5 md:grid-cols-3 md:gap-3']) }}>
    @foreach ($facts as $fact)
        <div class="flex items-start gap-3 rounded-tile border border-line bg-white p-3.5">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-surface-alt text-navy max-xs:hidden"><x-icon :name="$fact['icon']" :size="19" /></span>
            <div class="min-w-0">
                <dt class="text-[12.5px] text-secondary">{{ $fact['label'] }}</dt>
                <dd class="mt-0.5 text-[15px] font-semibold text-navy [overflow-wrap:anywhere]">{{ $fact['value'] }}</dd>
            </div>
        </div>
    @endforeach
</dl>
