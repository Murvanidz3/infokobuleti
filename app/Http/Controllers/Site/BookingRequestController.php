<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Notifications\NewInquiry;
use App\Rules\HumanVerified;
use App\Support\GeorgianPhone;
use App\Support\Listings\Contact;
use App\Support\Listings\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Booking request from a hotel page (no payment: the hotel confirms or declines).
 */
class BookingRequestController extends Controller
{
    public function __invoke(Request $request, Hotel $hotel): RedirectResponse
    {
        abort_unless(Visibility::isLive($hotel), 404);

        $data = $request->validateWithBag('booking', [
            'room_type_id' => ['nullable', 'integer', Rule::exists(RoomType::class, 'id')->where('hotel_id', $hotel->id)],
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in', 'before_or_equal:'.now()->addYear()->toDateString()],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:10'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'regex:/^\+?[\d\s\-()]{7,20}$/'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'message' => ['nullable', 'string', 'max:1000'],
            'cf-turnstile-response' => $request->user() === null ? [new HumanVerified] : [],
        ], attributes: __('site.booking.fields'));

        $booking = BookingRequest::query()->create([
            ...Arr::except($data, ['cf-turnstile-response']),
            'hotel_id' => $hotel->id,
            'user_id' => $request->user()?->getKey(),
            'children' => (int) ($data['children'] ?? 0),
            'contact_phone' => Contact::normalizePhone($data['contact_phone']),
            'locale' => app()->getLocale(),
        ]);

        $hotel->loadMissing('user');
        $hotel->user?->notify(new NewInquiry('booking', $hotel, [
            'name' => $booking->contact_name,
            'phone' => GeorgianPhone::format($booking->contact_phone),
            'email' => $booking->contact_email,
            'dates' => $booking->check_in->format('d.m.Y').' – '.$booking->check_out->format('d.m.Y'),
            'guests' => (string) ($booking->adults + $booking->children),
            'text' => $booking->message,
        ], localized_route('account.bookings', locale: $hotel->user->preferredLocale())));

        return redirect()->to(localized_route('hotels.show', [$hotel]).'#booking')
            ->with('toast', ['message' => __('site.booking.sent'), 'type' => 'success']);
    }
}
