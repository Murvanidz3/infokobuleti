@php
    $input = 'block min-h-11 w-full min-w-0 rounded-field border border-line bg-white px-3.5 text-[15px] text-navy hover:border-line-strong focus:border-navy';
    $bookingErrors = $errors->booking;
    $user = auth()->user();
@endphp

<x-layout.app :title="$hotel->title" :description="$description">
    @if ($preview)
        <x-detail.preview-banner :status="$hotel->status->getLabel()" />
    @endif

    <div class="container-page pt-5 pb-[clamp(56px,6vw,96px)]">
        <x-breadcrumbs :items="[
            ['label' => __('nav.home'), 'url' => localized_route('home')],
            ['label' => __('nav.hotels'), 'url' => localized_route('hotels.index')],
            ['label' => $hotel->title],
        ]" />

        <div class="mt-4 mb-5 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <x-badge variant="tint">{{ $hotel->type->getLabel() }}</x-badge>
                    @if ($hotel->stars)
                        <span class="text-[14px] tracking-wider text-sun" aria-label="{{ trans_choice('site.filters.stars_count', $hotel->stars, ['count' => $hotel->stars]) }}">{{ str_repeat('★', (int) $hotel->stars) }}</span>
                    @endif
                </div>
                <h1 class="font-serif text-[clamp(28px,3vw,40px)] leading-[1.2] font-semibold text-balance text-navy">{{ $hotel->title }}</h1>
                <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[14.5px] text-secondary">
                    <span class="flex items-center gap-1.5"><x-icon name="pin" :size="16" class="text-navy" />{{ $address }}</span>
                    @if ($hotel->rating_avg)
                        <a href="#reviews" class="flex items-center gap-1.5 font-semibold text-navy"><x-icon name="star" :size="16" class="fill-sun text-sun" />{{ number_format((float) $hotel->rating_avg, 1) }} <span class="font-normal text-secondary">· {{ trans_choice('ui.counts.reviews', $hotel->reviews_count, ['count' => $hotel->reviews_count]) }}</span></a>
                    @endif
                </p>
            </div>
            <x-detail.actions type="hotel" :id="$hotel->id" :title="$hotel->title" :saved="$saved" />
        </div>

        <x-detail.gallery :images="$gallery" :title="$hotel->title" />

        <div x-data="stayPicker(@js(old('check_in', $stay['check_in'])), @js(old('check_out', $stay['check_out'])))" class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_380px]">
            <div class="flex min-w-0 flex-col gap-10">
                <x-detail.facts :facts="$facts" />

                @if (filled($hotel->description))
                    <x-detail.section :title="__('site.detail.about')">
                        <x-detail.text :text="$hotel->description" />
                    </x-detail.section>
                @endif

                @if ($hotel->amenities->isNotEmpty())
                    <x-detail.section :title="__('site.filters.amenities')">
                        <ul class="grid grid-cols-2 gap-x-6 gap-y-3 xs:grid-cols-3">
                            @foreach ($hotel->amenities as $amenity)
                                <li class="flex items-center gap-2.5 text-[15px] text-navy"><x-icon :name="\App\View\Components\Icon::has($amenity->icon) ? $amenity->icon : 'check'" :size="18" class="shrink-0 text-sea" />{{ $amenity->name }}</li>
                            @endforeach
                        </ul>
                    </x-detail.section>
                @endif

                @if ($rooms)
                    <x-detail.section :title="__('site.hotels.rooms_title')" id="rooms">
                        <ul class="flex flex-col gap-4">
                            @foreach ($rooms as $room)
                                <li class="grid overflow-hidden rounded-card border border-line bg-white xs:grid-cols-[200px_minmax(0,1fr)]">
                                    <div class="relative aspect-16/10 bg-line xs:aspect-auto"><x-image :src="$room['image']" :alt="$room['name']" class="absolute inset-0" /></div>
                                    <div class="flex flex-col gap-2 p-4 xs:p-5">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <h3 class="text-[17px] font-semibold text-navy">{{ $room['name'] }}</h3>
                                            <p class="text-right"><span class="text-[19px] font-bold text-price">{{ $room['price'] }}</span> <span class="text-[13px] text-secondary">{{ __('listing.per_night') }}</span></p>
                                        </div>
                                        <p class="flex flex-wrap gap-x-4 gap-y-1 text-[13.5px] text-secondary">
                                            <span class="flex items-center gap-1.5"><x-icon name="users" :size="15" />{{ trans_choice('site.filters.guests_count', $room['capacity'], ['count' => $room['capacity']]) }}</span>
                                            @if ($room['size'])
                                                <span class="flex items-center gap-1.5"><x-icon name="area" :size="15" />{{ __('listing.area', ['value' => $room['size']]) }}</span>
                                            @endif
                                        </p>
                                        @if ($room['description'])
                                            <p class="text-[14px] leading-[1.6] text-secondary">{{ $room['description'] }}</p>
                                        @endif
                                        @if ($room['amenities'])
                                            <p class="text-[13px] text-secondary">{{ implode(' · ', $room['amenities']) }}</p>
                                        @endif
                                        <div class="mt-1">
                                            <x-button size="sm" variant="secondary" x-on:click="$refs.room.value = '{{ $room['id'] }}'; document.getElementById('booking').scrollIntoView({ behavior: 'smooth' })">{{ __('site.hotels.choose_room') }}</x-button>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </x-detail.section>
                @endif

                <x-detail.section :title="__('site.hotels.availability')" id="availability">
                    <p class="mb-4 text-[14px] text-secondary">{{ __('site.hotels.availability_hint') }}</p>
                    <div class="flex snap-x snap-mandatory gap-6 overflow-x-auto pb-2 scrollbar-none">
                        @foreach ($months as $month)
                            <div class="w-[min(100%,340px)] shrink-0 snap-start">
                                <h3 class="mb-3 text-[15px] font-semibold text-navy">{{ $month['label'] }}</h3>
                                <div class="grid grid-cols-7 gap-1 text-center" role="grid">
                                    @foreach (__('time.weekdays_short') as $weekday)
                                        <span class="pb-1 text-[11.5px] font-semibold text-secondary">{{ $weekday }}</span>
                                    @endforeach
                                    @for ($i = 0; $i < $month['offset']; $i++)
                                        <span></span>
                                    @endfor
                                    @foreach ($month['days'] as $day)
                                        @if ($day['available'])
                                            <button type="button" x-on:click="pick('{{ $day['date'] }}')"
                                                    x-bind:class="{ '!bg-navy !text-white': state('{{ $day['date'] }}') === 'edge', '!bg-navy/10': state('{{ $day['date'] }}') === 'inside' }"
                                                    aria-label="{{ $day['date'] }}"
                                                    class="flex aspect-square flex-col items-center justify-center rounded-chip bg-surface-alt text-navy transition-colors hover:bg-coral-tint">
                                                <span class="text-[13.5px] font-semibold">{{ $day['day'] }}</span>
                                                <span class="text-[9.5px] leading-none opacity-75">{{ (int) $day['price'] }}</span>
                                            </button>
                                        @else
                                            <span @class(['flex aspect-square items-center justify-center rounded-chip text-[13.5px]', 'text-secondary/40' => $day['past'], 'text-secondary/60 line-through' => ! $day['past']])>{{ $day['day'] }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-detail.section>

                @if ($hotel->lat !== null)
                    <x-detail.section :title="__('site.detail.location')" id="location">
                        <x-detail.map :lat="$hotel->lat" :lng="$hotel->lng" :title="$hotel->title" :nearby="$nearby" :address="$address" />
                    </x-detail.section>
                @endif

                <x-detail.section :title="__('site.reviews.title')" id="reviews">
                    <x-detail.reviews :reviews="$reviews" :rating="$hotel->rating_avg ? number_format((float) $hotel->rating_avg, 1) : null" :count="$hotel->reviews_count" />
                </x-detail.section>
            </div>

            <aside class="flex flex-col gap-5 lg:sticky lg:top-24 lg:self-start">
                <div id="booking" class="scroll-mt-28 rounded-card border border-line bg-white p-5 shadow-card-soft">
                    <div class="mb-4 flex items-baseline justify-between gap-3">
                        @if ($priceFrom)
                            <p><span class="text-[13px] text-secondary">{{ __('site.price.from_word') }}</span> <span class="text-[24px] font-bold text-price">{{ $priceFrom }}</span> <span class="text-[13px] text-secondary">{{ __('listing.per_night') }}</span></p>
                        @endif
                    </div>
                    <h2 class="mb-4 font-serif text-[20px] font-semibold text-navy">{{ __('site.booking.title') }}</h2>
                    <form method="post" action="{{ localized_route('hotels.booking', [$hotel]) }}" class="flex flex-col gap-3.5" novalidate>
                        @csrf
                        <div class="grid grid-cols-2 gap-2.5">
                            <label>
                                <span class="mb-1 block text-[12.5px] text-secondary">{{ __('home.search.fields.check_in') }}</span>
                                <input type="date" name="check_in" x-model="checkIn" min="{{ today()->toDateString() }}" required class="{{ $input }}">
                            </label>
                            <label>
                                <span class="mb-1 block text-[12.5px] text-secondary">{{ __('home.search.fields.check_out') }}</span>
                                <input type="date" name="check_out" x-model="checkOut" x-bind:min="checkIn" required class="{{ $input }}">
                            </label>
                        </div>
                        @foreach (['check_in', 'check_out'] as $field)
                            @if ($bookingErrors->has($field))
                                <p class="-mt-2 text-[13px] text-coral-pressed">{{ $bookingErrors->first($field) }}</p>
                            @endif
                        @endforeach
                        @if ($rooms)
                            <label>
                                <span class="mb-1 block text-[12.5px] text-secondary">{{ __('site.booking.fields.room_type_id') }}</span>
                                <select name="room_type_id" x-ref="room" class="{{ $input }}">
                                    <option value="">{{ __('site.booking.any_room') }}</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room['id'] }}" @selected((string) old('room_type_id') === (string) $room['id'])>{{ $room['name'] }} · {{ $room['price'] }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <div class="grid grid-cols-2 gap-2.5">
                            <label>
                                <span class="mb-1 block text-[12.5px] text-secondary">{{ __('site.booking.fields.adults') }}</span>
                                <input type="number" name="adults" min="1" max="20" value="{{ old('adults', $stay['guests']) }}" class="{{ $input }}">
                            </label>
                            <label>
                                <span class="mb-1 block text-[12.5px] text-secondary">{{ __('site.booking.fields.children') }}</span>
                                <input type="number" name="children" min="0" max="10" value="{{ old('children', 0) }}" class="{{ $input }}">
                            </label>
                        </div>
                        <label>
                            <span class="mb-1 block text-[12.5px] text-secondary">{{ __('site.booking.fields.contact_name') }}</span>
                            <input type="text" name="contact_name" required maxlength="120" autocomplete="name" value="{{ old('contact_name', $user?->name) }}" class="{{ $input }}">
                        </label>
                        <label>
                            <span class="mb-1 block text-[12.5px] text-secondary">{{ __('site.booking.fields.contact_phone') }}</span>
                            <input type="tel" name="contact_phone" required autocomplete="tel" value="{{ old('contact_phone', $user?->phone ? \App\Support\GeorgianPhone::format($user->phone) : '') }}" class="{{ $input }}">
                        </label>
                        <label>
                            <span class="mb-1 block text-[12.5px] text-secondary">{{ __('site.booking.fields.contact_email') }} <span class="opacity-70">({{ __('components.optional') }})</span></span>
                            <input type="email" name="contact_email" autocomplete="email" value="{{ old('contact_email', $user?->email) }}" class="{{ $input }}">
                        </label>
                        <label>
                            <span class="mb-1 block text-[12.5px] text-secondary">{{ __('site.booking.fields.message') }} <span class="opacity-70">({{ __('components.optional') }})</span></span>
                            <textarea name="message" rows="3" maxlength="1000" class="{{ $input }} py-2.5">{{ old('message') }}</textarea>
                        </label>
                        @foreach (['room_type_id', 'adults', 'children', 'contact_name', 'contact_phone', 'contact_email', 'message', 'cf-turnstile-response'] as $field)
                            @if ($bookingErrors->has($field))
                                <p class="text-[13px] text-coral-pressed">{{ $bookingErrors->first($field) }}</p>
                            @endif
                        @endforeach
                        @guest
                            <x-turnstile />
                        @endguest
                        <x-button type="submit" size="lg" class="w-full">{{ __('site.booking.submit') }}</x-button>
                        <p class="text-center text-[12.5px] text-secondary">{{ __('site.booking.note') }}</p>
                    </form>
                </div>

                <x-detail.owner :owner="$owner" type="hotel" :id="$hotel->id" :has-phone="$hasPhone" :title="$hotel->title" />
            </aside>
        </div>

        @if ($similar->isNotEmpty())
            <x-detail.section :title="__('site.detail.similar_hotels')" class="mt-14 border-t pt-10">
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,260px),1fr))] gap-5">
                    @foreach ($similar as $item)
                        <x-card.hotel :name="$item['name']" :address="$item['address']" :sea="$item['sea']" :rating="$item['rating']" :reviews="$item['reviews']" :price="$item['price']" :url="$item['url']" :image="$item['image']" :saved="$item['saved']" :favorite="$item['favorite']" />
                    @endforeach
                </div>
            </x-detail.section>
        @endif
    </div>
</x-layout.app>
