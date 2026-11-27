<?php

namespace App\Listings;

/**
 * When the wizard shows a field. Rules are still emitted when this is false: today's validator
 * keeps apply-by-phone and apply-by-email rules even while those inputs are hidden.
 */
final readonly class ShowWhen
{
    public const string ALWAYS = 'always';

    public const string EQUALS = 'equals';

    public const string IN = 'in';

    public function __construct(
        public string $mode,
        public ?string $field = null,
        public mixed $value = null,
    ) {}

    public static function always(): self
    {
        return new self(self::ALWAYS);
    }

    public static function equals(string $field, string $value): self
    {
        return new self(self::EQUALS, $field, $value);
    }

    /**
     * @param  list<string>  $values
     */
    public static function in(string $field, array $values): self
    {
        return new self(self::IN, $field, $values);
    }

    /**
     * @param  array<string, mixed>  $form
     */
    public function visible(array $form): bool
    {
        if ($this->mode === self::ALWAYS) {
            return true;
        }

        $current = (string) ($form[$this->field] ?? '');

        if ($this->mode === self::IN) {
            return in_array($current, array_map(strval(...), (array) $this->value), true);
        }

        return $current === (string) $this->value;
    }
}
