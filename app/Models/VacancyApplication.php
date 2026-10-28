<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\LogsStaffActivity;
use Database\Factories\VacancyApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['vacancy_id', 'user_id', 'cover_note', 'contact_phone'])]
class VacancyApplication extends Model implements HasMedia
{
    /** @use HasFactory<VacancyApplicationFactory> */
    use HasFactory, InteractsWithMedia, LogsStaffActivity;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'status_changed_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cv')
            ->singleFile()
            ->useDisk('local')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
    }

    /**
     * @return BelongsTo<Vacancy, $this>
     */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
