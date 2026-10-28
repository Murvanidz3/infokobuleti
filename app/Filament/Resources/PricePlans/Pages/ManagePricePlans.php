<?php

namespace App\Filament\Resources\PricePlans\Pages;

use App\Filament\Resources\PricePlans\PricePlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePricePlans extends ManageRecords
{
    protected static string $resource = PricePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
