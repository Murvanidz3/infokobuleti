<?php

namespace App\Support;

/**
 * Length and parts of an SMS. Texts in the GSM-7 alphabet (Latin) fit 160 characters in one SMS
 * and 153 per part when longer; anything else (Georgian, Cyrillic, emoji) is sent as UCS-2: 70
 * characters in one SMS, 67 per part. Providers charge per part.
 */
final class SmsText
{
    /** Placeholders a campaign text may use. */
    public const array PLACEHOLDERS = ['{name}', '{phone}'];

    private const string GSM_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    /** These take two characters in GSM-7. */
    private const string GSM_EXTENDED = "^{}\\[~]|€\f";

    public static function isUnicode(string $text): bool
    {
        foreach (mb_str_split($text) as $char) {
            if (! str_contains(self::GSM_BASIC, $char) && ! str_contains(self::GSM_EXTENDED, $char)) {
                return true;
            }
        }

        return false;
    }

    /** Characters as the network counts them (GSM-7 extension characters count twice). */
    public static function length(string $text): int
    {
        if (self::isUnicode($text)) {
            // UCS-2: characters outside the basic plane (emoji) take two units.
            return (int) (strlen((string) mb_convert_encoding($text, 'UTF-16BE', 'UTF-8')) / 2);
        }

        $length = 0;

        foreach (mb_str_split($text) as $char) {
            $length += str_contains(self::GSM_EXTENDED, $char) ? 2 : 1;
        }

        return $length;
    }

    public static function segments(string $text): int
    {
        $length = self::length($text);

        if ($length === 0) {
            return 0;
        }

        [$single, $part] = self::isUnicode($text) ? [70, 67] : [160, 153];

        return $length <= $single ? 1 : (int) ceil($length / $part);
    }

    /**
     * @return array{length: int, segments: int, unicode: bool, per_segment: int}
     */
    public static function stats(string $text): array
    {
        $unicode = self::isUnicode($text);
        $segments = self::segments($text);

        return [
            'length' => self::length($text),
            'segments' => $segments,
            'unicode' => $unicode,
            'per_segment' => $unicode ? ($segments > 1 ? 67 : 70) : ($segments > 1 ? 153 : 160),
        ];
    }

    /**
     * Fills the placeholders of a campaign text for one recipient.
     *
     * @param  array{phone: string, name?: string|null}  $recipient
     */
    public static function render(string $text, array $recipient): string
    {
        $text = strtr($text, [
            '{name}' => trim((string) ($recipient['name'] ?? '')),
            '{phone}' => GeorgianPhone::format($recipient['phone']),
        ]);

        // An empty name must not leave "Hello , ..." behind.
        $text = (string) preg_replace(['/[ \t]+([,.!?;:])/u', '/[ \t]{2,}/u'], ['$1', ' '], $text);

        return trim($text);
    }
}
