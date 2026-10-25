<?php

namespace App\Actions\Auth;

use App\Enums\AccountType;
use App\Enums\BusinessType;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteOnboarding
{
    public function handle(User $user, string $name, AccountType $accountType, ?BusinessType $businessType = null, ?string $businessName = null): void
    {
        DB::transaction(function () use ($user, $name, $accountType, $businessType, $businessName): void {
            $user->forceFill([
                'name' => $name,
                'account_type' => $accountType,
                'onboarded_at' => now(),
            ])->save();

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
