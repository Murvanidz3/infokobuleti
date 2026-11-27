<?php

namespace App\Listings\Definitions;

final class HouseDefinition extends AbstractPropertyDefinition
{
    public function key(): string
    {
        return 'property.house';
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
