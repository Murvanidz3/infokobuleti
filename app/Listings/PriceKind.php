<?php

namespace App\Listings;

enum PriceKind: string
{
    case Fixed = 'fixed';
    case OptionalQuote = 'optional_quote';
    case SalaryRange = 'salary_range';
}
