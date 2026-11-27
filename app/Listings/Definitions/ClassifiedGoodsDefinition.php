<?php

namespace App\Listings\Definitions;

final class ClassifiedGoodsDefinition extends AbstractClassifiedDefinition
{
    public function key(): string
    {
        return 'classified.goods';
    }
}
