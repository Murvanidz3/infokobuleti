<?php

namespace App\Listings\Definitions;

final class HotelBusinessDefinition extends AbstractPropertyDefinition
{
    // TODO PR2: a hotel sold as a business still uses the apartment field set (no stars, no room model).

    public function key(): string
    {
        return 'property.hotel_business';
    }

    protected function areaRequired(): bool
    {
        return true;
    }

    protected function landAreaRequired(): bool
    {
        return false;
    }
}
