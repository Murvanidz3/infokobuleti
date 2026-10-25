<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

/**
 * Labels for backed enums from lang/{locale}/enums.php, keyed by the snake_case enum name.
 */
trait HasLabel
{
    public function label(): string
    {
        return __('enums.'.self::langKey().'.'.$this->value);
    }

    public static function langKey(): string
    {
        return Str::snake(class_basename(self::class));
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[(string) $case->value] = $case->label();
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => (string) $case->value, self::cases());
    }
}
