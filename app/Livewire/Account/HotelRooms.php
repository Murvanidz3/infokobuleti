<?php

namespace App\Livewire\Account;

use App\Actions\Hotels\UpdateAvailability;
use App\Models\Hotel;
use App\Models\RoomAvailability;
use App\Models\RoomType;
use App\Models\RoomTypePriceRule;
use App\Models\User;
use App\Services\DateFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Hotel owner: room types, seasonal price rules and a month calendar where a range of nights
 * (selected by dragging) is closed, opened or given a special price.
 */
class HotelRooms extends Component
{
    #[Locked]
    public int $hotelId;

    /** Room type shown in the calendar and price rules. */
    public int $roomId = 0;

    /** Months from the current one. */
    public int $monthOffset = 0;

    /** Room type form: id 0 = new. */
    public ?int $editingRoom = null;

    /** @var array{name: string, capacity_adults: string, capacity_children: string, base_price: string, units: string, size_m2: string} */
    public array $room = ['name' => '', 'capacity_adults' => '2', 'capacity_children' => '0', 'base_price' => '', 'units' => '1', 'size_m2' => ''];

    /** @var array{starts_on: string, ends_on: string, price: string, min_nights: string} */
    public array $rule = ['starts_on' => '', 'ends_on' => '', 'price' => '', 'min_nights' => '1'];

    public string $rangePrice = '';

    public function mount(int $hotelId): void
    {
        $this->hotelId = $hotelId;
        $this->hotelId = $this->hotel()->id;
        $this->roomId = (int) RoomType::query()->where('hotel_id', $this->hotelId)->orderBy('sort')->orderBy('id')->value('id');
    }

    public function selectRoom(int $id): void
    {
        $this->roomId = $this->ownedRoom($id)->id;
    }

    public function editRoom(?int $id = null): void
    {
        $this->resetValidation();
        $this->editingRoom = $id ?? 0;

        if ($id !== null) {
            $room = $this->ownedRoom($id);
            $this->room = [
                'name' => (string) $room->getTranslation('name', 'ka', false),
                'capacity_adults' => (string) $room->capacity_adults,
                'capacity_children' => (string) $room->capacity_children,
                'base_price' => (string) (float) $room->base_price,
                'units' => (string) $room->units,
                'size_m2' => (string) ($room->size_m2 ?? ''),
            ];
        } else {
            $this->reset('room');
        }
    }

    public function saveRoom(): void
    {
        $data = $this->validate([
            'room.name' => ['required', 'string', 'min:2', 'max:80'],
            'room.capacity_adults' => ['required', 'integer', 'between:1,20'],
            'room.capacity_children' => ['nullable', 'integer', 'between:0,10'],
            'room.base_price' => ['required', 'numeric', 'between:1,100000'],
            'room.units' => ['required', 'integer', 'between:1,500'],
            'room.size_m2' => ['nullable', 'integer', 'between:5,1000'],
        ], [], $this->roomAttributes())['room'];

        $room = $this->editingRoom ? $this->ownedRoom($this->editingRoom) : new RoomType(['hotel_id' => $this->hotelId]);
        $room->setTranslation('name', 'ka', trim($data['name']));
        $room->fill([
            'capacity_adults' => (int) $data['capacity_adults'],
            'capacity_children' => (int) ($data['capacity_children'] ?? 0),
            'base_price' => (float) $data['base_price'],
            'units' => (int) $data['units'],
            'size_m2' => filled($data['size_m2'] ?? null) ? (int) $data['size_m2'] : null,
        ]);
        $room->save();

        $this->roomId = $room->id;
        $this->editingRoom = null;
        $this->dispatch('toast', message: __('account.rooms.saved'), type: 'success');
    }

    public function deleteRoom(int $id): void
    {
        $this->ownedRoom($id)->delete();
        $this->roomId = (int) RoomType::query()->where('hotel_id', $this->hotelId)->orderBy('sort')->orderBy('id')->value('id');
        $this->editingRoom = null;
    }

    public function addRule(): void
    {
        $room = $this->ownedRoom($this->roomId);

        $data = $this->validate([
            'rule.starts_on' => ['required', 'date', 'after_or_equal:today'],
            'rule.ends_on' => ['required', 'date', 'after_or_equal:rule.starts_on', 'before:'.now()->addYears(2)->toDateString()],
            'rule.price' => ['required', 'numeric', 'between:1,100000'],
            'rule.min_nights' => ['required', 'integer', 'between:1,60'],
        ], [], [
            'rule.starts_on' => __('account.rooms.rule_from'),
            'rule.ends_on' => __('account.rooms.rule_to'),
            'rule.price' => __('account.rooms.rule_price'),
            'rule.min_nights' => __('account.rooms.min_nights'),
        ])['rule'];

        $room->priceRules()->create([
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'price' => (float) $data['price'],
            'min_nights' => (int) $data['min_nights'],
        ]);

        $this->reset('rule');
        $this->dispatch('toast', message: __('account.rooms.rule_added'), type: 'success');
    }

