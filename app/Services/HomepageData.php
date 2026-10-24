<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Static placeholder content for the homepage until real listings arrive (Phase 6).
 *
 * Every method returns display-ready arrays in the shape the card components expect. Texts come
 * from lang/{locale}/demo.php; numbers, prices and dates are formatted for the current locale.
 */
class HomepageData
{
    public function __construct(
        private PriceFormatter $price,
        private DateFormatter $dates,
    ) {}

    /**
     * @return list<array{name: string, count: string, icon: string, url: string}>
     */
    public function categories(): array
    {
        $categories = [
            ['key' => 'hotels', 'count' => 1248, 'unit' => 'listings', 'icon' => 'bed'],
            ['key' => 'apartments', 'count' => 3420, 'unit' => 'listings', 'icon' => 'building'],
            ['key' => 'houses', 'count' => 865, 'unit' => 'listings', 'icon' => 'house'],
            ['key' => 'cars', 'count' => 1102, 'unit' => 'listings', 'icon' => 'car'],
            ['key' => 'services', 'count' => 740, 'unit' => 'listings', 'icon' => 'wrench'],
            ['key' => 'jobs', 'count' => 312, 'unit' => 'vacancies', 'icon' => 'briefcase'],
            ['key' => 'restaurants', 'count' => 186, 'unit' => 'places', 'icon' => 'fork'],
            ['key' => 'events', 'count' => 48, 'unit' => 'events', 'icon' => 'calendar'],
        ];

        return array_map(fn (array $category): array => [
            'name' => __('demo.categories.'.$category['key']),
            'count' => $this->count($category['unit'], $category['count']),
            'icon' => $category['icon'],
            'url' => '#',
        ], $categories);
    }

    /**
     * @return list<array{id: string, name: string, address: string, sea: string, rating: string, reviews: string, price: string, url: string, image: ?string, saved: bool}>
     */
    public function hotels(): array
    {
        $hotels = [
            ['id' => 'h1', 'sea' => 30, 'rating' => 9.4, 'reviews' => 312, 'price' => 220],
            ['id' => 'h2', 'sea' => 120, 'rating' => 9.1, 'reviews' => 487, 'price' => 185],
            ['id' => 'h3', 'sea' => 250, 'rating' => 9.6, 'reviews' => 158, 'price' => 95, 'saved' => true],
            ['id' => 'h4', 'sea' => 50, 'rating' => 8.9, 'reviews' => 226, 'price' => 160],
            ['id' => 'h5', 'sea' => 400, 'rating' => 9.0, 'reviews' => 94, 'price' => 70],
            ['id' => 'h6', 'sea' => 80, 'rating' => 9.3, 'reviews' => 141, 'price' => 260],
        ];

        return array_map(fn (array $hotel): array => [
            'id' => $hotel['id'],
            'name' => __("demo.hotels.{$hotel['id']}.name"),
            'address' => __("demo.hotels.{$hotel['id']}.address"),
            'sea' => __('listing.to_sea', ['distance' => $this->price->number($hotel['sea'])]),
            'rating' => number_format($hotel['rating'], 1),
            'reviews' => $this->count('reviews', $hotel['reviews']),
            'price' => $this->price->format($hotel['price']),
            'url' => '#',
            'image' => null,
            'saved' => $hotel['saved'] ?? false,
        ], $hotels);
    }

