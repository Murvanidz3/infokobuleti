<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum BedType: string
{
    use HasLabel;

    case Single = 'single';
    case Double = 'double';
    case Twin = 'twin';
    case Sofa = 'sofa';
    case Bunk = 'bunk';
}
