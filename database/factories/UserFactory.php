<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\User;
use Database\Factories\Support\Kobuleti;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state: a phone-verified individual.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Kobuleti::name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => Kobuleti::phone(),
            'phone_verified_at' => now(),
            'locale' => 'ka',
            'account_type' => AccountType::Individual,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);
    }

    public function phoneOnly(): static
    {
        return $this->state(fn (array $attributes) => ['email' => null, 'email_verified_at' => null]);
    }

    public function google(): static
    {
        return $this->state(fn (array $attributes) => [
            'google_id' => fake()->unique()->numerify('1#################'),
            'phone' => null,
            'phone_verified_at' => null,
        ]);
    }

    public function business(): static
    {
        return $this->state(fn (array $attributes) => ['account_type' => AccountType::Business]);
    }

    /**
     * Staff log in with email and password.
     */
    public function staff(?string $password = null): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => $password !== null ? Hash::make($password) : (static::$password ??= Hash::make('password')),
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => ['is_blocked' => true, 'blocked_reason' => 'spam']);
    }
}
