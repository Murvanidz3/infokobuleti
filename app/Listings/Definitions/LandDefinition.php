<?php

namespace App\Listings\Definitions;

final class LandDefinition extends AbstractPropertyDefinition
{
    // TODO PR2: land still shows apartment fields (rooms, bedrooms, bathrooms, floor, total floors, condition, building status, amenities).

    public function key(): string
    {
        return 'property.land';
    }

    protected function areaRequired(): bool
    {
        return false;
    }

    protected function landAreaRequired(): bool
    {
        return true;
    }
}
