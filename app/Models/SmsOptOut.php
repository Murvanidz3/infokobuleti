<?php

namespace App\Models;

use App\Models\Concerns\LogsStaffActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Numbers that asked not to get campaigns. Campaigns skip them; sign-in codes still reach them.
 */
#[Fillable(['phone', 'reason', 'created_by'])]
class SmsOptOut extends Model
{
    use LogsStaffActivity;

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
