<?php

namespace App\Models;

use Database\Factories\DailyStatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['statable_type', 'statable_id', 'date', 'views', 'phone_reveals', 'favorites', 'messages'])]
#[WithoutTimestamps]
class DailyStat extends Model
{
    /** @use HasFactory<DailyStatFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function statable(): MorphTo
    {
        return $this->morphTo();
    }
}
