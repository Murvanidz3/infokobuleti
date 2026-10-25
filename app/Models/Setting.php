<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One site setting edited in the admin (key "contacts.phone", value any JSON).
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }
}
