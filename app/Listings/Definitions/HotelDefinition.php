<?php

namespace App\Listings\Definitions;

use App\Enums\AmenityScope;

final class HotelDefinition extends AbstractPropertyDefinition
{
    // TODO PR2: a hotel listing still shows the apartment number grid. Built area stays optional; the price is one amount in any currency.

    public function key(): string
    {
        return 'property.hotel';
    }

    protected function areaRequired(): bool
    {
        return false;
    }

    protected function landAreaRequired(): bool
    {
        return false;
    }

    /**
     * @return list<string>
     */
    protected function amenityScopes(): array
    {
        return [AmenityScope::Property->value, AmenityScope::Hotel->value];
    }
}
