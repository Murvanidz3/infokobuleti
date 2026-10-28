<?php

namespace App\Filament\Resources\Classifieds\Pages;

use App\Filament\Resources\Classifieds\ClassifiedResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClassifieds extends ListRecords
{
    protected static string $resource = ClassifiedResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
