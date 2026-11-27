<x-filament-panels::page>
    <x-filament::section>
        <div>
            @foreach ($features as $feature)
                <div class="ik-feature-row">
                    <div style="min-width:0">
                        <p class="text-sm font-semibold">{{ $feature['label'] }}</p>
                        <p class="ik-feature-help text-sm">{{ $feature['help'] }}</p>
                        @if ($feature['needs'])
                            <p class="ik-feature-needs">{{ $feature['needs'] }}</p>
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
