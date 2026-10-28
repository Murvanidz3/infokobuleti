<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\SavesTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = ArticleResource::class;
}
