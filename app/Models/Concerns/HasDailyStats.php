<?php

namespace App\Models\Concerns;

use App\Models\DailyStat;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDailyStats
{
    /**
     * @return MorphMany<DailyStat, $this>
     */
    public function dailyStats(): MorphMany
    {
        return $this->morphMany(DailyStat::class, 'statable');
    }
}
