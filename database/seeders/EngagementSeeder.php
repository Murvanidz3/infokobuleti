<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\BookingStatus;
use App\Enums\BusinessType;
use App\Enums\ListingModule;
use App\Enums\NotificationChannel;
use App\Enums\PlaceType;
use App\Enums\PromotionType;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Enums\Role;
use App\Enums\SearchFrequency;
use App\Models\BookingRequest;
use App\Models\BusinessProfile;
use App\Models\Classified;
use App\Models\Conversation;
use App\Models\DailyStat;
use App\Models\Favorite;
use App\Models\Hotel;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Place;
use App\Models\Promotion;
use App\Models\Property;
use App\Models\Report;
use App\Models\Review;
use App\Models\SavedSearch;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use Database\Factories\Support\Kobuleti;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Activity around the listings: reviews, favorites, booking requests, applications, conversations,
 * saved searches, reports, daily stats and paid promotions.
 */
class EngagementSeeder extends Seeder
{
    use SeedsDemoData;

    /** @var array<string, list<array{0: string, 1: int, 2: string}>> locale, rating, body */
    private const array REVIEWS = [
        'stay' => [
            ['ka', 10, 'ზღვამდე ორი წუთია, ოთახი სუფთა და მყუდრო. მასპინძლები ძალიან სტუმართმოყვარეები არიან.'],
            ['ka', 9, 'გემრიელი საუზმე და მშვიდი ეზო. აუცილებლად დავბრუნდებით მომავალ ზაფხულს.'],
            ['ka', 8, 'კარგი მდებარეობა ბულვართან ახლოს, ოღონდ საღამოობით ცოტა ხმაურიანია.'],
            ['ka', 7, 'ფასისთვის ნორმალური ვარიანტია, Wi-Fi ზოგჯერ ითიშებოდა.'],
            ['ka', 9, 'ოჯახთან ერთად ვისვენებდით, ბავშვებს ძალიან მოეწონათ. ყველაფერი ისე იყო, როგორც ფოტოებზე.'],
            ['ka', 6, 'ოთახი ფოტოებზე უფრო პატარა აღმოჩნდა, თუმცა პერსონალი თავაზიანი იყო.'],
            ['en', 9, 'Great location right by the boulevard. Clean room and very friendly hosts.'],
            ['en', 8, 'Good value for money. Breakfast was simple but tasty.'],
            ['ru', 10, 'Отличное место для отдыха с детьми, до моря пара минут. Хозяева очень радушные.'],
            ['ru', 8, 'Чисто, уютно, хороший завтрак. Немного шумно вечером из-за кафе рядом.'],
        ],
        'place' => [
            ['ka', 10, 'ძალიან ლამაზი ადგილია, აუცილებლად ღირს ნახვა.'],
            ['ka', 9, 'მშვენიერი გარემო, ოღონდ ზაფხულში ბევრი ხალხია.'],
            ['ka', 8, 'კარგი ადგილია ოჯახთან ერთად გასასეირნებლად.'],
            ['en', 9, 'Beautiful spot, well worth a visit.'],
            ['ru', 9, 'Очень красиво, обязательно приедем ещё.'],
        ],
        'food' => [
            ['ka', 9, 'გემრიელი საჭმელი და სწრაფი მომსახურება.'],
            ['ka', 8, 'ხაჭაპური საუკეთესოა ქალაქში, ფასებიც ნორმალურია.'],
            ['ka', 7, 'საჭმელი კარგია, მაგრამ შაბათ-კვირას დიდხანს მოგვიწია ლოდინი.'],
            ['en', 9, 'Delicious food and friendly staff.'],
            ['ru', 9, 'Вкусно, быстро и недорого.'],
        ],
        'agency' => [
            ['ka', 10, 'ბინა სწრაფად ვიპოვეთ, აგენტი ძალიან დაგვეხმარა.'],
            ['ka', 8, 'პროფესიონალური მომსახურება, ყველა დოკუმენტი მოგვიგვარეს.'],
            ['ru', 9, 'Помогли быстро найти квартиру у моря, рекомендую.'],
        ],
    ];

    /** @var array<string, string> */
    private const array OWNER_REPLIES = [
        'ka' => 'გმადლობთ შეფასებისთვის! მოხარულები ვიქნებით, თუ კვლავ გვესტუმრებით.',
        'en' => 'Thank you for your review! We would be happy to welcome you again.',
        'ru' => 'Спасибо за отзыв! Будем рады видеть вас снова.',
    ];