    public function deleteRule(int $id): void
    {
        RoomTypePriceRule::query()->whereKey($id)->whereIn('room_type_id', RoomType::query()->select('id')->where('hotel_id', $this->hotelId))->delete();
    }

    public function shiftMonth(int $by): void
    {
        $this->monthOffset = max(0, min(18, $this->monthOffset + $by));
    }

    /**
     * Applies the calendar action ("close", "open" or "price") to the selected nights.
     */
    public function applyRange(UpdateAvailability $availability, string $from, string $to, string $action): void
    {
        if (! in_array($action, ['close', 'open', 'price'], true)) {
            return;
        }

        $room = $this->ownedRoom($this->roomId);

        if ($action === 'price') {
            $this->validate(['rangePrice' => ['required', 'numeric', 'between:1,100000']], [], ['rangePrice' => __('account.rooms.rule_price')]);
        }

        try {
            $count = $availability->handle($room, Carbon::parse($from), Carbon::parse($to), $action, $action === 'price' ? (float) $this->rangePrice : null);
        } catch (\Throwable) {
            $this->dispatch('toast', message: __('account.listings.not_allowed'), type: 'error');

            return;
        }

        $this->dispatch('toast', message: trans_choice('account.rooms.range_done', $count, ['count' => $count]), type: 'success');
    }

    public function render(DateFormatter $dates): View
    {
        $hotel = $this->hotel();
        $rooms = RoomType::query()->where('hotel_id', $hotel->id)->orderBy('sort')->orderBy('id')->get();
        $room = $rooms->firstWhere('id', $this->roomId);

        return view('livewire.account.hotel-rooms', [
            'hotel' => $hotel,
            'rooms' => $rooms,
            'current' => $room,
            'rules' => $room?->priceRules()->where('ends_on', '>=', today())->orderBy('starts_on')->get() ?? collect(),
            'month' => $room !== null ? $this->month($room, $dates) : null,
        ]);
    }

    /**
     * Calendar of one month for the selected room: price per night and closed nights.
     *
     * @return array{label: string, offset: int, days: list<array{date: string, day: int, price: float, closed: bool, special: bool, past: bool}>}
     */
    private function month(RoomType $room, DateFormatter $dates): array
    {
        $start = today()->startOfMonth()->addMonthsNoOverflow($this->monthOffset);
        $end = $start->copy()->endOfMonth();

        $rules = $room->priceRules()->where('ends_on', '>=', $start)->where('starts_on', '<=', $end)->orderByDesc('starts_on')->get();
        $exceptions = $room->availability()->whereBetween('date', [$start->toDateString(), $end->toDateString()])->get()
            ->keyBy(fn (RoomAvailability $row): string => $row->date->toDateString());

        $days = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $exception = $exceptions->get($day->toDateString());
            $rule = $rules->first(fn (RoomTypePriceRule $rule): bool => $rule->starts_on->lte($day) && $rule->ends_on->gte($day));

            $days[] = [
                'date' => $day->toDateString(),
                'day' => $day->day,
                'price' => (float) ($exception->price_override ?? $rule->price ?? $room->base_price),
                'closed' => (bool) ($exception->is_closed ?? false),
                'special' => $exception?->price_override !== null || $rule !== null,
                'past' => $day->lt(today()),
            ];
        }

        return ['label' => $dates->monthYear($start), 'offset' => $start->dayOfWeekIso - 1, 'days' => $days];
    }

    /**
     * @return array<string, string>
     */
    private function roomAttributes(): array
    {
        return collect(['name', 'capacity_adults', 'capacity_children', 'base_price', 'units', 'size_m2'])
            ->mapWithKeys(fn (string $field): array => ["room.{$field}" => __('account.rooms.fields.'.$field)])
            ->all();
    }

    private function hotel(): Hotel
    {
        return Hotel::query()->where('user_id', $this->user()->id)->findOrFail($this->hotelId);
    }

    private function ownedRoom(int $id): RoomType
    {
        return RoomType::query()->where('hotel_id', $this->hotel()->id)->findOrFail($id);
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
