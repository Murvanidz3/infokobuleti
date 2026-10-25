<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'avatar', 'locale', 'account_type'])]
#[Hidden(['password', 'remember_token', 'google_id', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'account_type' => AccountType::class,
            'is_blocked' => 'boolean',
            'onboarded_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value === null ? null : mb_strtolower(trim($value)));
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(array_map(
            fn (Role $role): string => $role->value,
            array_filter(Role::cases(), fn (Role $role): bool => $role->isStaff()),
        ));
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone !== null && $this->phone_verified_at !== null;
    }

    public function hasGoogle(): bool
    {
        return $this->google_id !== null;
    }

    public function isOnboarded(): bool
    {
        return $this->onboarded_at !== null;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Ways this account can sign in: Google, a verified phone, and email + password for staff.
     *
     * @return list<string>
     */
    public function signInMethods(): array
    {
        return array_keys(array_filter([
            'google' => $this->hasGoogle(),
            'phone' => $this->hasVerifiedPhone(),
            'password' => $this->password !== null && $this->isStaff(),
        ]));
    }

    /**
     * A sign-in method can be removed only while another one remains.
     */
    public function canRemoveSignInMethod(string $method): bool
    {
        $methods = $this->signInMethods();

        return in_array($method, $methods, true) && count($methods) > 1;
    }

    /**
     * Initials for the avatar placeholder in the header.
     */
    public function initials(): string
    {
        $name = trim((string) $this->name);

        if ($name === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $name) ?: [];
        $initials = mb_substr($words[0], 0, 1);

        if (count($words) > 1) {
            $initials .= mb_substr((string) end($words), 0, 1);
        }

        return mb_strtoupper($initials);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    /**
     * @return HasMany<BusinessProfile, $this>
     */
    public function businessProfiles(): HasMany
    {
        return $this->hasMany(BusinessProfile::class);
    }

    /**
     * @return HasMany<Hotel, $this>
     */
    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class);
    }

    /**
     * @return HasMany<Property, $this>
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /**
     * @return HasMany<Classified, $this>
     */
    public function classifieds(): HasMany
    {
        return $this->hasMany(Classified::class);
    }

    /**
     * @return HasMany<Vacancy, $this>
     */
    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }

    /**
     * @return HasMany<VacancyApplication, $this>
     */
    public function vacancyApplications(): HasMany
    {
        return $this->hasMany(VacancyApplication::class);
    }

    /**
     * @return HasMany<BookingRequest, $this>
     */
    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class);
    }

    /**
     * @return HasMany<Favorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<SavedSearch, $this>
     */
    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    /**
     * @return BelongsToMany<Conversation, $this>
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['last_read_at', 'archived_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<UserSession, $this>
     */
    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
