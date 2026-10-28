<?php

namespace App\Filament\Resources\Places\Pages;

use App\Filament\Resources\Places\PlaceResource;
use App\Filament\Support\ListingAdmin;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlace extends ViewRecord
{
    protected static string $resource = PlaceResource::class;

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
