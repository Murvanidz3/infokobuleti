<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * Switchable Phase 9 features (Laravel Pennant, one global scope). Staff turn them on and off
 * in the admin; the defaults apply until then.
 */
enum SiteFeature: string implements FilamentLabel
{
    use HasLabel;

    case SearchAlerts = 'search-alerts';
    case WebPush = 'web-push';
    case TelegramBot = 'telegram-bot';
    case PriceInsight = 'price-insight';
    case Pwa = 'pwa';
    case SeasonalHome = 'seasonal-home';
    case TrustLayer = 'trust-layer';
    case BusinessInsights = 'business-insights';

    /**
     * On by default, except the features that need keys nobody has entered yet.
     */
    public function defaultState(): bool
    {
        return match ($this) {
            self::WebPush => filled(config('webpush.vapid.public_key')),
            self::TelegramBot => filled(config('services.telegram.bot_token')),
            default => true,
        };
    }
}
