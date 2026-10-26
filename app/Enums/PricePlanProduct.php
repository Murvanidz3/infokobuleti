<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * What an order buys. The first seven are sold from price_plans (prices edited in the admin);
 * wallet top-ups take any amount, and custom invoices are issued by staff (banner slots, deals).
 */
enum PricePlanProduct: string implements FilamentLabel
{
    use HasLabel;

    case Vip = 'vip';
    case SuperVip = 'super_vip';
    case Bump = 'bump';
    case AutoBump = 'auto_bump';
    case BusinessPackage = 'business_package';
    case FeaturedHotel = 'featured_hotel';
    case Banner = 'banner';
    case WalletTopUp = 'wallet_top_up';
    case Custom = 'custom';

    /**
     * Products that have rows in price_plans.
     *
     * @return array<string, string>
     */
    public static function planOptions(): array
    {
        return collect(self::cases())
            ->filter(fn (self $product): bool => $product->hasPricePlans())
            ->mapWithKeys(fn (self $product): array => [$product->value => $product->label()])
            ->all();
    }

    public function hasPricePlans(): bool
    {
        return ! in_array($this, [self::WalletTopUp, self::Custom], true);
    }

    /**
     * Products bought for one listing (from the "promote" page).
     */
    public function isListingPromotion(): bool
    {
        return in_array($this, [self::Vip, self::SuperVip, self::Bump, self::AutoBump, self::FeaturedHotel], true);
    }

    /**
     * The promotion row a purchase creates, if any.
     */
    public function promotionType(): ?PromotionType
    {
        return match ($this) {
            self::Vip => PromotionType::Vip,
            self::SuperVip => PromotionType::SuperVip,
            self::Bump => PromotionType::Bump,
            self::AutoBump => PromotionType::AutoBump,
            self::FeaturedHotel => PromotionType::FeaturedHome,
            default => null,
        };
    }
}
