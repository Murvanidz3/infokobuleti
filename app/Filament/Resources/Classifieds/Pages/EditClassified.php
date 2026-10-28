<?php

namespace App\Filament\Resources\Classifieds\Pages;

use App\Filament\Resources\Classifieds\ClassifiedResource;
use App\Filament\Support\SavesTranslations;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClassified extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = ClassifiedResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }
}
