<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Category-specific fields stored in categories.attribute_schema and the values a classified
 * stores in attribute_values.
 *
 * Field shape: {key, type: text|number|select|multiselect|boolean, label: {ka, en, ru},
 * options?: [{value, label: {ka, en, ru}}], required?, unit?, min?, max?, filterable?}
 *
 * @phpstan-type Field array{key: string, type: string, label: array<string, string>, options?: list<array{value: string, label: array<string, string>}>, required?: bool, unit?: string, min?: int|float, max?: int|float, filterable?: bool}
 */
final readonly class AttributeSchema
{
    public const array TYPES = ['text', 'number', 'select', 'multiselect', 'boolean'];

    /**
     * @param  list<Field>  $fields
     */
    public function __construct(private array $fields) {}

    /**
     * @return list<Field>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function isEmpty(): bool
    {
        return $this->fields === [];
    }

    /**
     * Laravel validation rules, keyed "{$prefix}.{key}".
     *
     * @return array<string, list<mixed>>
     */
    public function rules(string $prefix = 'attribute_values'): array
    {
        $rules = [$prefix => ['array']];

        foreach ($this->fields as $field) {
            $key = "{$prefix}.{$field['key']}";
            $base = [($field['required'] ?? false) ? 'required' : 'nullable'];
            $options = array_column($field['options'] ?? [], 'value');

            $rules[$key] = match ($field['type']) {
                'number' => [...$base, 'numeric', ...$this->bounds($field)],
                'select' => [...$base, 'string', Rule::in($options)],
                'multiselect' => [...$base, 'array'],
                'boolean' => [...$base, 'boolean'],
                default => [...$base, 'string', 'max:255'],
            };

            if ($field['type'] === 'multiselect') {
                $rules["{$key}.*"] = ['string', Rule::in($options)];
            }
        }

        return $rules;
    }

    /**
     * Validates the values and returns only the keys the schema knows about.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $values): array
    {
        $validated = Validator::make(['attribute_values' => $values], $this->rules())->validate();

        return array_intersect_key($validated['attribute_values'] ?? [], array_flip(array_column($this->fields, 'key')));
    }

    /**
     * @param  Field  $field
     * @return list<string>
     */
    private function bounds(array $field): array
    {
        return array_values(array_filter([
            isset($field['min']) ? 'min:'.$field['min'] : null,
            isset($field['max']) ? 'max:'.$field['max'] : null,
        ]));
    }
}
