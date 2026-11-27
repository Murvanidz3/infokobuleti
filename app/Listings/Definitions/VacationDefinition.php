<?php

namespace App\Listings\Definitions;

use App\Enums\AmenityScope;
use App\Enums\Currency;
use App\Listings\Field;
use App\Listings\PriceKind;
use App\Listings\PriceModel;
use App\Listings\Support\SharedFields;

/**
 * Daily-rent stays. The deal is always daily rent, so the rubric step does not ask it.
 * Stars show only for a hotel. Room count shows for a hotel, guesthouse or hostel.
 */
final class VacationDefinition extends AbstractPropertyDefinition
{
    public function key(): string
    {
        return 'property.vacation';
    }

    /**
     * @return list<Field>
     */
    protected function typeFields(): array
    {
        return [SharedFields::vacationType()];
    }

    /**
     * @return list<Field>
     */
    protected function detailFields(): array
    {
        return [
            SharedFields::guests(),
            SharedFields::bedrooms(),
            SharedFields::distanceToSea(),
            SharedFields::checkIn(),
            SharedFields::checkOut(),
            SharedFields::stars(),
            SharedFields::roomsCount(),
            SharedFields::features($this->amenityScopes()),
        ];
    }

    /**
     * @return list<Field>
     */
    protected function extraPriceFields(): array
    {
        return [SharedFields::website()];
    }

    public function price(): PriceModel
    {
        return new PriceModel(
            kind: PriceKind::Fixed,
            required: true,
            min: 1,
            max: 100000000000,
            currencies: Currency::values(),
            perSquareMeter: false,
        );
    }

    /**
     * @return list<string>
     */
    protected function amenityScopes(): array
    {
        return [AmenityScope::Property->value, AmenityScope::Hotel->value];
    }
}
