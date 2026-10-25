<?php

namespace App\Models\Concerns;

use App\Models\Report;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Reportable
{
    /**
     * @return MorphMany<Report, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
