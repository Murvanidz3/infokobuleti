<?php

namespace App\Filament\Resources\Hotels\Pages;

use App\Filament\Resources\Hotels\HotelResource;
use App\Filament\Support\SavesTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateHotel extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = HotelResource::class;
}