    /** @var list<array{0: string, 1: string}> locale, message */
    private const array BOOKING_MESSAGES = [
        ['ka', 'გამარჯობა, გვაინტერესებს ოთახი ზღვის ხედით, თუ თავისუფალია.'],
        ['ka', 'მოვდივართ ორი ზრდასრული და ერთი ბავშვი. შესაძლებელია ადრე შემოსვლა?'],
        ['ka', 'პარკინგი გაქვთ? მანქანით ვიმოგზაურებთ.'],
        ['en', 'Hello, is the room available for these dates? We would arrive around 6 pm.'],
        ['ru', 'Здравствуйте! Есть ли свободный номер на эти даты? Нужна детская кроватка.'],
    ];

    private const array COVER_NOTES = [
        'გამარჯობა, მაქვს მსგავს პოზიციაზე მუშაობის გამოცდილება და შემიძლია დაწყება ნებისმიერ დროს.',
        'ვარ სტუდენტი, ზაფხულის განმავლობაში სრულ განაკვეთზე მუშაობა შემიძლია.',
        'ვლაპარაკობ ქართულად, ინგლისურად და რუსულად. გთხოვთ დამიკავშირდეთ.',
    ];

    /** Alternating buyer / owner lines. */
    private const array CHAT = [
        'გამარჯობა, განცხადება ისევ აქტუალურია?',
        'გამარჯობა, დიახ, აქტუალურია.',
        'ფასზე შეთანხმება შესაძლებელია?',
        'ნახვის შემდეგ შევთანხმდებით.',
        'როდის შეიძლება ნახვა?',
        'ხვალ, 12 საათის შემდეგ. მისამართს მოგწერთ.',
    ];

    /** @var array<string, int> */
    private const array PROMOTION_PRICES = ['super_vip' => 49, 'vip' => 25, 'bump' => 3, 'featured_home' => 99];

    public function run(): void
    {
        $this->reviews();
        $this->favorites();
        $this->bookingRequests();
        $this->applications();
        $this->conversations();
        $this->savedSearches();
        $this->reports();
        $this->dailyStats();
        $this->promotions();
    }

    private function reviews(): void
    {
        foreach (Hotel::query()->published()->get() as $hotel) {
            $this->reviewsFor($hotel, fake()->numberBetween(2, 8), 'stay', reply: $hotel->business_profile_id !== null);
        }

        foreach (Place::query()->where('type', '!=', PlaceType::Service)->get() as $place) {
            $kind = in_array($place->type, [PlaceType::Restaurant, PlaceType::Cafe], true) ? 'food' : 'place';
            $this->reviewsFor($place, fake()->numberBetween(1, 5), $kind);
        }

        foreach (BusinessProfile::query()->whereIn('type', [BusinessType::Agency, BusinessType::Restaurant])->get() as $profile) {
            $this->reviewsFor($profile, fake()->numberBetween(1, 3), $profile->type === BusinessType::Agency ? 'agency' : 'food', reply: true);
        }
    }

    private function reviewsFor(Model $reviewable, int $count, string $kind, bool $reply = false): void
    {
        foreach ($this->individuals()->random($count) as $user) {
            [$locale, $rating, $body] = fake()->randomElement(self::REVIEWS[$kind]);
            $createdAt = now()->subDays(fake()->numberBetween(3, 300));
            $replied = $reply && fake()->boolean(40);

            Review::factory()->create([
                'user_id' => $user->id,
                'reviewable_type' => $reviewable->getMorphClass(),
                'reviewable_id' => $reviewable->getKey(),
                'rating' => $rating,
                'body' => $body,
                'locale' => $locale,
                'status' => fake()->boolean(90) ? ReviewStatus::Published : ReviewStatus::Pending,
                'owner_reply' => $replied ? self::OWNER_REPLIES[$locale] : null,
                'replied_at' => $replied ? $createdAt->copy()->addDay() : null,
                'created_at' => $createdAt,
            ]);
        }
    }

    private function favorites(): void
    {
        $pool = collect()
            ->concat(Hotel::query()->published()->get())
            ->concat(Property::query()->published()->get())
            ->concat(Classified::query()->published()->get())
            ->concat(Place::query()->published()->get());

        foreach ($this->individuals()->random(25) as $user) {
            foreach ($pool->random(fake()->numberBetween(2, 6)) as $listing) {
                Favorite::query()->create([
                    'user_id' => $user->id,
                    'favoritable_type' => $listing->getMorphClass(),
                    'favoritable_id' => $listing->getKey(),
                ]);
            }
        }
    }

