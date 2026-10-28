<?php

namespace App\Console\Commands;

use App\Services\Info\NationalBankRates;
use App\Services\Info\OpenMeteo;
use App\Services\Info\UsefulInfo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('info:refresh {--only= : weather or rates}')]
#[Description('Fetch the weather, sea temperature and NBG exchange rates for the useful-info widgets')]
class RefreshUsefulInfo extends Command
{
    public function handle(OpenMeteo $weather, NationalBankRates $rates): int
    {
        $only = $this->option('only');
        $failed = false;

        if ($only === null || $only === 'weather') {
            try {
                Cache::forever(UsefulInfo::WEATHER_KEY, [...$weather->current(), 'fetched_at' => now()->toIso8601String()]);
                $this->info('Weather updated.');
            } catch (\Throwable $exception) {
                report($exception);
                $this->warn('Weather unavailable, keeping the last value: '.$exception->getMessage());
                $failed = true;
            }
        }

        if ($only === null || $only === 'rates') {
            try {
                Cache::forever(UsefulInfo::RATES_KEY, [...$rates->fetch(config('info.rates.currencies')), 'fetched_at' => now()->toIso8601String()]);
                $this->info('Exchange rates updated.');
            } catch (\Throwable $exception) {
                report($exception);
                $this->warn('Exchange rates unavailable, keeping the last value: '.$exception->getMessage());
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
