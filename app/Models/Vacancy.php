<?php

namespace App\Models;

use App\Contracts\Listing;
use App\Enums\ApplyMethod;
use App\Enums\Currency;
use App\Enums\EmploymentType;
use App\Enums\Experience;
use App\Enums\SalaryPeriod;
use App\Models\Concerns\Favoritable;
use App\Models\Concerns\HasDailyStats;
use App\Models\Concerns\HasLocation;
use App\Models\Concerns\HasMediaGallery;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\LogsStaffActivity;
use App\Models\Concerns\Promotable;
use App\Models\Concerns\Reportable;
use Database\Factories\VacancyFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * A job vacancy. Named Vacancy because "jobs" is Laravel's queue table and App\Jobs its namespace;
 * public URLs and UI copy still say "jobs".
 */
#[Guarded(Listing::SYSTEM_COLUMNS)]
class Vacancy extends Model implements Listing
{
    /** @use HasFactory<VacancyFactory> */
    use Favoritable, HasDailyStats, HasFactory, HasLocation, HasMediaGallery, HasSlug, HasStatus, HasTranslations, LogsStaffActivity, Promotable, Reportable, SoftDeletes;

    protected $table = 'vacancies';

    /** @var list<string> */
    public array $translatable = ['title', 'description', 'address'];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'salary_currency' => Currency::class,
            'salary_period' => SalaryPeriod::class,
            'experience' => Experience::class,
            'apply_method' => ApplyMethod::class,
            'languages' => 'array',
            'benefits' => 'array',
            'deadline' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The employer, when the vacancy is posted by a business rather than an individual.
     *
     * @return BelongsTo<BusinessProfile, $this>
     */
    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<VacancyApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(VacancyApplication::class);
    }
}