    private function bookingRequests(): void
    {
        $hotels = Hotel::query()->published()->with('roomTypes')->get();
        $statuses = [
            ...array_fill(0, 18, BookingStatus::Pending), ...array_fill(0, 12, BookingStatus::Accepted),
            ...array_fill(0, 5, BookingStatus::Declined), ...array_fill(0, 3, BookingStatus::Cancelled),
            ...array_fill(0, 2, BookingStatus::Expired),
        ];

        foreach ($statuses as $status) {
            $hotel = $hotels->random();
            $room = $hotel->roomTypes->random();
            $user = fake()->boolean(60) ? $this->individuals()->random() : null;
            [$locale, $message] = fake()->randomElement(self::BOOKING_MESSAGES);

            BookingRequest::factory()->status($status)->create([
                'hotel_id' => $hotel->id,
                'room_type_id' => $room->id,
                'user_id' => $user?->id,
                'adults' => fake()->numberBetween(1, $room->capacity_adults),
                'children' => fake()->numberBetween(0, $room->capacity_children),
                'contact_name' => $user->name ?? Kobuleti::name(),
                'contact_phone' => $user->phone ?? Kobuleti::phone(),
                'contact_email' => $user?->email,
                'message' => $message,
                'locale' => $locale,
            ]);
        }
    }

    private function applications(): void
    {
        $vacancies = Vacancy::query()->published()->get();
        $statuses = [
            ...array_fill(0, 14, ApplicationStatus::New), ...array_fill(0, 8, ApplicationStatus::Viewed),
            ...array_fill(0, 5, ApplicationStatus::Shortlisted), ...array_fill(0, 2, ApplicationStatus::Rejected),
            ApplicationStatus::Hired,
        ];
        $taken = [];

        foreach ($statuses as $status) {
            do {
                $vacancy = $vacancies->random();
                $user = $this->individuals()->random();
            } while (isset($taken["{$vacancy->id}:{$user->id}"]));

            $taken["{$vacancy->id}:{$user->id}"] = true;

            VacancyApplication::factory()->create([
                'vacancy_id' => $vacancy->id,
                'user_id' => $user->id,
                'cover_note' => fake()->randomElement(self::COVER_NOTES),
                'contact_phone' => $user->phone ?? Kobuleti::phone(),
                'status' => $status,
                'status_changed_at' => $status === ApplicationStatus::New ? null : now()->subDays(fake()->numberBetween(0, 5)),
            ]);
        }
    }

    private function conversations(): void
    {
        $subjects = collect()
            ->concat(Property::query()->published()->inRandomOrder()->limit(5)->get())
            ->concat(Classified::query()->published()->inRandomOrder()->limit(5)->get());

        foreach ($subjects as $subject) {
            /** @var Property|Classified $subject */
            $buyer = $this->individuals()->where('id', '!=', $subject->user_id)->random();
            $conversation = Conversation::query()->create(['subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey()]);
            $sentAt = now()->subDays(fake()->numberBetween(1, 6));

            $conversation->participants()->attach([
                $buyer->id => ['last_read_at' => now()],
                $subject->user_id => ['last_read_at' => $sentAt],
            ]);

            foreach (array_slice(self::CHAT, 0, fake()->numberBetween(2, count(self::CHAT))) as $index => $body) {
                $sentAt = $sentAt->copy()->addMinutes(fake()->numberBetween(5, 240));

                Message::factory()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $index % 2 === 0 ? $buyer->id : $subject->user_id,
                    'body' => $body,
                    'created_at' => $sentAt,
                    'updated_at' => $sentAt,
                ]);
            }
        }
    }

    private function savedSearches(): void
    {
        $searches = [
            [ListingModule::Properties, 'ბინა ჩრდილოეთ ქობულეთში', ['deal' => 'sale', 'type' => 'apartment', 'location' => 'north-kobuleti', 'price_max' => 60000, 'currency' => 'USD']],
            [ListingModule::Properties, 'დღიური ქირა, 2+ ოთახი', ['deal' => 'daily_rent', 'type' => 'apartment', 'rooms_min' => 2]],
            [ListingModule::Properties, 'მიწის ნაკვეთი', ['deal' => 'sale', 'type' => 'land', 'land_area_min' => 500]],
            [ListingModule::Hotels, 'საოჯახო სასტუმრო ზღვასთან', ['type' => 'guesthouse', 'distance_to_sea_max' => 300]],
            [ListingModule::Classifieds, 'Toyota Prius', ['category' => 'cars', 'make' => 'toyota', 'price_max' => 12000, 'currency' => 'USD']],
            [ListingModule::Classifieds, 'iPhone', ['category' => 'phones', 'brand' => 'apple']],
            [ListingModule::Vacancies, 'სეზონური სამუშაო სასტუმროში', ['category' => 'hospitality', 'employment_type' => 'seasonal']],
            [ListingModule::Vacancies, 'IT ვაკანსიები', ['category' => 'it-marketing']],
        ];

        foreach ($searches as [$module, $name, $filters]) {
            SavedSearch::query()->create([
                'user_id' => $this->individuals()->random()->id,
                'module' => $module,
                'name' => $name,
                'filters' => $filters,
                'channels' => fake()->boolean(30) ? [NotificationChannel::Email->value, NotificationChannel::Telegram->value] : [NotificationChannel::Email->value],
                'frequency' => fake()->randomElement(SearchFrequency::cases()),
            ]);
        }
    }

