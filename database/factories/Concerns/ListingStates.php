<?php

namespace Database\Factories\Concerns;

use App\Enums\ListingStatus;

/**
 * Status states for module factories. Definitions start from listingAttributes(), i.e. published.
 */
trait ListingStates
{
    /**
     * Published with realistic dates and counters.
     *
     * @return array<string, mixed>
     */
    protected function listingAttributes(): array
    {
        $attributes = [
            'status' => ListingStatus::Published,
            'published_at' => fake()->dateTimeBetween('-25 days', '-1 hour'),
            'views_count' => fake()->numberBetween(5, 2400),
        ];

        if ($this->expires()) {
            $attributes['expires_at'] = now()->addDays(fake()->numberBetween(5, 60));
        }

        return $attributes;
    }

    public function published(): static
    {
        return $this->withStatus(ListingStatus::Published, ['published_at' => now()->subDay()]);
    }

    public function draft(): static
    {
        return $this->withStatus(ListingStatus::Draft, ['published_at' => null]);
    }

    public function pending(): static
    {
        return $this->withStatus(ListingStatus::Pending, ['published_at' => null]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function withStatus(ListingStatus $status, array $extra = []): static
    {
        return $this->state(function () use ($status, $extra): array {
            $attributes = ['status' => $status, ...$extra];

            if ($this->expires() && ! array_key_exists('expires_at', $extra)) {
                $attributes['expires_at'] = $status === ListingStatus::Published ? now()->addDays(30) : null;
            }

            return $attributes;
        });
    }

    private function expires(): bool
    {
        return $this->newModel()->expiryDays() !== null;
    }
}
