<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /** @var list<array{rating: int, body: string}> */
    private const array SAMPLES = [
        ['rating' => 10, 'body' => 'ზღვამდე ორი წუთია, ოთახი სუფთა და მყუდრო. მასპინძლები ძალიან სტუმართმოყვარეები არიან.'],
        ['rating' => 9, 'body' => 'გემრიელი საუზმე და მშვიდი ეზო. აუცილებლად დავბრუნდებით მომავალ ზაფხულს.'],
        ['rating' => 8, 'body' => 'კარგი მდებარეობა ბულვართან ახლოს, ოღონდ საღამოობით ცოტა ხმაურიანია.'],
        ['rating' => 7, 'body' => 'ფასისთვის ნორმალური ვარიანტია, Wi-Fi ზოგჯერ ითიშებოდა.'],
        ['rating' => 9, 'body' => 'ოჯახთან ერთად ვისვენებდით, ბავშვებს ძალიან მოეწონათ. ყველაფერი ისე იყო, როგორც ფოტოებზე.'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sample = fake()->randomElement(self::SAMPLES);

        return [
            'user_id' => User::factory(),
            'reviewable_type' => 'place',
            'reviewable_id' => Place::factory(),
            'rating' => $sample['rating'],
            'body' => $sample['body'],
            'status' => ReviewStatus::Published,
            'locale' => 'ka',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => ReviewStatus::Pending]);
    }
}
