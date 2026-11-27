<?php

namespace App\Listings;

use App\Enums\Currency;
use App\Services\PriceFormatter;

/**
 * Review-step facts for one listing definition. Every filled field the wizard is showing
 * becomes a row, in the definition's order, so a new field cannot be left off the summary.
 *
 * Title, description and photos are already drawn above the list. The map pin is not a fact.
 */
final class ReviewSummary
{
    /** @var list<string> */
    private const array SHOWN_ELSEWHERE = ['title', 'description', 'photos', 'lat', 'lng'];

    /**
     * @param  array<string, mixed>  $form
     * @param  array<string, mixed>  $options
     * @return list<array{label: string, value: string}>
     */
    public function facts(ListingDefinition $definition, array $form, array $options, PriceFormatter $price): array
    {
        $facts = [];

        foreach ($definition->steps() as $step) {
            foreach ($step->fields as $field) {
                if (! $field->visible($form)) {
                    continue;
                }

                $value = $this->display($field, $form[$field->name] ?? null, $form, $options, $price);

                if ($value === null || $value === '') {
                    continue;
                }

                $facts[] = [
                    'label' => __('account.wizard.fields.'.$field->name),
                    'value' => $value,
                ];
            }
        }

        return $facts;
    }

    /**
     * @param  array<string, mixed>  $form
     * @param  array<string, mixed>  $options
     */
    private function display(Field $field, mixed $raw, array $form, array $options, PriceFormatter $price): ?string
    {
        if (in_array($field->name, self::SHOWN_ELSEWHERE, true) || $this->blank($raw)) {
            return null;
        }

        if ($field->name === 'price' && is_numeric($raw)) {
            $currency = is_string($form['currency'] ?? null) && in_array($form['currency'], Currency::values(), true)
                ? $form['currency']
                : Currency::GEL->value;

            return $price->format((float) $raw, $currency);
        }

        if (in_array($field->name, ['area_m2', 'land_area_m2'], true) && is_numeric($raw)) {
            return $raw.' m²';
        }

        if ($field->name === 'distance_to_sea_m' && is_numeric($raw)) {
            return $raw.' m';
        }

        $choices = $this->choices($field, $options);

        return match ($field->type) {
            FieldType::Select => $this->labelFrom($choices, (string) $raw),
            FieldType::Multiselect => is_array($raw) ? $this->labelsFrom($choices, $raw) : null,
            FieldType::Boolean => $raw === true || $raw === 1 || $raw === '1' ? __('account.wizard.yes') : null,
            FieldType::Photos, FieldType::Schema, FieldType::TranslatableText, FieldType::TranslatableTextarea => null,
            default => is_scalar($raw) ? (string) $raw : null,
        };
    }

    private function blank(mixed $value): bool
    {
        if ($value === null || $value === false || $value === '' || $value === []) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }

    /**
     * Wizard option lists use a few shorter keys than {@see FieldOptions}.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, string>
     */
    private function choices(Field $field, array $options): array
    {
        $key = match ($field->options) {
            null => null,
            FieldOptions::LandPurposes => 'purposes',
            FieldOptions::LandUtilities => 'utilities',
            FieldOptions::PropertyConditions => 'conditions',
            FieldOptions::PropertyFeatures => 'features',
            default => $field->options->value,
        };

        if ($key === null || ! isset($options[$key]) || ! is_array($options[$key])) {
            return [];
        }

        $choices = [];

        foreach ($options[$key] as $choiceKey => $label) {
            if (is_string($label) && $label !== '') {
                $choices[(string) $choiceKey] = $label;
            }
        }

        return $choices;
    }

    /**
     * @param  array<string, string>  $choices
     */
    private function labelFrom(array $choices, string $selected): string
    {
        return $choices[$selected] ?? $selected;
    }

    /**
     * Labels follow the option list, which is the order the wizard showed.
     *
     * @param  array<string, string>  $choices
     * @param  array<mixed>  $selected
     */
    private function labelsFrom(array $choices, array $selected): ?string
    {
        $picked = array_flip(array_map(strval(...), $selected));
        $labels = [];

        foreach ($choices as $key => $label) {
            if (array_key_exists($key, $picked)) {
                $labels[] = $label;
            }
        }

        return $labels === [] ? null : implode(', ', $labels);
    }
}
