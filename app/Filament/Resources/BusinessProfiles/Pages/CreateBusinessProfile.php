<?php

namespace App\Filament\Resources\BusinessProfiles\Pages;

use App\Filament\Resources\BusinessProfiles\BusinessProfileResource;
use App\Filament\Support\SavesTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessProfile extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = BusinessProfileResource::class;
}
