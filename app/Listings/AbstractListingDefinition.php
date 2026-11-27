<?php

namespace App\Listings;

abstract class AbstractListingDefinition implements ListingDefinition
{
    public function step(string $key): ?Step
    {
        foreach ($this->steps() as $step) {
            if ($step->key === $key) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public function rulesFor(string $step, array $form): array
    {
        $found = $this->step($step);

        if ($found === null) {
            return [];
        }

        $rules = [];

        foreach ($found->fields as $field) {
            foreach ($field->rulesFor($form) as $key => $rule) {
                $rules[$key] = $rule;
            }
        }

        return $rules;
    }
}
