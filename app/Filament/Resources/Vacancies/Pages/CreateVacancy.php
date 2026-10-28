<?php

namespace App\Filament\Resources\Vacancies\Pages;

use App\Filament\Resources\Vacancies\VacancyResource;
use App\Filament\Support\SavesTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateVacancy extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = VacancyResource::class;
}
