<?php

namespace App\Filament\Resources\Classifieds\Pages;

use App\Filament\Resources\Classifieds\ClassifiedResource;
use App\Filament\Support\ListingAdmin;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClassified extends ViewRecord
{
    protected static string $resource = ClassifiedResource::class;

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
