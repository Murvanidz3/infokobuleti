<?php

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Models\Classified;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reportable_type' => 'classified',
            'reportable_id' => Classified::factory(),
            'reason' => fake()->randomElement(ReportReason::cases()),
            'comment' => 'ეს განცხადება უკვე რამდენჯერმე დაიდო.',
        ];
    }
}
