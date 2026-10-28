<?php

namespace App\Models;

use App\Models\Concerns\LogsStaffActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A reusable SMS text for single sends and campaigns.
 */
#[Fillable(['name', 'body'])]
class SmsTemplate extends Model
{
    use LogsStaffActivity;
}
