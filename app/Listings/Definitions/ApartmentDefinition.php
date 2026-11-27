<?php

namespace App\Listings\Definitions;

final class ApartmentDefinition extends AbstractPropertyDefinition
{
    public function key(): string
    {
        return 'property.apartment';
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
