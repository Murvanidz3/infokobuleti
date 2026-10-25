<?php

namespace App\Services\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP (authenticator app) codes for staff accounts, plus single-use recovery codes.
 */
class TwoFactorAuthenticator
{
    public const int RECOVERY_CODES = 8;

    public function __construct(private Google2FA $engine, private Cache $cache) {}

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey(32);
    }

    /**
     * QR code (inline SVG, navy on white) for adding the account to an authenticator app.
     */
    public function qrCodeSvg(string $accountLabel, string $secret): string
    {
        $url = $this->engine->getQRCodeUrl((string) config('app.name'), $accountLabel, $secret);

        $style = new RendererStyle(200, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(10, 26, 47)));
        $svg = (new Writer(new ImageRenderer($style, new SvgImageBackEnd)))->writeString($url);

        return trim(substr($svg, (int) strpos($svg, "\n") + 1));
    }

    /**
     * Check a 6-digit code against a secret. With a user id, each code works only once.
     */
    public function verify(string $secret, string $code, ?int $userId = null): bool
    {
        $code = preg_replace('/\D+/', '', $code) ?? '';

        if (strlen($code) !== 6) {
            return false;
        }

        $cacheKey = 'two-factor:last-timestamp:'.$userId;
        $lastTimestamp = $userId !== null ? $this->cache->get($cacheKey) : null;

        $timestamp = $this->engine->verifyKeyNewer($secret, $code, $lastTimestamp, 1);

        if ($timestamp === false) {
            return false;
        }

        if ($userId !== null) {
            $this->cache->put($cacheKey, $timestamp === true ? $this->engine->getTimestamp() : $timestamp, 150);
        }

        return true;
    }

    /**
     * Use a recovery code: it is removed from the user's list when it matches.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $normalized = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $stored) {
            if (hash_equals(str_replace('-', '', $stored), $normalized) && $normalized !== '') {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string> codes like "K7QX2-M9PLD"
     */
    public function generateRecoveryCodes(): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return array_map(function () use ($alphabet): string {
            $chars = '';

            for ($i = 0; $i < 10; $i++) {
                $chars .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            return substr($chars, 0, 5).'-'.substr($chars, 5);
        }, range(1, self::RECOVERY_CODES));
    }

    /**
     * Turn on two-factor auth with a confirmed secret and fresh recovery codes.
     *
     * @return list<string> the recovery codes, to show once
     */
    public function enable(User $user, string $secret): array
    {
        $codes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $codes;
    }

    /**
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return $codes;
    }

    /**
     * Secret shown for manual entry, in groups of four.
     */
    public static function formatSecret(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }
}
