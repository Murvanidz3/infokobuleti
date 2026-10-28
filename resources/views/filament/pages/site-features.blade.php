<x-filament-panels::page>
    <x-filament::section>
        <div style="display:flex;flex-direction:column">
            @foreach ($features as $feature)
                <div style="display:flex;gap:16px;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid rgba(127,127,127,.18)">
                    <div style="min-width:0">
                        <p class="text-sm font-semibold">{{ $feature['label'] }}</p>
                        <p class="text-sm" style="opacity:.7">{{ $feature['help'] }}</p>
                        @if ($feature['needs'])
                            <p class="text-xs" style="margin-top:4px;color:rgb(217,119,6)">{{ $feature['needs'] }}</p>
                        @endif
                    </div>
                    <x-filament::button size="sm" :color="$feature['active'] ? 'success' : 'gray'" wire:click="toggle('{{ $feature['key'] }}')"
                                        :icon="$feature['active'] ? 'heroicon-o-check-circle' : 'heroicon-o-minus-circle'">
                        {{ $feature['active'] ? __('admin.features.on') : __('admin.features.off') }}
                    </x-filament::button>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
