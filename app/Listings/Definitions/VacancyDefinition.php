<?php

namespace App\Listings\Definitions;

use App\Enums\Currency;
use App\Listings\AbstractListingDefinition;
use App\Listings\LocationRules;
use App\Listings\MediaRules;
use App\Listings\PriceKind;
use App\Listings\PriceModel;
use App\Listings\Registry;
use App\Listings\Step;
use App\Listings\Support\SharedFields;

final class VacancyDefinition extends AbstractListingDefinition
{
    public function key(): string
    {
        return 'vacancy';
    }

    /**
     * @return list<Step>
     */
    public function steps(): array
    {
        return [
            new Step('type', 1, SharedFields::vacancyType()),
            new Step('details', 2, [
                ...SharedFields::titleAndDescription(),
                ...SharedFields::vacancySpecifics(),
            ]),
            new Step('location', 3, SharedFields::location($this->location())),
            new Step('photos', 4, [SharedFields::photos($this->media())]),
            new Step('price', 5, SharedFields::salaryAndApplication($this->price())),
            new Step('preview', 6, []),
        ];
    }

    public function price(): PriceModel
    {
        return new PriceModel(
            kind: PriceKind::SalaryRange,
            required: false,
            min: 0,
            max: 1000000,
            currencies: Currency::values(),
            perSquareMeter: false,
        );
    }

    public function location(): LocationRules
    {
        return new LocationRules(pinRequired: false, locationRequired: true, bounds: Registry::BOUNDS);
    }

    public function media(): MediaRules
    {
        return new MediaRules(min: 0, max: Registry::MAX_PHOTOS, required: false);
    }
}
