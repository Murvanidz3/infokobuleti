<?php

namespace App\Listings\Definitions;

final class ClassifiedServiceDefinition extends AbstractClassifiedDefinition
{
    /**
     * Category slug, including its children, that selects this definition.
     */
    public const string CATEGORY_SLUG = 'services';

    // TODO PR2: services still show item condition (new / used), the same field as goods.

    public function key(): string
    {
        return 'classified.service';
    }
}