    private function reports(): void
    {
        $moderator = User::role(Role::Moderator->value)->firstOrFail();
        $classifieds = Classified::query()->published()->inRandomOrder()->limit(6)->get();

        foreach ($classifieds->values() as $index => $classified) {
            $resolved = $index === 0;

            Report::factory()->create([
                'user_id' => $this->individuals()->random()->id,
                'reportable_type' => $classified->getMorphClass(),
                'reportable_id' => $classified->id,
                'reason' => fake()->randomElement([ReportReason::Spam, ReportReason::Duplicate, ReportReason::AlreadySold, ReportReason::WrongCategory]),
                'status' => $resolved ? ReportStatus::Resolved : ReportStatus::Open,
                'handled_by' => $resolved ? $moderator->id : null,
                'handled_at' => $resolved ? now()->subHours(5) : null,
                'resolution_note' => $resolved ? 'ავტორს მიეწერა გაფრთხილება.' : null,
            ]);
        }
    }

    private function dailyStats(): void
    {
        $listings = collect()
            ->concat(Hotel::query()->published()->limit(12)->get())
            ->concat(Property::query()->published()->limit(12)->get());
        $rows = [];

        foreach ($listings as $listing) {
            foreach (range(1, 14) as $daysAgo) {
                $views = fake()->numberBetween(5, 120);

                $rows[] = [
                    'statable_type' => $listing->getMorphClass(),
                    'statable_id' => $listing->getKey(),
                    'date' => now()->subDays($daysAgo)->toDateString(),
                    'views' => $views,
                    'phone_reveals' => intdiv($views, fake()->numberBetween(6, 12)),
                    'favorites' => intdiv($views, fake()->numberBetween(15, 40)),
                    'messages' => intdiv($views, fake()->numberBetween(30, 80)),
                ];
            }
        }

        DailyStat::query()->insert($rows);
    }

    private function promotions(): void
    {
        $plan = [
            [Hotel::class, PromotionType::SuperVip, 2], [Property::class, PromotionType::SuperVip, 3], [Classified::class, PromotionType::SuperVip, 1],
            [Hotel::class, PromotionType::Vip, 4], [Property::class, PromotionType::Vip, 5], [Classified::class, PromotionType::Vip, 4], [Vacancy::class, PromotionType::Vip, 2],
            [Property::class, PromotionType::Bump, 3], [Classified::class, PromotionType::Bump, 6],
            [Hotel::class, PromotionType::FeaturedHome, 2],
        ];
        $used = [];

        foreach ($plan as [$model, $type, $count]) {
            $listings = $model::query()->published()->whereNotIn('id', $used[$model] ?? [])->inRandomOrder()->limit($count)->get();

            foreach ($listings as $listing) {
                $used[$model][] = $listing->id;
                $startsAt = now()->subDays(fake()->numberBetween(0, 3))->subHours(fake()->numberBetween(0, 20));

                $payment = Payment::factory()->create([
                    'user_id' => $listing->user_id,
                    'payable_type' => $listing->getMorphClass(),
                    'payable_id' => $listing->id,
                    'amount' => self::PROMOTION_PRICES[$type->value],
                    'paid_at' => $startsAt,
                ]);

                Promotion::query()->create([
                    'promotable_type' => $listing->getMorphClass(),
                    'promotable_id' => $listing->id,
                    'user_id' => $listing->user_id,
                    'payment_id' => $payment->id,
                    'type' => $type,
                    'starts_at' => $startsAt,
                    'ends_at' => $type === PromotionType::Bump ? null : $startsAt->copy()->addDays(7),
                ]);
            }
        }
    }
}
