<?php

namespace App\Filament\Resources\SmsCampaigns\Pages;

use App\Filament\Resources\SmsCampaigns\SmsCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmsCampaigns extends ListRecords
{
    protected static string $resource = SmsCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
