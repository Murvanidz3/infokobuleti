<?php

namespace App\Actions\Privacy;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Everything the site keeps about a user, as one JSON file on the private disk (right of access
 * under Georgia's Law on Personal Data Protection). The file is removed after DAYS days.
 */
class ExportUserData
{
    public const int DAYS = 7;

    public const string DIRECTORY = 'exports';

    /** Columns that are secrets or internal bookkeeping, never exported. */
    private const array HIDDEN = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'search_vector', 'embedding'];

    /**
     * Writes the file and returns its path on the "local" disk.
     */
    public function handle(User $user): string
    {
        $path = self::DIRECTORY.'/'.$user->id.'-'.Str::random(32).'.json';
        Storage::disk('local')->put($path, (string) json_encode($this->data($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(User $user): array
    {
        $user->loadMissing(['businessProfiles', 'hotels', 'properties', 'classifieds', 'vacancies', 'vacancyApplications', 'bookingRequests', 'favorites', 'reviews', 'savedSearches', 'userSessions', 'orders', 'walletTransactions', 'businessSubscriptions', 'pushSubscriptions']);

        return [
            'exported_at' => now()->toIso8601String(),
            'site' => config('app.url'),
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
                'google_linked' => $user->google_id !== null,
                'telegram_linked' => $user->telegram_chat_id !== null,
                'locale' => $user->locale,
                'account_type' => $user->account_type->value,
                'balance' => (string) $user->balance,
                'created_at' => $user->created_at?->toIso8601String(),
                'last_seen_at' => $user->last_seen_at?->toIso8601String(),
                'notification_preferences' => $user->getAttribute('notification_preferences'),
            ],
            'business_profiles' => $this->rows($user->businessProfiles),
            'listings' => [
                'hotels' => $this->rows($user->hotels),
                'properties' => $this->rows($user->properties),
                'classifieds' => $this->rows($user->classifieds),
                'vacancies' => $this->rows($user->vacancies),
            ],
            'job_applications' => $this->rows($user->vacancyApplications),
            'booking_requests' => $this->rows($user->bookingRequests),
            'favorites' => $this->rows($user->favorites),
            'reviews' => $this->rows($user->reviews),
            'saved_searches' => $this->rows($user->savedSearches),
            'messages_sent' => $this->rows(Message::query()->where('user_id', $user->id)->orderBy('id')->get()),
            'sessions' => $this->rows($user->userSessions),
            'orders' => $this->rows($user->orders),
            'wallet' => $this->rows($user->walletTransactions),
            'business_packages' => $this->rows($user->businessSubscriptions),
            'push_devices' => $user->pushSubscriptions->count(),
        ];
    }

    /**
     * @param  Collection<int, covariant Model>  $records
     * @return list<array<string, mixed>>
     */
    private function rows(Collection $records): array
    {
        return $records->map(function (Model $record): array {
            $attributes = $record->attributesToArray();

            if (method_exists($record, 'getTranslations')) {
                foreach ($record->translatable ?? [] as $field) {
                    $attributes[$field] = $record->getTranslations($field);
                }
            }

            return array_diff_key($attributes, array_flip(self::HIDDEN));
        })->values()->all();
    }
}
