<?php

namespace App\Services\Sms;

use Carbon\CarbonInterface;

/**
 * How a message goes out. otp: a one-time sign-in code (UBill sends it with the highest priority,
 * only allowed for authentication). respectStopList: skip numbers on the provider's stop list
 * (people who unsubscribed from the brand) — on for campaigns, off for codes and service messages.
 */
final readonly class SmsOptions
{
    public function __construct(
        public bool $otp = false,
        public bool $respectStopList = false,
        public ?CarbonInterface $sendAt = null,
    ) {}

    public static function otp(): self
    {
        return new self(otp: true);
    }

    public static function campaign(bool $respectStopList = true): self
    {
        return new self(respectStopList: $respectStopList);
    }
}
