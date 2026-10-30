<?php

namespace App\Actions\Privacy;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Deleting an account (right to erasure): listings, business pages, favorites, searches, drafts,
 * devices and sign-in links go; the user row is anonymized and soft-deleted. Orders, payments and
 * wallet entries stay (accounting rules) but no longer point to a person; messages stay for the
 * other side of the conversation under "Deleted user".
 */
class DeleteAccount
{
    public function handle(User $user): void
    {
        if ($user->isStaff()) {
            throw new RuntimeException('Staff accounts are removed by a super admin.');
        }

        DB::transaction(function () use ($user): void {
            foreach (['properties', 'projects', 'classifieds', 'vacancies'] as $relation) {
                $user->{$relation}()->get()->each(fn (Model $listing) => $listing->delete());
            }

            $user->businessProfiles()->get()->each(fn (BusinessProfile $profile) => $profile->delete());
            $user->listingDrafts()->get()->each(fn (Model $draft) => $draft->delete());
            $user->favorites()->delete();
            $user->savedSearches()->delete();
            $user->pushSubscriptions()->delete();
            $user->userSessions()->delete();
            $user->notifications()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->clearMediaCollection('avatar');
            $user->roles()->detach();
            $user->forceFill([
                'name' => __('privacy.deleted_user', [], $user->preferredLocale()),
                // An account needs an email or a phone (database rule): a reserved, unreachable address.
                'email' => 'deleted-'.$user->id.'@deleted.invalid',
                'email_verified_at' => null,
                'phone' => null,
                'phone_verified_at' => null,
                'google_id' => null,
                'avatar' => null,
                'telegram_chat_id' => null,
                'password' => Str::random(64),
                'remember_token' => null,
                'notification_preferences' => null,
            ])->save();

            $user->delete();
        });

        activity('privacy')->event('account_deleted')->performedOn($user)->log('account deleted by the user');
    }
}
