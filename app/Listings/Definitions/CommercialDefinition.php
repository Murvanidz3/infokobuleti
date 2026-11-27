<?php

namespace App\Listings\Definitions;

use App\Listings\Field;
use App\Listings\Support\SharedFields;

final class CommercialDefinition extends AbstractPropertyDefinition
{
    public function key(): string
    {
        return 'property.commercial';
    }

    /**
     * @return list<Field>
     */
    protected function typeFields(): array
    {
        return [
            ...SharedFields::realEstateType(),
            SharedFields::commercialSubtype(),
        ];
    }

    /**
     * Subtype is chosen on the rubric step. Area and floor only — no room grid.
     *
     * @return list<Field>
     */
    protected function detailFields(): array
    {
        return [
            SharedFields::area(required: true),
            SharedFields::floor(),
            SharedFields::totalFloors(),
        ];
    }
}
