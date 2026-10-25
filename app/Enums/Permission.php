<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Staff permissions (spatie/laravel-permission). super_admin passes every check through Gate::before.
 */
enum Permission: string
{
    use HasLabel;

    case AccessAdmin = 'access_admin';
    case ModerateContent = 'moderate_content';
    case ManageContent = 'manage_content';
    case ManageUsers = 'manage_users';
    case ManagePayments = 'manage_payments';
    case ManageSettings = 'manage_settings';

    /**
     * @return list<self>
     */
    public static function forRole(Role $role): array
    {
        return match ($role) {
            Role::SuperAdmin => self::cases(),
            Role::Admin => [self::AccessAdmin, self::ModerateContent, self::ManageContent, self::ManageUsers, self::ManagePayments],
            Role::Moderator => [self::AccessAdmin, self::ModerateContent],
            Role::Business, Role::User => [],
        };
    }
}
