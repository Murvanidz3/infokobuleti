<?php

namespace App\Listings\Definitions;

use App\Listings\Field;
use App\Listings\Support\SharedFields;

final class ApartmentDefinition extends AbstractPropertyDefinition
{
    public function key(): string
    {
        return 'property.apartment';
    }

    /**
     * @return list<Field>
     */
    protected function typeFields(): array
    {
        return SharedFields::realEstateType();
    }

    /**
     * Rooms, bedrooms, bathrooms, floor and floors, area, condition, building status,
     * amenities, and the apartment number grid (cadastral code and who is listing).
     *
     * @return list<Field>
     */
    protected function detailFields(): array
    {
        return [
            SharedFields::area(required: true),
            SharedFields::rooms(),
            SharedFields::bedrooms(),
            SharedFields::bathrooms(),
            SharedFields::floor(),
            SharedFields::totalFloors(),
            SharedFields::condition(),
            SharedFields::buildingStatus(),
            SharedFields::cadastralCode(),
            SharedFields::listedBy(),
            SharedFields::features($this->amenityScopes()),
        ];
    }
}
