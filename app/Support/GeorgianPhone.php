<?php

namespace App\Support;

/**
 * Georgian mobile numbers: stored as E.164 (+9955XXXXXXXX), shown as "+995 5XX XX XX XX".
 */
final class GeorgianPhone
{
    public const string COUNTRY_CODE = '995';

    /**
     * E.164 form of a Georgian mobile number typed in any common way ("555 12 34 56",
     * "+995 555-12-34-56", "00995555123456"), or null when it is not one.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input) ?? '';

        if (str_starts_with($digits, '00'.self::COUNTRY_CODE)) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, self::COUNTRY_CODE)) {
            $digits = substr($digits, 3);
        }

        if (preg_match('/^5\d{8}$/', $digits) !== 1) {
            return null;
        }

        return '+'.self::COUNTRY_CODE.$digits;
    }

    public static function isValid(?string $input): bool
    {
        return self::normalize($input) !== null;
    }

    /**
     * "+995 555 12 34 56". Values that are not Georgian mobiles are returned unchanged.
     */
    public static function format(?string $phone): string
    {
        $e164 = self::normalize($phone);

        if ($e164 === null) {
            return (string) $phone;
        }

        $national = substr($e164, 4);

        return sprintf('+%s %s %s %s %s', self::COUNTRY_CODE, substr($national, 0, 3), substr($national, 3, 2), substr($national, 5, 2), substr($national, 7, 2));
    }

    /**
     * "+995 555 •• •• 56" for places where the full number should not be shown.
     */
    public static function mask(?string $phone): string
    {
        $e164 = self::normalize($phone);

        if ($e164 === null) {
            return '';
        }

        $national = substr($e164, 4);

        return sprintf('+%s %s •• •• %s', self::COUNTRY_CODE, substr($national, 0, 3), substr($national, 7, 2));
    }
}
