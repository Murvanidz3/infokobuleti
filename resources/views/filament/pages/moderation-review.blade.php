<x-filament-panels::page>
    @if ($item)
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                <x-filament::section>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-filament::badge color="gray">{{ __('admin.morph.'.$item->listing_type) }}</x-filament::badge>
                        <x-filament::badge :color="$item->status->getColor()">{{ $item->status->label() }}</x-filament::badge>
                        @if ($item->risk_score !== null)
                            <x-filament::badge :color="$item->risk_score >= 70 ? 'danger' : ($item->risk_score >= 40 ? 'warning' : 'success')">
                                {{ __('admin.fields.risk_score') }}: {{ $item->risk_score }}
                            </x-filament::badge>
                        @endif
                        <span class="text-sm text-gray-500">{{ __('admin.fields.submitted') }}: {{ $item->created_at->format('d.m.Y H:i') }}</span>
                    </div>

                    <h2 class="mt-4 text-2xl font-semibold text-gray-950 dark:text-white">{{ $item->titleText() }}</h2>

                    @if ($description !== '')
                        <p class="mt-3 whitespace-pre-line text-[15px] leading-relaxed text-gray-700 dark:text-gray-300">{{ $description }}</p>
                    @endif

                    @if ($listing && filled($listing->rejection_reason ?? null))
                        <p class="mt-4 rounded-lg bg-danger-50 px-3 py-2 text-sm text-danger-700">{{ __('admin.fields.rejection_reason') }}: {{ $listing->rejection_reason }}</p>
                    @endif
                </x-filament::section>

                @if ($photos !== [])
                    <x-filament::section :heading="__('admin.moderation.photos')">
                        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                            @foreach ($photos as $photo)
                                <a href="{{ $photo }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl">
                                    <img src="{{ $photo }}" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover">
                                </a>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endif
            </div>

            <div class="flex flex-col gap-6">
                <x-filament::section :heading="__('admin.fields.owner')">
                    @if ($owner)
                        <dl class="grid gap-2 text-sm">
                            <div><dt class="text-gray-500">{{ __('admin.fields.name') }}</dt><dd class="font-medium">{{ $owner->name ?? '—' }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('admin.fields.phone') }}</dt><dd>{{ \App\Support\GeorgianPhone::format($owner->phone) ?: '—' }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('admin.fields.created_at') }}</dt><dd>{{ $owner->created_at->format('d.m.Y') }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('admin.moderation.owner_listings') }}</dt><dd>{{ $ownerListings }}</dd></div>
                            @if ($owner->is_blocked)
                                <div><x-filament::badge color="danger">{{ __('admin.fields.is_blocked') }}</x-filament::badge></div>
                            @endif
                        </dl>
                    @else
                        <p class="text-sm text-gray-500">—</p>
                    @endif
                </x-filament::section>

                <x-filament::section :heading="__('admin.moderation.shortcuts')">
                    <ul class="grid gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                        <li><kbd class="rounded border px-1.5 font-mono">A</kbd> {{ __('admin.moderation.approve') }}</li>
                        <li><kbd class="rounded border px-1.5 font-mono">R</kbd> {{ __('admin.moderation.reject') }}</li>
                        <li><kbd class="rounded border px-1.5 font-mono">S</kbd> {{ __('admin.moderation.skip') }}</li>
                        <li><kbd class="rounded border px-1.5 font-mono">O</kbd> {{ __('admin.moderation.open') }}</li>
                    </ul>
                    <p class="mt-3 text-sm text-gray-500">{{ trans_choice('admin.moderation.remaining', $remaining, ['count' => $remaining]) }}</p>
                </x-filament::section>
            </div>
        </div>
    @endif
</x-filament-panels::page>
