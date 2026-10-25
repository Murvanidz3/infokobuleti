<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\Hotel;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingRequest>
 */
class BookingRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = now()->addDays(fake()->numberBetween(3, 120))->startOfDay();

        return [
            'hotel_id' => Hotel::factory(),
            'check_in' => $checkIn,
            'check_out' => $checkIn->copy()->addDays(fake()->numberBetween(2, 10)),
            'adults' => fake()->numberBetween(1, 4),
            'children' => fake()->numberBetween(0, 2),
            'contact_name' => Kobuleti::name(),
            'contact_phone' => Kobuleti::phone(),
            'contact_email' => fake()->safeEmail(),
            'message' => 'გამარჯობა, გვაინტერესებს ოთახი ზღვის ხედით, თუ თავისუფალია.',
            'status' => BookingStatus::Pending,
            'locale' => 'ka',
        ];
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'responded_at' => $status === BookingStatus::Pending ? null : now()->subHours(3),
        ]);
    }
}
