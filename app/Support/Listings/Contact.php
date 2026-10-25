<?php

namespace App\Support\Listings;

use App\Models\BusinessProfile;
use App\Models\User;
use App\Support\GeorgianPhone;
use Illuminate\Database\Eloquent\Model;

/**
 * Who answers for a public item and on which number.
 */
final class Contact
{
    /**
     * The item's own contact number, then its business profile's, then the owner's verified phone.
     */
    public static function phone(Model $record): ?string
    {
        if ($record instanceof BusinessProfile) {
            return $record->phone;
        }

        foreach (['contact_phone', 'apply_phone', 'phone'] as $column) {
            $value = $record->getAttribute($column);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $profile = $record->getAttribute('business_profile_id') !== null ? $record->getRelationValue('businessProfile') : null;

        if ($profile instanceof BusinessProfile && filled($profile->phone)) {
            return $profile->phone;
        }

        $owner = self::owner($record);

        return $owner?->hasVerifiedPhone() ? $owner->phone : null;
    }

    public static function owner(Model $record): ?User
    {
        $owner = $record->getAttribute('user_id') !== null ? $record->getRelationValue('user') : null;

        return $owner instanceof User ? $owner : null;
    }

    /**
     * Compact form for storage: E.164 for Georgian mobiles, "+digits" (or digits) otherwise.
     */
    public static function normalizePhone(string $phone): string
    {
        $mobile = GeorgianPhone::normalize($phone);

        if ($mobile !== null) {
            return $mobile;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return mb_substr((str_starts_with(trim($phone), '+') ? '+' : '').$digits, 0, 16);
    }

    /**
     * Display text and links for a phone number.
     *
     * @return array{display: string, tel: string, whatsapp: ?string, viber: ?string}
     */
    public static function links(string $phone): array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $mobile = GeorgianPhone::normalize($phone);

        if (strlen($digits) === 9) {
            $digits = GeorgianPhone::COUNTRY_CODE.$digits;
        }

        return [
            'display' => GeorgianPhone::format($phone),
            'tel' => 'tel:+'.$digits,
            // Messengers work only with mobile numbers.
            'whatsapp' => $mobile !== null ? 'https://wa.me/'.ltrim($mobile, '+') : null,
            'viber' => $mobile !== null ? 'viber://chat?number=%2B'.ltrim($mobile, '+') : null,
        ];
    }
}
