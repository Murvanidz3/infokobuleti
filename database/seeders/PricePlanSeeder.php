<?php

namespace Database\Seeders;

use App\Enums\PricePlanProduct;
use App\Models\PricePlan;
use Illuminate\Database\Seeder;

/**
 * Starting price list (placeholder prices in GEL, edited in the admin). Existing plans are kept.
 */
class PricePlanSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 0;

        foreach ([1 => 2, 3 => 5, 7 => 10, 30 => 30] as $days => $price) {
            $this->plan("vip-{$days}", PricePlanProduct::Vip, ['ka' => "VIP {$days} დღით", 'en' => "VIP for {$days} days", 'ru' => "VIP на {$days} дн."], $price, $days, $sort++);
        }

        foreach ([1 => 4, 3 => 10, 7 => 20, 30 => 60] as $days => $price) {
            $this->plan("super-vip-{$days}", PricePlanProduct::SuperVip, ['ka' => "Super VIP {$days} დღით", 'en' => "Super VIP for {$days} days", 'ru' => "Super VIP на {$days} дн."], $price, $days, $sort++);
        }

        $this->plan('bump', PricePlanProduct::Bump, ['ka' => 'ამოწევა', 'en' => 'Bump to the top', 'ru' => 'Поднятие'], 1, null, $sort++);
        $this->plan('auto-bump-7', PricePlanProduct::AutoBump, ['ka' => 'ავტო-ამოწევა 7 დღით', 'en' => 'Daily auto-bump for 7 days', 'ru' => 'Автоподнятие на 7 дней'], 5, 7, $sort++);
        $this->plan('business-30', PricePlanProduct::BusinessPackage, ['ka' => 'ბიზნეს პაკეტი, თვე', 'en' => 'Business package, monthly', 'ru' => 'Бизнес-пакет, месяц'], 99, 30, $sort++, listingsLimit: 50);
        $this->plan('featured-hotel-30', PricePlanProduct::FeaturedHotel, ['ka' => 'რჩეული სასტუმრო მთავარზე, თვე', 'en' => 'Featured hotel on the homepage, monthly', 'ru' => 'Отель на главной, месяц'], 150, 30, $sort++);
        $this->plan('banner-30', PricePlanProduct::Banner, ['ka' => 'ბანერი, თვე', 'en' => 'Banner, monthly', 'ru' => 'Баннер, месяц'], 200, 30, $sort);
    }

    /**
     * @param  array{ka: string, en: string, ru: string}  $name
     */
    private function plan(string $code, PricePlanProduct $product, array $name, float $price, ?int $days, int $sort, ?int $listingsLimit = null): void
    {
        PricePlan::query()->firstOrCreate(['code' => $code], [
            'product' => $product,
            'name' => $name,
            'price' => $price,
            'currency' => 'GEL',
            'duration_days' => $days,
            'listings_limit' => $listingsLimit,
            'sort' => $sort,
            'is_active' => true,
        ]);
    }
}
