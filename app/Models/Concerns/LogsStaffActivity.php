<?php

namespace App\Models\Concerns;

use Filament\Facades\Filament;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Records every create, update and delete made by staff in the admin panel (log "admin"), with
 * the changed attributes. Changes made elsewhere (owners, seeders, jobs) are not logged here.
 */
trait LogsStaffActivity
{
    use LogsActivity {
        shouldLogEvent as protected shouldLogModelEvent;
    }

    /**
     * Attributes that are never written to the activity log.
     *
     * @var list<string>
     */
    protected static array $activityLogExcept = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
        'embedding', 'views_count', 'created_at', 'updated_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('admin')
            ->logAll()
            ->logExcept(static::$activityLogExcept)
            ->logOnlyDirty();
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        return Filament::isServing() && auth()->check() && $this->shouldLogModelEvent($eventName);
    }
}
