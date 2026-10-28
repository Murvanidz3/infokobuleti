<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\SavesTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = CategoryResource::class;
}
