<?php

namespace App\Listings\Definitions;

use App\Listings\Field;
use App\Listings\Support\SharedFields;

final class HouseDefinition extends AbstractPropertyDefinition
{
    public function key(): string
    {
        return 'property.house';
    }

    /**
     * @return list<Field>
     */
    protected function typeFields(): array
    {
        return SharedFields::realEstateType();
    }

    /**
     * Apartment facts plus yard area and a floor count. No floor-of-building.
     *
     * @return list<Field>
     */
    protected function detailFields(): array
    {
        return [
            SharedFields::area(required: true),
            SharedFields::landArea(required: false),
            SharedFields::rooms(),
            SharedFields::bedrooms(),
            SharedFields::bathrooms(),
            SharedFields::totalFloors(notBelowFloor: false),
            SharedFields::condition(),
            SharedFields::buildingStatus(),
            SharedFields::cadastralCode(),
            SharedFields::listedBy(),
            SharedFields::features($this->amenityScopes()),
        ];
    }
}