    /**
     * @return list<array{id: string, deal: string, badge: string, price: string, converted: string, title: string, location: string, features: list<array{icon: string, text: string}>, url: string, image: ?string, saved: bool}>
     */
    public function properties(): array
    {
        $area = fn (int $value): array => ['icon' => 'area', 'text' => __('listing.area', ['value' => $this->price->number($value)])];
        $rooms = fn (int $value): array => ['icon' => 'door', 'text' => trans_choice('listing.rooms', $value, ['count' => $value])];
        $floor = fn (int $floor, int $total): array => ['icon' => 'layers', 'text' => __('listing.floor', ['floor' => $floor, 'total' => $total])];

        $properties = [
            ['id' => 'r1', 'deal' => 'sale', 'price' => $this->price->format(58000, 'USD'), 'converted' => $this->price->format(157000),
                'features' => [$area(64), $rooms(2), $floor(7, 12)]],
            ['id' => 'r2', 'deal' => 'rent', 'price' => $this->price->format(1400).' '.__('listing.per_month'), 'converted' => $this->price->format(520, 'USD'),
                'features' => [$area(38), $rooms(1), $floor(4, 9)]],
            ['id' => 'r3', 'deal' => 'sale', 'price' => $this->price->format(135000, 'USD'), 'converted' => $this->price->format(365000),
                'features' => [$area(180), $rooms(5), ['icon' => 'house', 'text' => __('listing.yard', ['value' => $this->price->number(600)])]]],
            ['id' => 'r4', 'deal' => 'rent', 'price' => $this->price->format(180).' '.__('listing.per_day'), 'converted' => $this->price->format(67, 'USD'),
                'features' => [$area(92), $rooms(3), $floor(2, 5)]],
            ['id' => 'r5', 'deal' => 'sale', 'price' => $this->price->format(42000, 'USD'), 'converted' => $this->price->format(113000),
                'features' => [$area(1200), ['icon' => 'tag', 'text' => __('listing.attributes.buildable')], ['icon' => 'pin', 'text' => __('listing.attributes.road_access')]]],
            ['id' => 'r6', 'deal' => 'sale', 'price' => $this->price->format(210000, 'USD'), 'converted' => $this->price->format(568000),
                'features' => [$area(120), ['icon' => 'building', 'text' => __('listing.attributes.commercial')], $floor(1, 3)]],
        ];

        return array_map(fn (array $property): array => [
            'id' => $property['id'],
            'deal' => $property['deal'],
            'badge' => __('listing.deal.'.$property['deal']),
            'price' => $property['price'],
            'converted' => __('listing.approx', ['price' => $property['converted']]),
            'title' => __("demo.properties.{$property['id']}.title"),
            'location' => __("demo.properties.{$property['id']}.location"),
            'features' => $property['features'],
            'url' => '#',
            'image' => null,
            'saved' => false,
        ], $properties);
    }

    /**
     * @return list<array{title: string, meta: string, price: string, time: string, icon: string, isNew: bool, url: string}>
     */
    public function classifieds(): array
    {
        $now = Carbon::now();

        $classifieds = [
            ['id' => 'c1', 'price' => $this->price->format(950), 'posted' => $now->copy()->subHours(2), 'icon' => 'bike', 'new' => true],
            ['id' => 'c2', 'price' => $this->price->format(9800, 'USD'), 'posted' => $now->copy()->subHours(5), 'icon' => 'car', 'new' => true],
            ['id' => 'c3', 'price' => __('listing.price_from', ['price' => $this->price->format(60)]), 'posted' => $now->copy()->subDay(), 'icon' => 'wrench'],
            ['id' => 'c4', 'price' => $this->price->format(450), 'posted' => $now->copy()->subDay(), 'icon' => 'sofa'],
            ['id' => 'c5', 'price' => $this->price->format(320), 'posted' => $now->copy()->subDays(2), 'icon' => 'tag'],
        ];

        return array_map(fn (array $classified): array => [
            'title' => __("demo.classifieds.{$classified['id']}.title"),
            'meta' => __("demo.classifieds.{$classified['id']}.category").' · '.__("demo.classifieds.{$classified['id']}.location"),
            'price' => $classified['price'],
            'time' => $this->dates->relative($classified['posted'], $now),
            'icon' => $classified['icon'],
            'isNew' => $classified['new'] ?? false,
            'url' => '#',
        ], $classifieds);
    }

