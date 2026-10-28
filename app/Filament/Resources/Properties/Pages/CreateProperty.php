<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Support\SavesTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateProperty extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = PropertyResource::class;
}
