<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\BusinessType;
use App\Models\BusinessProfile;
use App\Models\User;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessProfile>
 */
class BusinessProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['account_type' => AccountType::Business]),
            'type' => BusinessType::Hotel,
            'name' => fake()->unique()->company(),
            'description' => [
                'ka' => 'ოჯახური ბიზნესი ქობულეთში, ზღვასთან ახლოს.',
                'en' => 'A family business in Kobuleti, close to the sea.',
                'ru' => 'Семейный бизнес в Кобулети, недалеко от моря.',
            ],
            'phone' => Kobuleti::phone(),
            'email' => fake()->companyEmail(),
            'address' => Kobuleti::address(),
            ...Kobuleti::coordinates(),
        ];
    }

    public function type(BusinessType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verified_at' => now()->subMonths(2)]);
    }
}
