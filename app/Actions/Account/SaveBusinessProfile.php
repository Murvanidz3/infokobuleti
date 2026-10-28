<?php

namespace App\Actions\Account;

use App\Enums\AccountType;
use App\Enums\BusinessType;
use App\Enums\Role;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Services\Media\ImageSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates the user's business profile. Creating one turns an individual account into a
 * business account. The verified badge is only ever set by staff.
 */
class SaveBusinessProfile
{
    /**
     * @param  array{type: string, name: string, description: array<string, string|null>, phone: ?string, email: ?string, website: ?string, address: ?string, location_id: ?int}  $data
     */
    public function handle(User $user, ?BusinessProfile $profile, array $data, ?UploadedFile $logo = null): BusinessProfile
    {
        return DB::transaction(function () use ($user, $profile, $data, $logo): BusinessProfile {
            $profile ??= new BusinessProfile(['user_id' => $user->getKey()]);

            $profile->fill([
                'type' => BusinessType::from($data['type']),
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'website' => $data['website'],
                'location_id' => $data['location_id'],
            ]);
            $profile->setTranslations('description', array_filter($data['description'], fn (?string $text): bool => filled($text)));
            $profile->setTranslations('address', array_filter(['ka' => $data['address']], fn (?string $text): bool => filled($text)));
            $profile->save();

            if ($logo !== null) {
                // Re-encoded first: metadata goes, and anything that is not a real image is refused.
                app(ImageSanitizer::class)->sanitize($logo->getRealPath());
                $profile->addMedia($logo)->toMediaCollection('logo');
            }

            if ($user->account_type !== AccountType::Business) {
                $user->forceFill(['account_type' => AccountType::Business])->save();
            }

            if (! $user->hasRole(Role::Business->value)) {
                $user->assignRole(Role::Business->value);
            }

            return $profile;
        });
    }
}