    /**
     * @return list<array{title: string, employer: string, salary: string, period: string, type: string, seasonal: bool, posted: string, url: string}>
     */
    public function jobs(): array
    {
        $now = Carbon::now();

        $jobs = [
            ['id' => 'j1', 'salary' => $this->price->range(1500, 2000), 'type' => 'seasonal', 'posted' => $now->copy()],
            ['id' => 'j2', 'salary' => $this->price->format(1800), 'type' => 'full_time', 'posted' => $now->copy()],
            ['id' => 'j3', 'salary' => $this->price->range(2200, 2800), 'type' => 'full_time', 'posted' => $now->copy()->subDay()],
            ['id' => 'j4', 'salary' => $this->price->format(1400), 'type' => 'seasonal', 'posted' => $now->copy()->subDays(2)],
            ['id' => 'j5', 'salary' => __('listing.plus_commission', ['salary' => $this->price->format(1200)]), 'type' => 'full_time', 'posted' => $now->copy()->subDays(3)],
        ];

        return array_map(fn (array $job): array => [
            'title' => __("demo.jobs.{$job['id']}.title"),
            'employer' => __("demo.jobs.{$job['id']}.employer"),
            'salary' => $job['salary'],
            'period' => __('listing.per_month_word'),
            'type' => __('listing.employment.'.$job['type']),
            'seasonal' => $job['type'] === 'seasonal',
            'posted' => $this->dates->relativeDay($job['posted'], $now),
            'url' => '#',
        ], $jobs);
    }

    /**
     * @return list<array{tag: string, title: string, description: ?string, meta: string, url: string, image: ?string}>
     */
    public function guide(): array
    {
        return array_map(fn (string $id): array => [
            'tag' => __("demo.guide.{$id}.tag"),
            'title' => __("demo.guide.{$id}.title"),
            'description' => $id === 'g1' ? __("demo.guide.{$id}.description") : null,
            'meta' => __("demo.guide.{$id}.meta"),
            'url' => '#',
            'image' => null,
        ], ['g1', 'g2', 'g3', 'g4', 'g5']);
    }

    /**
     * @return array{updated: string, weather: array{air: string, condition: string, sea: string}, rates: array<string, string>, airport: array{distance: string, note: string}, railway: array{value: string, note: string}}
     */
    public function usefulInfo(): array
    {
        $updatedAt = Carbon::now()->setTime(10, 30);

        return [
            'updated' => $this->dates->weekdayDate($updatedAt).' · '.__('home.info.updated_at', ['time' => $this->dates->time($updatedAt)]),
            'weather' => [
                'air' => '+17°',
                'condition' => __('demo.info.weather_condition', ['speed' => 3]),
                'sea' => '+19°',
            ],
            'rates' => ['USD' => '2.71', 'EUR' => '3.14', 'TRY' => '0.065'],
            'airport' => [
                'distance' => __('home.info.km', ['value' => 30]),
                'note' => __('home.info.airport_note', ['minutes' => 35]),
            ],
            'railway' => [
                'value' => __('demo.info.railway_value'),
                'note' => __('demo.info.railway_note'),
            ],
        ];
    }

    /**
     * @return list<array{tag: string, date: string, title: string, url: string, image: ?string}>
     */
    public function news(): array
    {
        $news = [
            ['id' => 'n1', 'date' => '2026-11-02'],
            ['id' => 'n2', 'date' => '2026-10-22'],
            ['id' => 'n3', 'date' => '2026-10-18'],
        ];

        return array_map(fn (array $item): array => [
            'tag' => __("demo.news.{$item['id']}.tag"),
            'date' => $this->dates->date(Carbon::parse($item['date'])),
            'title' => __("demo.news.{$item['id']}.title"),
            'url' => '#',
            'image' => null,
        ], $news);
    }

    private function count(string $unit, int $count): string
    {
        return trans_choice('ui.counts.'.$unit, $count, ['count' => $this->price->number($count)]);
    }
}
