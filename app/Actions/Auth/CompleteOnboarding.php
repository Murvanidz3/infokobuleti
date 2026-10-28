<?php

namespace App\Actions\Auth;

use App\Enums\AccountType;
use App\Enums\BusinessType;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteOnboarding
{
    /**
     * @param  string|null  $email  for accounts without one (phone sign-ups); stored unverified
     */
    public function handle(User $user, string $name, AccountType $accountType, ?BusinessType $businessType = null, ?string $businessName = null, ?string $email = null): void
    {
        DB::transaction(function () use ($user, $name, $accountType, $businessType, $businessName, $email): void {
            $user->forceFill(array_filter([
                'name' => $name,
                'account_type' => $accountType,
                'onboarded_at' => now(),
                'email' => $user->email === null ? $email : null,
            ], fn ($value) => $value !== null))->save();

            if ($accountType === AccountType::Business && $businessType !== null && filled($businessName)) {
                $user->businessProfiles()->create([
                    'type' => $businessType,
                    'name' => $businessName,
                    'phone' => $user->phone,
                    'email' => $user->email,
                ]);

                $user->assignRole(Role::Business->value);
            }
        });
    }
}
