<?php

namespace App\Listings\Support;

use App\Enums\CategoryModule;
use App\Models\Category;
use Closure;

/**
 * Cross-field checks the wizard already applies. Kept as the same closures so the messages stay
 * the ones ListingForm used before the registry.
 */
final class Constraints
{
    /**
     * An active category of the module without subcategories (listings go into the most specific one).
     */
    public static function leafCategory(CategoryModule $module): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($module): void {
            $valid = is_numeric($value) && Category::query()
                ->whereKey((int) $value)
                ->where('module', $module->value)
                ->where('is_active', true)
                ->whereDoesntHave('children', fn ($query) => $query->where('is_active', true))
                ->exists();

            if (! $valid) {
                $fail(__('account.wizard.errors.category'));
            }
        };
    }

    /**
     * The value must not be lower than another (optional) field's value.
     */
    public static function notBelow(mixed $other): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($other): void {
            if (is_numeric($value) && is_numeric($other) && (float) $value < (float) $other) {
                $fail(__('account.wizard.errors.not_below'));
            }
        };
    }

    /**
     * Category-schema rules of a classified, keyed under attribute_values.
     *
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public static function schemaRules(array $form): array
    {
        $category = Category::query()->find(self::int($form['category_id'] ?? null));

        if ($category === null) {
            return [];
        }

        return $category->schema()->rules('attribute_values');
    }

    private static function int(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
