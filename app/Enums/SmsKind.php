<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * What an SMS in the history was, read from its purpose.
 */
enum SmsKind: string implements FilamentLabel, HasColor
{
    use HasLabel;

    case Code = 'code';
    case Single = 'single';
    case Campaign = 'campaign';
    case Test = 'test';

    public static function fromPurpose(string $purpose): self
    {
        return match (true) {
            str_starts_with($purpose, 'otp.') => self::Code,
            $purpose === 'campaign' => self::Campaign,
            $purpose === 'campaign.test' => self::Test,
            default => self::Single,
        };
    }

    /** SQL LIKE pattern / exact purposes of the kind. */
    public function purposes(): string
    {
        return match ($this) {
            self::Code => 'otp.%',
            self::Single => 'admin.single',
            self::Campaign => 'campaign',
            self::Test => 'campaign.test',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Code => 'gray',
            self::Single => 'info',
            self::Campaign => 'primary',
            self::Test => 'warning',
        };
    }
}
