<?php

namespace App\Listings;

/**
 * One wizard step and the fields edited on it, in validation order.
 */
final readonly class Step
{
    /**
     * @param  list<Field>  $fields
     */
    public function __construct(
        public string $key,
        public int $order,
        public array $fields,
    ) {}

    /**
     * @return list<string>
     */
    public function fieldNames(): array
    {
        return array_map(fn (Field $field): string => $field->name, $this->fields);
    }
}
