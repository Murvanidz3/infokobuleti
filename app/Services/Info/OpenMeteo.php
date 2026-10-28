<?php

namespace App\Services\Info;

use Illuminate\Support\Facades\Http;

/**
 * Current weather and sea surface temperature for Kobuleti from Open-Meteo (free, no key).
 */
class OpenMeteo
{
    /**
     * @return array{air: float, code: int, wind: float, sea: ?float}
     *
     * @throws \Throwable when the forecast API is unreachable or answers with an error
     */
    public function current(): array
    {
        $config = config('info.weather');

        $forecast = Http::timeout(6)->retry(2, 300)->get($config['forecast_url'], [
            'latitude' => $config['lat'],
            'longitude' => $config['lng'],
            'current' => 'temperature_2m,weather_code,wind_speed_10m',
            'wind_speed_unit' => 'ms',
            'timezone' => config('app.timezone'),
        ])->throw()->json('current');

        $sea = null;

        try {
            $sea = Http::timeout(6)->get($config['marine_url'], [
                'latitude' => $config['sea_lat'],
                'longitude' => $config['sea_lng'],
                'current' => 'sea_surface_temperature',
            ])->throw()->json('current.sea_surface_temperature');
        } catch (\Throwable $exception) {
            report($exception);
        }

        return [
            'air' => (float) $forecast['temperature_2m'],
            'code' => (int) $forecast['weather_code'],
            'wind' => (float) $forecast['wind_speed_10m'],
            'sea' => is_numeric($sea) ? (float) $sea : null,
        ];
    }

    /**
     * WMO weather code → our condition key (lang site.weather.*).
     */
    public static function condition(int $code): string
    {
        return match (true) {
            $code === 0 => 'clear',
            $code <= 2 => 'partly_cloudy',
            $code === 3 => 'cloudy',
            in_array($code, [45, 48], true) => 'fog',
            $code >= 51 && $code <= 57 => 'drizzle',
            ($code >= 61 && $code <= 67) || ($code >= 80 && $code <= 82) => 'rain',
            ($code >= 71 && $code <= 77) || $code === 85 || $code === 86 => 'snow',
            $code >= 95 => 'storm',
            default => 'cloudy',
        };
    }
}
