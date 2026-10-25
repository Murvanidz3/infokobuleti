<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VacancyApplication>
 */
class VacancyApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vacancy_id' => Vacancy::factory(),
            'user_id' => User::factory(),
            'cover_note' => 'გამარჯობა, მაქვს მსგავს პოზიციაზე მუშაობის ორწლიანი გამოცდილება და შემიძლია დაწყება მაშინვე.',
            'contact_phone' => Kobuleti::phone(),
            'status' => fake()->randomElement(ApplicationStatus::cases()),
        ];
    }
}
