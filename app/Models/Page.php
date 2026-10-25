<?php

namespace App\Models;

use App\Models\Concerns\LogsStaffActivity;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Static pages (about, terms, privacy, contacts), edited in the admin.
 */
#[Guarded(['id'])]
class Page extends Model
{
    use HasTranslations, LogsStaffActivity;

    /** @var list<string> */
    public array $translatable = ['title', 'body', 'meta_description'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}
