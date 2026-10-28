<?php

namespace App\Livewire\Account;

use App\Actions\Hotels\AnswerBookingRequest;
use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\Hotel;
use App\Models\User;
use App\Services\DateFormatter;
use App\Support\GeorgianPhone;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Booking requests inbox for hotel owners, with accept / decline and a note for the guest.
 */
class Bookings extends Component
{
    use WithPagination;

    #[Url(except: 'pending')]
    public string $status = 'pending';

    #[Url(except: 0)]
    public int $hotel = 0;

    /** @var array<int, string> booking id => note to the guest */
    public array $notes = [];

    #[Locked]
    public string $path = '';

    public function mount(): void
    {
        $this->path = url()->current();

        if ($this->status !== 'all' && BookingStatus::tryFrom($this->status) === null) {
            $this->status = 'pending';
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'hotel'], true)) {
            $this->resetPage();
        }
    }

    public function accept(AnswerBookingRequest $action, int $id): void
    {
        $this->answer($action, $id, true);
    }

    public function decline(AnswerBookingRequest $action, int $id): void
    {
        $this->answer($action, $id, false);
    }

    public function render(DateFormatter $dates): View
    {
        $hotels = Hotel::query()->where('user_id', $this->user()->id)->with('media')->withCount([
            'roomTypes',
        ])->latest()->get();

        $base = BookingRequest::query()
            ->whereIn('hotel_id', $hotels->modelKeys())
            ->when($this->hotel > 0, fn ($query) => $query->where('hotel_id', $this->hotel));

        $counts = (clone $base)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn ($count): int => (int) $count)->all();

        $paginator = (clone $base)
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->with(['hotel', 'roomType'])
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->latest()
            ->paginate(15)
            ->withPath($this->path)
            ->appends(array_filter(['status' => $this->status !== 'pending' ? $this->status : null, 'hotel' => $this->hotel ?: null]));

        return view('livewire.account.bookings', [
            'hotels' => $hotels,
            'counts' => $counts,
            'paginator' => $paginator,
            'bookings' => collect($paginator->items())->map(fn (BookingRequest $booking): array => [
                'id' => $booking->id,
                'hotel' => (string) $booking->hotel->title,
                'room' => $booking->roomType !== null ? (string) $booking->roomType->name : null,
                'dates' => $dates->date($booking->check_in).' – '.$dates->date($booking->check_out),
                'nights' => $booking->nights(),
                'guests' => $booking->adults + $booking->children,
                'name' => $booking->contact_name,
                'phone' => GeorgianPhone::format($booking->contact_phone),
                'tel' => 'tel:'.$booking->contact_phone,
                'email' => $booking->contact_email,
                'message' => $booking->message,
                'status' => $booking->status,
                'note' => $booking->owner_note,
                'created' => $dates->relative($booking->created_at ?? now()),
                'past' => $booking->check_in->isPast(),
            ])->all(),
            'statuses' => ['pending' => BookingStatus::Pending->label(), 'accepted' => BookingStatus::Accepted->label(), 'declined' => BookingStatus::Declined->label(), 'all' => __('account.listings.all')],
        ]);
    }

    private function answer(AnswerBookingRequest $action, int $id, bool $accept): void
    {
        $booking = BookingRequest::query()
            ->whereHas('hotel', fn ($query) => $query->where('user_id', $this->user()->id))
            ->findOrFail($id);

        if ($booking->status !== BookingStatus::Pending) {
            return;
        }

        $this->validate(["notes.{$id}" => ['nullable', 'string', 'max:500']], [], ["notes.{$id}" => __('account.bookings.note')]);

        $action->handle($booking, $accept, $this->notes[$id] ?? null);
        unset($this->notes[$id]);

        $this->dispatch('toast', message: $accept ? __('account.bookings.accepted') : __('account.bookings.declined'), type: 'success');
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
