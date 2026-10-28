<?php

namespace App\Filament\Resources\Places\Pages;

use App\Filament\Resources\Places\PlaceResource;
use App\Filament\Support\SavesTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreatePlace extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = PlaceResource::class;
}
