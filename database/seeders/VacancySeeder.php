<?php

namespace Database\Seeders;

use App\Enums\ApplyMethod;
use App\Enums\CategoryModule;
use App\Enums\EmploymentType;
use App\Enums\Experience;
use App\Enums\ListingStatus;
use App\Enums\SalaryPeriod;
use App\Enums\VacancyBenefit;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Vacancy;
use Database\Factories\Support\Kobuleti;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;

/**
 * 40 vacancies from hotels, restaurants and local companies, many of them seasonal.
 */
class VacancySeeder extends Seeder
{
    use SeedsDemoData;

    /** @var array<int, ListingStatus> */
    private const array STATUSES = [12 => ListingStatus::Pending, 27 => ListingStatus::Pending, 35 => ListingStatus::Expired];

    public function run(): void
    {
        $data = require __DIR__.'/data/vacancies.php';
        $categories = Category::query()->where('module', CategoryModule::Vacancies)->pluck('id', 'slug');
        $profiles = BusinessProfile::query()->get()->groupBy(fn (BusinessProfile $profile): string => $profile->type->value);

        foreach ($data['items'] as $position => [$ka, $en, $ru, $category, $type, $min, $max, $period, $employer]) {
            $profile = $employer === 'individual' ? null : $profiles->get($employer)?->random();
            $employment = EmploymentType::from($type);
            $multilingual = in_array($category, ['hospitality', 'food-service', 'transport-logistics'], true);

            $this->withDemoStatus(Vacancy::factory(), $position, self::STATUSES)->create([
                'user_id' => $profile->user_id ?? $this->individuals()->random()->id,
                'business_profile_id' => $profile?->id,
                'category_id' => $categories[$category],
                'title' => ['ka' => $ka, 'en' => $en, 'ru' => $ru],
                'description' => $data['descriptions'][$category],
                'location_id' => $profile->location_id ?? $this->location('kobuleti-centre')->id,
                'lat' => $profile->lat ?? Kobuleti::CENTER_LAT,
                'lng' => $profile->lng ?? Kobuleti::CENTER_LNG,
                'address' => $profile?->getTranslations('address') ?: $this->location('kobuleti')->getTranslations('name'),
                'employment_type' => $employment,
                'salary_min' => $min,
                'salary_max' => $max,
                'salary_period' => SalaryPeriod::from($period),
                'experience' => fake()->randomElement(Experience::cases()),
                'languages' => $multilingual ? fake()->randomElement([['ka', 'en'], ['ka', 'ru'], ['ka', 'en', 'ru']]) : ['ka'],
                'benefits' => $employment === EmploymentType::Seasonal
                    ? ($category === 'hospitality' ? [VacancyBenefit::Accommodation->value, VacancyBenefit::Meals->value] : [VacancyBenefit::Meals->value, VacancyBenefit::Transport->value])
                    : fake()->randomElements([VacancyBenefit::Training->value, VacancyBenefit::Uniform->value], fake()->numberBetween(0, 1)),
                'apply_method' => $position % 4 === 0 ? ApplyMethod::Phone : ApplyMethod::Site,
                'apply_phone' => $profile->phone ?? Kobuleti::phone(),
                'apply_email' => $profile?->email,
            ]);
        }
    }
}
