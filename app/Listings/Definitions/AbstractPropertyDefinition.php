<?php

namespace App\Listings\Definitions;

use App\Enums\AmenityScope;
use App\Enums\Currency;
use App\Listings\AbstractListingDefinition;
use App\Listings\Field;
use App\Listings\LocationRules;
use App\Listings\MediaRules;
use App\Listings\PriceKind;
use App\Listings\PriceModel;
use App\Listings\Registry;
use App\Listings\Step;
use App\Listings\Support\SharedFields;

abstract class AbstractPropertyDefinition extends AbstractListingDefinition
{
    /**
     * @return list<Field>
     */
    abstract protected function typeFields(): array;

    /**
     * @return list<Field>
     */
    abstract protected function detailFields(): array;

    /**
     * @return list<Field>
     */
    protected function extraPriceFields(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    protected function amenityScopes(): array
    {
        return [AmenityScope::Property->value];
    }

    /**
     * @return list<Step>
     */
    public function steps(): array
    {
        return [
            new Step('type', 1, $this->typeFields()),
            new Step('details', 2, [
                ...SharedFields::titleAndDescription(),
                ...$this->detailFields(),
            ]),
            new Step('location', 3, SharedFields::location($this->location())),
            new Step('photos', 4, [SharedFields::photos($this->media())]),
            new Step('price', 5, [
                ...SharedFields::fixedPriceAndContact($this->price()),
                ...$this->extraPriceFields(),
            ]),
            new Step('preview', 6, []),
        ];
    }

    public function price(): PriceModel
    {
        return new PriceModel(
            kind: PriceKind::Fixed,
            required: true,
            min: 1,
            max: 100000000000,
            currencies: Currency::values(),
            perSquareMeter: true,
        );
    }

    public function location(): LocationRules
    {
        return new LocationRules(pinRequired: true, locationRequired: true, bounds: Registry::BOUNDS);
    }

    public function media(): MediaRules
    {
        return new MediaRules(min: 1, max: Registry::MAX_PHOTOS, required: true);
    }
}
