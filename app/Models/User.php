<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\NotificationTopic;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Concerns\LogsStaffActivity;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'avatar', 'locale', 'account_type'])]
#[Hidden(['password', 'remember_token', 'google_id', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasLocalePreference, HasMedia, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, LogsStaffActivity, Notifiable, SoftDeletes;

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
            'notification_preferences' => 'array',
            'balance' => 'decimal:2',
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

    /**
     * The admin panel is for staff who passed the staff sign-in with two-factor authentication.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return ! $this->is_blocked
            && $this->isStaff()
            && $this->hasTwoFactorEnabled()
            && $this->can(Permission::AccessAdmin->value);
    }

    public function getFilamentName(): string
    {
        return $this->name ?? $this->email ?? (string) $this->phone;
    }

    /**
     * Notifications (moderation results and the like) go out in the user's language.
     */
    public function preferredLocale(): string
    {
        return $this->locale ?: config('locales.default');
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
     * Whether this user also wants notifications of a topic by email (on unless switched off).
     */
    public function wantsEmailFor(NotificationTopic $topic): bool
    {
        return $this->email !== null && ! $this->trashed() && ($this->notification_preferences[$topic->value] ?? true) !== false;
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
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
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
     * Users this user blocked in messages.
     *
     * @return BelongsToMany<User, $this>
     */
    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'user_id', 'blocked_user_id')->withPivot('created_at');
    }

    /**
     * Whether either user blocked the other.
     */
    public function isBlockedWith(User $other): bool
    {
        return DB::table('user_blocks')
            ->where(fn ($query) => $query->where('user_id', $this->getKey())->where('blocked_user_id', $other->getKey()))
            ->orWhere(fn ($query) => $query->where('user_id', $other->getKey())->where('blocked_user_id', $this->getKey()))
            ->exists();
    }

    /**
     * @return HasMany<ListingDraft, $this>
     */
    public function listingDrafts(): HasMany
    {
        return $this->hasMany(ListingDraft::class);
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

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<WalletTransaction, $this>
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * @return HasMany<BusinessSubscription, $this>
     */
    public function businessSubscriptions(): HasMany
    {
        return $this->hasMany(BusinessSubscription::class);
    }

    /**
     * The business package running now, if any (the one that lasts longest).
     */
    public function activeBusinessSubscription(): ?BusinessSubscription
    {
        return $this->businessSubscriptions()->active()->latest('ends_at')->first();
    }

    /**
     * Browsers that accept web push notifications for this user.
     *
     * @return HasMany<PushSubscription, $this>
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function hasTelegram(): bool
    {
        return filled($this->telegram_chat_id);
    }
}
