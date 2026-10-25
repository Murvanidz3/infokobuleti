<?php

namespace App\Models;

use Database\Factories\RoomAvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-date exception for a room type: closed, a different price or fewer free units.
 */
#[Fillable(['room_type_id', 'date', 'is_closed', 'price_override', 'units_available'])]
class RoomAvailability extends Model
{
    /** @use HasFactory<RoomAvailabilityFactory> */
    use HasFactory;

    protected $table = 'room_availability';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_closed' => 'boolean',
            'price_override' => 'float',
        ];
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
