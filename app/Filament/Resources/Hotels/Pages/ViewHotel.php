<?php

namespace App\Filament\Resources\Hotels\Pages;

use App\Filament\Resources\Hotels\HotelResource;
use App\Filament\Support\ListingAdmin;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHotel extends ViewRecord
{
    protected static string $resource = HotelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ListingAdmin::approveAction(),
            ListingAdmin::rejectAction(),
            ListingAdmin::changeStatusAction(),
            EditAction::make(),
        ];
    }
}
