<?php

namespace App\Support;

/**
 * Just enough user-agent parsing to label a signed-in device ("Chrome · Windows").
 */
final readonly class UserAgent
{
    public function __construct(
        public ?string $browser,
        public ?string $platform,
        public bool $mobile,
    ) {}

    public static function parse(?string $userAgent): self
    {
        $ua = (string) $userAgent;

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'YaBrowser') => 'Yandex',
            str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS') => 'Firefox',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => null,
        };

        $platform = match (true) {
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'CrOS') => 'ChromeOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };

        $mobile = (bool) preg_match('/Mobile|Android|iPhone|iPad/', $ua);

        return new self($browser, $platform, $mobile);
    }

    public function label(): ?string
    {
        $parts = array_filter([$this->browser, $this->platform]);

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
