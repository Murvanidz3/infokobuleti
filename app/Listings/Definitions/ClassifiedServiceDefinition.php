<?php

namespace App\Listings\Definitions;

final class ClassifiedServiceDefinition extends AbstractClassifiedDefinition
{
    /**
     * Category slug, including its children, that selects this definition.
     */
    public const string CATEGORY_SLUG = 'services';

    public function key(): string
    {
        return 'classified.service';
    }

    protected function asksForCondition(): bool
    {
        return false;
    }
}
