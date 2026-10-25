<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * What an SMS code proves: signing in with a phone, or owning a new number before it is saved.
 */
enum OtpPurpose: string implements FilamentLabel
{
    use HasLabel;

    case Login = 'login';
    case ChangePhone = 'change_phone';
}
