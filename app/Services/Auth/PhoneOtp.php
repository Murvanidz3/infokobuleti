<?php

namespace App\Services\Auth;

use App\Enums\OtpError;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpException;
use App\Models\PhoneVerification;
use App\Models\User;
use App\Services\Sms\SmsSender;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\DB;

/**
 * Sends and checks SMS codes (config otp.length digits). Codes are stored as an HMAC, expire after five minutes and
 * allow five guesses; sending is limited per phone (cooldown, hourly, daily) and per IP address.
 */
class PhoneOtp
{
    public function __construct(
        private SmsSender $sms,
        private RateLimiter $limiter,
    ) {}

    /**
     * @throws OtpException when a limit is reached
     */
    public function send(string $phone, OtpPurpose $purpose, ?string $ip, ?User $user = null): OtpSent
    {
        $this->ensureCanSend($phone, $ip);

        $code = $this->generateCode();

        $verification = DB::transaction(function () use ($phone, $purpose, $ip, $user, $code): PhoneVerification {
            // A new code replaces every earlier one for the same number and purpose.
            PhoneVerification::query()->open($phone, $purpose)->update(['consumed_at' => now()]);

            return PhoneVerification::query()->create([
                'phone' => $phone,
                'purpose' => $purpose,
                'user_id' => $user?->id,
                'code_hash' => $this->hash($phone, $code),
                'expires_at' => now()->addSeconds(config('otp.ttl')),
                'ip_address' => $ip,
                'locale' => app()->getLocale(),
            ]);
        });

        $this->hitSendLimits($phone, $ip);

        $this->sms->send($phone, 'otp.'.$purpose->value, $this->message($code));

        return new OtpSent($verification, (int) config('otp.resend_after'), $this->shouldReveal() ? $code : null);
    }

    /**
     * Accept a code. The matching verification is consumed and returned.
     *
     * @throws OtpException
     */
    public function verify(string $phone, OtpPurpose $purpose, string $code, ?string $ip): PhoneVerification
    {
        $ipKey = 'otp:verify-ip:'.$ip;
        $ipLimit = config('otp.limits.verify_per_ip');

        if ($this->limiter->tooManyAttempts($ipKey, $ipLimit['max'])) {
            throw new OtpException(OtpError::IpLimit, $this->limiter->availableIn($ipKey));
        }

        $this->limiter->hit($ipKey, $ipLimit['decay']);

        $verification = PhoneVerification::query()->open($phone, $purpose)->latest('id')->first();

        if ($verification === null || $verification->isExpired()) {
            throw new OtpException(OtpError::Expired);
        }

        $maxAttempts = (int) config('otp.max_attempts');

        // Count the guess atomically so parallel requests cannot exceed the limit.
        $counted = PhoneVerification::query()
            ->whereKey($verification->id)
            ->where('attempts', '<', $maxAttempts)
            ->increment('attempts');

        if ($counted === 0) {
            throw new OtpException(OtpError::TooManyAttempts);
        }

        $remaining = $maxAttempts - $verification->attempts - 1;
        $code = preg_replace('/\D+/', '', $code) ?? '';

        if (! hash_equals($verification->code_hash, $this->hash($phone, $code))) {
            throw $remaining > 0
                ? new OtpException(OtpError::Invalid, remainingAttempts: $remaining)
                : new OtpException(OtpError::TooManyAttempts);
        }

        $verification->forceFill(['consumed_at' => now(), 'attempts' => $verification->attempts + 1])->save();

        return $verification;
    }

    /**
     * Seconds until another code can be sent to this number.
     */
    public function resendAvailableIn(string $phone): int
    {
        return $this->limiter->tooManyAttempts('otp:cooldown:'.$phone, 1) ? $this->limiter->availableIn('otp:cooldown:'.$phone) : 0;
    }

    /**
     * @throws OtpException
     */
    private function ensureCanSend(string $phone, ?string $ip): void
    {
        if (($wait = $this->resendAvailableIn($phone)) > 0) {
            throw new OtpException(OtpError::Cooldown, $wait);
        }

        foreach (['per_phone_hour' => 'otp:phone-hour:'.$phone, 'per_phone_day' => 'otp:phone-day:'.$phone] as $limit => $key) {
            if ($this->limiter->tooManyAttempts($key, config("otp.limits.{$limit}.max"))) {
                throw new OtpException(OtpError::PhoneLimit, $this->limiter->availableIn($key));
            }
        }

        $ipKey = 'otp:ip:'.$ip;

        if ($this->limiter->tooManyAttempts($ipKey, config('otp.limits.per_ip_hour.max'))) {
            throw new OtpException(OtpError::IpLimit, $this->limiter->availableIn($ipKey));
        }
    }

    private function hitSendLimits(string $phone, ?string $ip): void
    {
        $this->limiter->hit('otp:cooldown:'.$phone, (int) config('otp.resend_after'));
        $this->limiter->hit('otp:phone-hour:'.$phone, config('otp.limits.per_phone_hour.decay'));
        $this->limiter->hit('otp:phone-day:'.$phone, config('otp.limits.per_phone_day.decay'));
        $this->limiter->hit('otp:ip:'.$ip, config('otp.limits.per_ip_hour.decay'));
    }

    private function generateCode(): string
    {
        $length = (int) config('otp.length');

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    private function hash(string $phone, string $code): string
    {
        return hash_hmac('sha256', $phone.'|'.$code, (string) config('app.key'));
    }

    /**
     * SMS text in the current locale, kept as short as possible ("One-time code 1234").
     */
    private function message(string $code): string
    {
        return __('auth.sms.code', ['code' => $code]);
    }

    private function shouldReveal(): bool
    {
        return config('sms.reveal_codes') && config('sms.default') === 'log' && ! app()->isProduction();
    }
}
