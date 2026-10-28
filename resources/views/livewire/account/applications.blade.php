<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 xs:mx-0 xs:px-0">
            <x-pill :active="$status === ''" wire:click="$set('status', '')" class="shrink-0">
                {{ __('account.listings.all') }} <span @class(['text-xs', 'text-white/70' => $status === '', 'text-secondary' => $status !== ''])>{{ $total }}</span>
            </x-pill>
            @foreach ($statuses as $value => $label)
                <x-pill :active="$status === $value" wire:click="$set('status', '{{ $value }}')" class="shrink-0">
                    {{ $label }} <span @class(['text-xs', 'text-white/70' => $status === $value, 'text-secondary' => $status !== $value])>{{ $counts[$value] ?? 0 }}</span>
                </x-pill>
            @endforeach
        </div>
        @if ($vacancies->count() > 1)
            <label>
                <span class="sr-only">{{ __('account.applications.vacancy_filter') }}</span>
                <select wire:model.live="vacancy" class="min-h-11 max-w-[280px] rounded-field border border-line bg-white px-3 text-[14.5px] text-navy">
                    <option value="0">{{ __('account.applications.all_vacancies') }}</option>
                    @foreach ($vacancies as $item)
                        <option value="{{ $item->id }}">{{ $item->title }} ({{ $item->applications_count }})</option>
                    @endforeach
                </select>
            </label>
        @endif
    </div>

    <ul class="flex flex-col gap-3" wire:loading.class="opacity-60" wire:target="status, vacancy, setStatus">
        @forelse ($applications as $application)
            <li wire:key="application-{{ $application['id'] }}" class="rounded-card border border-line bg-white shadow-card">
                <button type="button" wire:click="toggle({{ $application['id'] }})" aria-expanded="{{ $open === $application['id'] ? 'true' : 'false' }}"
                        class="flex w-full items-center gap-3 px-4 py-3.5 text-left md:px-5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-surface-alt text-navy"><x-icon name="user" :size="19" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="truncate text-[15.5px] font-semibold text-navy">{{ $application['name'] }}</span>
                            @if ($application['status']->value === 'new')
                                <x-badge variant="new">{{ __('account.applications.new') }}</x-badge>
                            @endif
                        </span>
                        <span class="block truncate text-[13px] text-secondary">{{ $application['vacancy'] }} · {{ $application['created'] }}</span>
                    </span>
                    <span @class([
                        'hidden rounded-full px-2.5 py-[3px] text-xs font-semibold xs:inline-flex',
                        'bg-navy text-white' => in_array($application['status']->value, ['shortlisted', 'hired'], true),
                        'bg-line text-navy' => ! in_array($application['status']->value, ['shortlisted', 'hired'], true),
                    ])>{{ $application['status']->label() }}</span>
                    <x-icon name="chevron-down" :size="18" :class="'shrink-0 text-secondary transition-transform'.($open === $application['id'] ? ' rotate-180' : '')" />
                </button>

                @if ($open === $application['id'])
                    <div class="border-t border-line px-4 py-4 md:px-5">
                        <div class="flex flex-wrap gap-x-5 gap-y-1 text-[14px]">
                            @if ($application['phone'])
                                <a href="{{ $application['tel'] }}" class="inline-flex min-h-9 items-center gap-1.5 font-semibold text-navy hover:text-coral-pressed"><x-icon name="phone" :size="16" class="text-secondary" />{{ $application['phone'] }}</a>
                            @endif
                            @if ($application['email'])
                                <a href="mailto:{{ $application['email'] }}" class="inline-flex min-h-9 items-center gap-1.5 text-navy hover:text-coral-pressed"><x-icon name="mail" :size="16" class="text-secondary" />{{ $application['email'] }}</a>
                            @endif
                            @if ($application['cv'])
                                <a href="{{ $application['cv'] }}" class="inline-flex min-h-9 items-center gap-1.5 font-semibold text-navy hover:text-coral-pressed"><x-icon name="file" :size="16" class="text-secondary" />{{ __('account.applications.cv') }}</a>
                            @endif
                        </div>
                        @if ($application['note'])
                            <p class="mt-3 rounded-tile bg-surface-alt px-4 py-3 text-[14px] leading-normal whitespace-pre-line text-navy">{{ $application['note'] }}</p>
                        @endif
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach (['shortlisted' => 'star', 'hired' => 'check-circle', 'rejected' => 'close'] as $value => $icon)
                                @if ($application['status']->value !== $value)
                                    @if ($value === 'rejected')
                                    <x-button size="sm" variant="ghost" :icon="$icon" wire:click="setStatus({{ $application['id'] }}, '{{ $value }}')" wire:confirm="{{ __('account.applications.confirm_reject') }}">{{ __('account.applications.actions.'.$value) }}</x-button>
                                @else
                                    <x-button size="sm" :variant="$value === 'hired' ? 'primary' : 'secondary'" :icon="$icon" wire:click="setStatus({{ $application['id'] }}, '{{ $value }}')">{{ __('account.applications.actions.'.$value) }}</x-button>
                                @endif
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </li>
        @empty
            <li>
                <x-empty-state icon="briefcase" :title="__('account.applications.empty_title')" :text="__('account.applications.empty_text')" />
            </li>
        @endforelse
    </ul>

    <x-pagination :paginator="$paginator" class="mt-4" />
</div>
