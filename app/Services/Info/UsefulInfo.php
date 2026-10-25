<?php

namespace App\Services\Info;

use App\Services\DateFormatter;
use App\Services\PriceFormatter;
use App\Services\Settings;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Carbon;

/**
 * The "useful info" strip: weather and sea, NBG exchange rates, airport and railway.
 *
 * External data is fetched on a schedule (php artisan info:refresh) and kept as the last good
 * value, so a page never waits for an API and an outage only makes the data older.
 */
class UsefulInfo
{
    public const string WEATHER_KEY = 'info.weather';

    public const string RATES_KEY = 'info.rates';

    public function __construct(
        private Cache $cache,
        private Settings $settings,
        private PriceFormatter $price,
        private DateFormatter $dates,
    ) {}

    /**
     * @return array{updated: ?string, weather: ?array{air: string, condition: string, sea: ?string}, rates: array<string, string>, airport: array{distance: string, note: string}, railway: array{value: string, note: string}}
     */
    public function data(): array
    {
        $weather = $this->cache->get(self::WEATHER_KEY);
        $rates = $this->cache->get(self::RATES_KEY);
        $updated = is_array($weather) && isset($weather['fetched_at']) ? Carbon::parse($weather['fetched_at']) : null;
        $locale = app()->getLocale();

        return [
            'updated' => $updated ? $this->dates->weekdayDate($updated).' · '.__('home.info.updated_at', ['time' => $this->dates->time($updated->timezone(config('app.timezone')))]) : null,
            'weather' => is_array($weather) ? [
                'air' => $this->degrees($weather['air']),
                'condition' => __('site.weather.'.OpenMeteo::condition((int) $weather['code'])).', '.__('site.weather.wind', ['speed' => $this->price->number(round((float) $weather['wind']))]),
                'sea' => isset($weather['sea']) ? $this->degrees($weather['sea']) : null,
            ] : null,
            'rates' => array_combine(
                $codes = array_values((array) config('info.rates.currencies')),
                array_map(fn (string $code): string => is_array($rates) && isset($rates['rates'][$code]) ? $this->rate((float) $rates['rates'][$code]) : '—', $codes),
            ),
            'airport' => [
                'distance' => __('home.info.km', ['value' => $this->settings->get('info.airport_km', config('info.transport.airport_km'))]),
                'note' => (string) ($this->settings->get("info.airport_note.{$locale}") ?: __('home.info.airport_note', ['minutes' => config('info.transport.airport_minutes')])),
            ],
            'railway' => [
                'value' => (string) ($this->settings->get("info.railway_value.{$locale}") ?: __('site.info.railway_value')),
                'note' => (string) ($this->settings->get("info.railway_note.{$locale}") ?: __('site.info.railway_note')),
            ],
        ];
    }

    private function degrees(mixed $value): string
    {
        $rounded = (int) round((float) $value);

        return ($rounded > 0 ? '+' : '').$rounded.'°';
    }

    private function rate(float $rate): string
    {
        return $rate >= 0.1 ? number_format($rate, 2, '.', '') : number_format($rate, 3, '.', '');
    }
}
