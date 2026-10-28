@php
    $control = 'block min-h-11 w-full rounded-field border border-line bg-white px-3.5 text-[15px] text-navy hover:border-line-strong focus:border-navy';
@endphp

<div class="flex flex-col gap-6">
    {{-- Room types --}}
    <section aria-labelledby="rooms-title" class="rounded-panel border border-line bg-white p-5 shadow-card md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="rooms-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('account.rooms.types') }}</h2>
            @if ($editingRoom === null)
                <x-button size="sm" variant="secondary" icon="plus" wire:click="editRoom">{{ __('account.rooms.add') }}</x-button>
            @endif
        </div>

        <ul class="mt-4 flex flex-col gap-2">
            @forelse ($rooms as $item)
                <li wire:key="room-{{ $item->id }}" @class(['flex flex-wrap items-center gap-3 rounded-tile border px-4 py-3', 'border-navy' => $item->id === $roomId, 'border-line' => $item->id !== $roomId])>
                    <button type="button" wire:click="selectRoom({{ $item->id }})" class="min-w-0 flex-1 text-left">
                        <span class="block truncate text-[15px] font-semibold text-navy">{{ $item->name }}</span>
                        <span class="block text-[13px] text-secondary">
                            {{ trans_choice('account.bookings.guests', $item->capacity_adults, ['count' => $item->capacity_adults]) }} · {{ trans_choice('account.rooms.units', $item->units, ['count' => $item->units]) }}
                        </span>
                    </button>
                    <span class="text-[15px] font-semibold text-price">{{ number_format((float) $item->base_price, 0, '.', ' ') }} ₾</span>
                    <div class="flex gap-1">
                        <x-icon-button icon="file" :label="__('account.listings.edit')" variant="ghost" wire:click="editRoom({{ $item->id }})" />
                        <x-icon-button icon="close" :label="__('account.common.delete')" variant="ghost" wire:click="deleteRoom({{ $item->id }})" wire:confirm="{{ __('account.rooms.confirm_delete') }}" />
                    </div>
                </li>
            @empty
                <li class="text-[14px] text-secondary">{{ __('account.rooms.empty') }}</li>
            @endforelse
        </ul>

        @if ($editingRoom !== null)
            <form wire:submit="saveRoom" class="mt-4 grid gap-4 rounded-tile bg-surface-alt p-4 md:grid-cols-3 md:p-5">
                <x-form.field class="md:col-span-3" :label="__('account.rooms.fields.name')" for="room-name" :error="$errors->first('room.name')">
                    <input id="room-name" type="text" wire:model="room.name" maxlength="80" class="{{ $control }}">
                </x-form.field>
                @foreach (['base_price' => ['min' => 1], 'capacity_adults' => ['min' => 1, 'max' => 20], 'capacity_children' => ['min' => 0, 'max' => 10], 'units' => ['min' => 1, 'max' => 500], 'size_m2' => ['min' => 5]] as $field => $attributes)
                    <x-form.field :label="__('account.rooms.fields.'.$field)" for="room-{{ $field }}" :error="$errors->first('room.'.$field)" :optional="in_array($field, ['capacity_children', 'size_m2'], true)">
                        <input id="room-{{ $field }}" type="number" inputmode="numeric" wire:model="room.{{ $field }}" @foreach ($attributes as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach class="{{ $control }}">
                    </x-form.field>
                @endforeach
                <div class="flex flex-wrap items-end gap-2 md:col-span-3">
                    <x-button type="submit" size="sm">{{ __('account.common.save') }}</x-button>
                    <x-button size="sm" variant="ghost" wire:click="$set('editingRoom', null)">{{ __('account.common.cancel') }}</x-button>
                </div>
            </form>
        @endif
    </section>

    @if ($current)
        {{-- Availability calendar --}}
        <section aria-labelledby="calendar-title" class="rounded-panel border border-line bg-white p-5 shadow-card md:p-6" x-data="rangeCalendar" x-on:pointerup.window="dragging = false">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="calendar-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('account.rooms.calendar') }}</h2>
                    <p class="text-[13.5px] text-secondary">{{ $current->name }} · {{ __('account.rooms.calendar_hint') }}</p>
                </div>
                <div class="flex items-center gap-1">
                    <x-icon-button icon="chevron-left" :label="__('components.pagination.previous')" variant="ghost" wire:click="shiftMonth(-1)" :disabled="$monthOffset === 0" />
                    <span class="min-w-[140px] text-center text-[15px] font-semibold text-navy">{{ $month['label'] }}</span>
                    <x-icon-button icon="chevron-right" :label="__('components.pagination.next')" variant="ghost" wire:click="shiftMonth(1)" />
                </div>
            </div>

            <div class="mt-4 grid max-w-[640px] grid-cols-7 gap-1 text-center text-[12px] font-semibold text-secondary" aria-hidden="true">
                @foreach (__('time.weekdays_short') as $weekday)
                    <span>{{ $weekday }}</span>
                @endforeach
            </div>
            <ol class="mt-1 grid max-w-[640px] touch-none grid-cols-7 gap-1 select-none" wire:loading.class="opacity-60" wire:target="applyRange, shiftMonth, selectRoom">
                @for ($blank = 0; $blank < $month['offset']; $blank++)
                    <li aria-hidden="true"></li>
                @endfor
                @foreach ($month['days'] as $day)
                    <li wire:key="day-{{ $day['date'] }}">
                        <button type="button" @disabled($day['past'])
                                x-on:pointerdown.prevent="down('{{ $day['date'] }}')"
                                x-on:pointerenter="enter('{{ $day['date'] }}')"
                                x-on:pointerup="up('{{ $day['date'] }}')"
                                x-bind:aria-pressed="selected('{{ $day['date'] }}')"
                                aria-label="{{ $day['date'] }}: {{ $day['closed'] ? __('account.rooms.closed') : number_format($day['price'], 0, '.', ' ').' ₾' }}"
                                @class([
                                    'flex aspect-square w-full flex-col items-center justify-center rounded-chip border text-[13px] leading-tight transition-colors md:aspect-[5/4]',
                                    'border-transparent text-line-strong' => $day['past'],
                                    'border-line bg-surface-alt text-secondary line-through' => ! $day['past'] && $day['closed'],
                                    'border-line bg-white text-navy hover:border-navy' => ! $day['past'] && ! $day['closed'],
                                    'aria-pressed:border-navy aria-pressed:bg-navy aria-pressed:text-white aria-pressed:no-underline',
                                ])>
                            <span class="font-semibold">{{ $day['day'] }}</span>
                            @unless ($day['past'])
                                <span @class(['text-[11px]', 'font-semibold text-price' => $day['special'] && ! $day['closed']])>{{ $day['closed'] ? '×' : number_format($day['price'], 0, '.', ' ') }}</span>
                            @endunless
                        </button>
                    </li>
                @endforeach
            </ol>

            <div x-show="start" x-cloak class="mt-4 flex flex-wrap items-center gap-2 rounded-tile bg-surface-alt p-3">
                <p class="w-full text-[13.5px] font-semibold text-navy md:w-auto md:flex-1" x-text="from === to || ! to ? from : from + ' – ' + to"></p>
                <x-button size="sm" variant="tertiary" icon="minus" x-on:click="apply('close')">{{ __('account.rooms.close') }}</x-button>
                <x-button size="sm" variant="secondary" icon="check" x-on:click="apply('open')">{{ __('account.rooms.open') }}</x-button>
                <div class="flex items-center gap-1.5">
                    <label for="range-price" class="sr-only">{{ __('account.rooms.rule_price') }}</label>
                    <input id="range-price" type="number" inputmode="decimal" min="1" wire:model="rangePrice" placeholder="₾" class="min-h-9 w-24 rounded-field border border-line bg-white px-3 text-[14px] text-navy">
                    <x-button size="sm" icon="tag" x-on:click="apply('price')">{{ __('account.rooms.set_price') }}</x-button>
                </div>
                <x-button size="sm" variant="ghost" x-on:click="clear()">{{ __('account.common.cancel') }}</x-button>
                @error('rangePrice')<p class="w-full text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
            </div>
        </section>

        {{-- Seasonal prices --}}
        <section aria-labelledby="rules-title" class="rounded-panel border border-line bg-white p-5 shadow-card md:p-6">
            <h2 id="rules-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('account.rooms.seasonal') }}</h2>
            <p class="mt-1 text-[13.5px] text-secondary">{{ __('account.rooms.seasonal_hint') }}</p>

            @if ($rules->isNotEmpty())
                <ul class="mt-4 flex flex-col divide-y divide-line">
                    @foreach ($rules as $item)
                        <li wire:key="rule-{{ $item->id }}" class="flex items-center gap-3 py-2.5">
                            <span class="min-w-0 flex-1 text-[14.5px] text-navy">{{ $item->starts_on->format('d.m.Y') }} – {{ $item->ends_on->format('d.m.Y') }}</span>
                            <span class="text-[13px] text-secondary">{{ trans_choice('account.bookings.nights', $item->min_nights, ['count' => $item->min_nights]) }}+</span>
                            <span class="font-semibold text-price">{{ number_format($item->price, 0, '.', ' ') }} ₾</span>
                            <x-icon-button icon="close" :label="__('account.common.delete')" variant="ghost" wire:click="deleteRule({{ $item->id }})" />
                        </li>
                    @endforeach
                </ul>
            @endif

            <form wire:submit="addRule" class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-[1fr_1fr_120px_100px_auto] md:items-end">
                <x-form.field :label="__('account.rooms.rule_from')" for="rule-from" :error="$errors->first('rule.starts_on')">
                    <input id="rule-from" type="date" wire:model="rule.starts_on" min="{{ today()->toDateString() }}" class="{{ $control }}">
                </x-form.field>
                <x-form.field :label="__('account.rooms.rule_to')" for="rule-to" :error="$errors->first('rule.ends_on')">
                    <input id="rule-to" type="date" wire:model="rule.ends_on" min="{{ today()->toDateString() }}" class="{{ $control }}">
                </x-form.field>
                <x-form.field :label="__('account.rooms.rule_price')" for="rule-price" :error="$errors->first('rule.price')">
                    <input id="rule-price" type="number" inputmode="decimal" min="1" wire:model="rule.price" class="{{ $control }}">
                </x-form.field>
                <x-form.field :label="__('account.rooms.min_nights')" for="rule-nights" :error="$errors->first('rule.min_nights')">
                    <input id="rule-nights" type="number" inputmode="numeric" min="1" max="60" wire:model="rule.min_nights" class="{{ $control }}">
                </x-form.field>
                <x-button type="submit" size="md" icon="plus" class="col-span-2 md:col-span-1">{{ __('account.rooms.rule_add') }}</x-button>
            </form>
        </section>
    @endif
</div>
