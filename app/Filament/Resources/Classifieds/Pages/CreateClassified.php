<?php

namespace App\Filament\Resources\Classifieds\Pages;

use App\Filament\Resources\Classifieds\ClassifiedResource;
use App\Filament\Support\SavesTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateClassified extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = ClassifiedResource::class;
}
