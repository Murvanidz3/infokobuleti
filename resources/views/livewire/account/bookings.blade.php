<div class="flex flex-col gap-6">
    {{-- Hotels: rooms, prices and calendar --}}
    <section aria-labelledby="hotels-title">
        <h2 id="hotels-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('account.bookings.hotels') }}</h2>
        <ul class="mt-3 grid gap-3 md:grid-cols-2">
            @foreach ($hotels as $item)
                <li wire:key="hotel-{{ $item->id }}" class="flex items-center gap-3 rounded-card border border-line bg-white p-3.5 shadow-card">
                    <span class="size-14 shrink-0 overflow-hidden rounded-tile bg-surface-alt">
                        @if ($cover = $item->coverUrl('thumb'))<img src="{{ $cover }}" alt="" class="size-full object-cover">@endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="line-clamp-2 text-[15px] leading-snug font-semibold text-navy">{{ $item->title }}</span>
                        <span class="block text-[13px] text-secondary">{{ trans_choice('account.bookings.room_types', $item->room_types_count, ['count' => $item->room_types_count]) }} · {{ $item->status->label() }}</span>
                    </span>
                    <x-button size="sm" variant="secondary" icon="calendar" :href="localized_route('account.hotels.rooms', ['hotel' => $item->id])">{{ __('account.bookings.manage') }}</x-button>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Requests --}}
    <section aria-labelledby="requests-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="requests-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('account.bookings.requests') }}</h2>
            @if ($hotels->count() > 1)
                <label class="flex items-center gap-2 text-[14px] text-secondary">
                    <span class="sr-only">{{ __('account.bookings.hotel_filter') }}</span>
                    <select wire:model.live="hotel" class="min-h-11 rounded-field border border-line bg-white px-3 text-[14.5px] text-navy">
                        <option value="0">{{ __('account.bookings.all_hotels') }}</option>
                        @foreach ($hotels as $item)
                            <option value="{{ $item->id }}">{{ $item->title }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
        </div>

        <div class="scrollbar-none -mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-1 xs:mx-0 xs:px-0">
            @foreach ($statuses as $value => $label)
                <x-pill :active="$status === $value" wire:click="$set('status', '{{ $value }}')" class="shrink-0">
                    {{ $label }}
                    @if ($value !== 'all' && ($counts[$value] ?? 0) > 0)
                        <span @class(['text-xs', 'text-white/70' => $status === $value, 'text-secondary' => $status !== $value])>{{ $counts[$value] }}</span>
                    @endif
                </x-pill>
            @endforeach
        </div>

        <div class="mt-4 flex flex-col gap-3" wire:loading.class="opacity-60" wire:target="status, hotel, accept, decline">
            @forelse ($bookings as $booking)
                <article wire:key="booking-{{ $booking['id'] }}" class="rounded-card border border-line bg-white p-4 shadow-card md:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[12.5px] font-medium text-secondary">{{ $booking['hotel'] }}@if ($booking['room']) · {{ $booking['room'] }}@endif</p>
                            <h3 class="mt-0.5 text-[17px] font-semibold text-navy">{{ $booking['dates'] }}</h3>
                            <p class="mt-0.5 text-[13.5px] text-secondary">
                                {{ trans_choice('account.bookings.nights', $booking['nights'], ['count' => $booking['nights']]) }} · {{ trans_choice('account.bookings.guests', $booking['guests'], ['count' => $booking['guests']]) }} · {{ $booking['created'] }}
                            </p>
                        </div>
                        <span @class([
                            'inline-flex items-center rounded-full px-2.5 py-[3px] text-xs font-semibold',
                            'bg-coral-tint text-coral-pressed' => $booking['status']->value === 'pending',
                            'bg-navy text-white' => $booking['status']->value === 'accepted',
                            'bg-line text-navy' => ! in_array($booking['status']->value, ['pending', 'accepted'], true),
                        ])>{{ $booking['status']->label() }}</span>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-[14px]">
                        <span class="inline-flex items-center gap-1.5 font-semibold text-navy"><x-icon name="user" :size="16" class="text-secondary" />{{ $booking['name'] }}</span>
                        <a href="{{ $booking['tel'] }}" class="inline-flex min-h-9 items-center gap-1.5 font-semibold text-navy hover:text-coral-pressed"><x-icon name="phone" :size="16" class="text-secondary" />{{ $booking['phone'] }}</a>
                        @if ($booking['email'])
                            <a href="mailto:{{ $booking['email'] }}" class="inline-flex min-h-9 items-center gap-1.5 text-navy hover:text-coral-pressed"><x-icon name="mail" :size="16" class="text-secondary" />{{ $booking['email'] }}</a>
                        @endif
                    </div>

                    @if ($booking['message'])
                        <p class="mt-3 rounded-tile bg-surface-alt px-4 py-3 text-[14px] leading-normal whitespace-pre-line text-navy">{{ $booking['message'] }}</p>
                    @endif

                    @if ($booking['status']->value === 'pending')
                        <div class="mt-4 flex flex-col gap-3 border-t border-line pt-4">
                            <label for="note-{{ $booking['id'] }}" class="sr-only">{{ __('account.bookings.note') }}</label>
                            <textarea id="note-{{ $booking['id'] }}" wire:model="notes.{{ $booking['id'] }}" rows="2" maxlength="500" placeholder="{{ __('account.bookings.note_placeholder') }}"
                                      class="block w-full rounded-field border border-line bg-white px-4 py-2.5 text-[14.5px] text-navy hover:border-line-strong focus:border-navy"></textarea>
                            @error('notes.'.$booking['id'])<p class="text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
                            <div class="flex flex-wrap gap-2">
                                <x-button size="sm" icon="check" wire:click="accept({{ $booking['id'] }})" wire:loading.attr="disabled">{{ __('account.bookings.accept') }}</x-button>
                                <x-button size="sm" variant="ghost" icon="close" wire:click="decline({{ $booking['id'] }})" wire:confirm="{{ __('account.bookings.confirm_decline') }}">{{ __('account.bookings.decline') }}</x-button>
                            </div>
                        </div>
                    @elseif ($booking['note'])
                        <p class="mt-3 text-[13.5px] text-secondary"><span class="font-semibold text-navy">{{ __('account.bookings.note') }}:</span> {{ $booking['note'] }}</p>
                    @endif
                </article>
            @empty
                <x-empty-state icon="calendar" :title="__('account.bookings.empty_title')" :text="__('account.bookings.empty_text')" />
            @endforelse
        </div>

        <x-pagination :paginator="$paginator" class="mt-8" />
    </section>
</div>
