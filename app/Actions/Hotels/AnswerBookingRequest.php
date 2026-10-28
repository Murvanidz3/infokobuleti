<?php

namespace App\Actions\Hotels;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Notifications\BookingAnswered;
use Illuminate\Support\Facades\Notification;

/**
 * The hotel owner accepts or declines a pending booking request; the guest is told either way.
 */
class AnswerBookingRequest
{
    public function handle(BookingRequest $booking, bool $accept, ?string $note = null): void
    {
        $booking->forceFill([
            'status' => $accept ? BookingStatus::Accepted : BookingStatus::Declined,
            'owner_note' => filled($note) ? trim((string) $note) : null,
            'responded_at' => now(),
        ])->save();

        $booking->loadMissing('hotel', 'user');
        $notification = new BookingAnswered($booking);

        if ($booking->user !== null) {
            $booking->user->notify($notification);
        } elseif (filled($booking->contact_email)) {
            Notification::route('mail', $booking->contact_email)->notify($notification->locale($booking->locale));
        }
    }
}
