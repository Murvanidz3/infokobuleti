<?php

namespace App\Listings\Definitions;

final class CommercialDefinition extends AbstractPropertyDefinition
{
    // TODO PR2: commercial listings still use the apartment field set.

    public function key(): string
    {
        return 'property.commercial';
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
