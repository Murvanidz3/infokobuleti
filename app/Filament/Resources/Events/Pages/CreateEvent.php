<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Filament\Support\SavesTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = EventResource::class;
}
