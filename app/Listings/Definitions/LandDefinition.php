<?php

namespace App\Listings\Definitions;

use App\Listings\Field;
use App\Listings\Support\SharedFields;

final class LandDefinition extends AbstractPropertyDefinition
{
    public function key(): string
    {
        return 'property.land';
    }

    /**
     * @return list<Field>
     */
    protected function typeFields(): array
    {
        return SharedFields::realEstateType();
    }

    /**
     * Plot size, purpose and utilities. No rooms, floor, condition or building status.
     *
     * @return list<Field>
     */
    protected function detailFields(): array
    {
        return [
            SharedFields::landArea(required: true),
            SharedFields::landPurpose(),
            SharedFields::landUtilities(),
        ];
    }
}
