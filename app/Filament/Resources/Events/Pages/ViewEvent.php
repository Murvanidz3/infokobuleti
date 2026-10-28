<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Filament\Support\ListingAdmin;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

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
