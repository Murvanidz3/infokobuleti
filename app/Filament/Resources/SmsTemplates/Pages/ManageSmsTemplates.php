<?php

namespace App\Filament\Resources\SmsTemplates\Pages;

use App\Filament\Resources\SmsTemplates\SmsTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSmsTemplates extends ManageRecords
{
    protected static string $resource = SmsTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
