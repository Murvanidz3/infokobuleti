<?php

namespace App\Listings;

use Closure;

/**
 * One wizard field: its name, input, whether it is always required, the rules it contributes,
 * and where its options come from.
 */
final readonly class Field
{
    /**
     * @param  Closure(array<string, mixed>): array<string, mixed>  $rules
     */
    public function __construct(
        public string $name,
        public FieldType $type,
        public bool $required,
        private Closure $rules,
        public ?FieldOptions $options = null,
        public ?ShowWhen $showWhen = null,
    ) {}

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public function rulesFor(array $form): array
    {
        return ($this->rules)($form);
    }

    /**
     * @param  array<string, mixed>  $form
     */
    public function visible(array $form): bool
    {
        return $this->showWhen === null || $this->showWhen->visible($form);
    }
}
