<?php

namespace App\Listings;

/**
 * The wizard contract for one listing kind: steps, fields, price, place and photos.
 */
interface ListingDefinition
{
    public function key(): string;

    /**
     * @return list<Step>
     */
    public function steps(): array;

    public function price(): PriceModel;

    public function location(): LocationRules;

    public function media(): MediaRules;

    public function step(string $key): ?Step;

    /**
     * Validation rules of one step, unprefixed. Hidden fields still contribute rules.
     *
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public function rulesFor(string $step, array $form): array;
}
