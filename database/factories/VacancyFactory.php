<?php

namespace Database\Factories;

use App\Enums\ApplyMethod;
use App\Enums\CategoryModule;
use App\Enums\Currency;
use App\Enums\EmploymentType;
use App\Enums\Experience;
use App\Enums\SalaryPeriod;
use App\Enums\VacancyBenefit;
use App\Models\Category;
use App\Models\User;
use App\Models\Vacancy;
use Database\Factories\Concerns\ListingStates;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vacancy>
 */
class VacancyFactory extends Factory
{
    use ListingStates;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(EmploymentType::cases());
        $min = fake()->numberBetween(8, 25) * 100;

        return [
            ...$this->listingAttributes(),
            'user_id' => User::factory(),
            'category_id' => Category::factory()->module(CategoryModule::Vacancies),
            'title' => ['ka' => 'მიმტანი', 'en' => 'Waiter', 'ru' => 'Официант'],
            'description' => [
                'ka' => 'ვეძებთ პასუხისმგებლიან და კომუნიკაბელურ თანამშრომელს. სამუშაო გრაფიკი 2/2.',
                'en' => 'We are looking for a responsible, friendly team member. Shifts 2 on / 2 off.',
                'ru' => 'Ищем ответственного и общительного сотрудника. График 2/2.',
            ],
            'address' => Kobuleti::address(),
            ...Kobuleti::coordinates(),
            'employment_type' => $type,
            'salary_min' => $min,
            'salary_max' => fake()->boolean(60) ? $min + fake()->numberBetween(2, 8) * 100 : null,
            'salary_currency' => Currency::GEL,
            'salary_period' => SalaryPeriod::Month,
            'experience' => fake()->randomElement(Experience::cases()),
            'languages' => fake()->randomElements(['ka', 'en', 'ru', 'tr'], fake()->numberBetween(1, 3)),
            'benefits' => $type === EmploymentType::Seasonal
                ? fake()->randomElements(VacancyBenefit::values(), fake()->numberBetween(1, 3))
                : [],
            'deadline' => now()->addDays(fake()->numberBetween(7, 45))->toDateString(),
            'apply_method' => ApplyMethod::Site,
            'apply_phone' => Kobuleti::phone(),
        ];
    }
}
