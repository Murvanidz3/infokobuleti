# Listing creation audit

Read-only map of how a განცხადება is stored and created on `develop` (commit `3033ca3`, 27 Nov 2026). No database was queried and no migration was run. Category and amenity trees below are the seeder source. Staff can edit those rows in admin afterwards, so a live database can drift from this file.

There is no `Listing` model and no single `listings` table. `App\Contracts\Listing` is an interface. The public “add a listing” wizard only creates four models: `Hotel`, `Property`, `Classified`, `Vacancy`. Places, articles and events implement the same interface but are staff content, not this wizard.

The six product types named for the redesign do not match six systems:

| Asked for | What the code actually has |
| --- | --- |
| უძრავი ქონება | `Property`. Deal × type, one form for every subtype. |
| მიწის ფართი | Not its own type. `PropertyType::Land` (`land`), Georgian label **მიწის ნაკვეთი**. The area field is labeled **მიწის ფართი, მ²**. |
| სასტუმრო | Its own module (`Hotel`) **and** a property type `hotel_business` (**სასტუმრო (ბიზნესი)**). |
| ნივთები | `Classified`, every category except `services`. |
| მომსახურება | A classified **category** (`services`), not a module. The wizard button is **ნივთი ან მომსახურება**. |
| ვაკანსიები | `Vacancy` (table `vacancies`; public URL is `/jobs`). |

Laravel is `^13.17` (`composer.json`). Admin is Filament v5 at `/admin`.

## 1. Data model

Shared columns for every module table come from `ListingSchema::columns()` in `app/Support/Database/ListingSchema.php` (lines 22–73), written by the module migrations below.

| Column | Notes |
| --- | --- |
| `user_id`, `business_profile_id` nullable | Owner and optional business. |
| `status` varchar(16), default `draft` | `ListingStatus`. |
| `slug` unique | Generated once from the Georgian title. |
| `title` jsonb, `description` jsonb nullable | Locales `ka`, `en`, `ru`. |
| `location_id` nullable, `lat`, `lng`, `address` jsonb | All four wizard modules include location. |
| `views_count`, `published_at`, `expires_at` | Expiry days in `config/listings.php`: hotel 365, property 60, classified 30, vacancy 30. |
| `rejection_reason`, `moderated_by`, `moderated_at`, `risk_score` | Moderation. Nothing in `app/` writes `risk_score` (column exists, unused by creation). |
| `promotion_level`, `promoted_until`, `bumped_at` | Present on all four wizard tables (`promotable: true`). |
| `embedding` vector(1024) | Present on all four. Nothing in `app/` writes it. |
| `timestamps`, `deleted_at` | Soft deletes. |

`Listing::SYSTEM_COLUMNS` (`app/Contracts/Listing.php` lines 21–25) blocks mass-assignment of status, counters, prices caches and `embedding`.

### Status

`app/Enums/ListingStatus.php` lines 12–19. Georgian labels from `lang/ka/enums.php` lines 131–138.

| Value | Label |
| --- | --- |
| `draft` | მონახაზი |
| `pending` | მოდერაციაზე |
| `published` | გამოქვეყნებული |
| `rejected` | უარყოფილი |
| `paused` | შეჩერებული |
| `expired` | ვადაგასული |
| `archived` | დაარქივებული |

Public submit always moves the row to `pending` (`app/Actions/Listings/SubmitListing.php` lines 72–74). A new model starts as `draft` (`app/Models/Concerns/HasStatus.php` line 40). Admin create does not override that, so an admin-created row stays `draft` until someone uses Approve (allowed from `draft` or `pending`, `app/Filament/Support/ListingAdmin.php` line 164).

### `hotels` and rooms

Migration `database/migrations/2026_10_25_100003_create_hotels_tables.php`. Model `app/Models/Hotel.php`.

Extra columns: `type` (16), `stars` 1–5 check, `distance_to_sea_m`, `check_in_from`, `check_out_until`, `price_from` decimal, `rooms_count`, `season_start_month`, `season_end_month` (both 1–12 check), `contact_phone`, `contact_email`, `website`, `rating_avg`, `reviews_count`.

`HotelType` (`app/Enums/HotelType.php` lines 12–16), labels `lang/ka/enums.php` lines 90–96:

| Value | Label |
| --- | --- |
| `hotel` | სასტუმრო |
| `guesthouse` | საოჯახო სასტუმრო |
| `apartment` | აპარტამენტი |
| `cottage` | კოტეჯი |
| `hostel` | ჰოსტელი |

Relations: `user`, `businessProfile`, `roomTypes`, `bookingRequests`, `amenities` (morph, scope `hotel`), plus the shared traits (location, gallery, slug, status, favorites, promotions, reports, reviews, search).

`price_from` is not entered. `Hotel::syncPriceFrom()` sets it to `min(room_types.base_price)` (`app/Models/Hotel.php` lines 68–73), called when a room is saved or deleted (`app/Models/RoomType.php` lines 29–36). No currency column. Prices are treated as GEL.

Child tables in the same migration:

| Table | Key columns | Model |
| --- | --- | --- |
| `room_types` | `hotel_id`, `name` jsonb, `description` jsonb, `capacity_adults` default 2, `capacity_children` default 0, `beds` jsonb, `size_m2`, `base_price`, `units` default 1, `sort` | `RoomType` |
| `room_type_price_rules` | `room_type_id`, `name` jsonb, `starts_on`, `ends_on` (`ends_on >= starts_on`), `price`, `min_nights` default 1 | `RoomTypePriceRule` |
| `room_availability` | unique `(room_type_id, date)`, `is_closed`, `price_override`, `units_available`. A missing row means the night is open. | `RoomAvailability` |
| `booking_requests` | `hotel_id`, `room_type_id` nullable, `user_id` nullable, `check_in`, `check_out` (`check_out > check_in`), `adults`, `children`, contact fields, `status` default `pending`, `locale` default `ka` | `BookingRequest` |

`BookingStatus` (`app/Enums/BookingStatus.php`): `pending` მოლოდინში, `accepted` დადასტურებული, `declined` უარყოფილი, `cancelled` გაუქმებული, `expired` ვადაგასული.

`BedType` exists (`single` ერთადგილიანი საწოლი, `double` ორადგილიანი საწოლი, `twin` ორი ცალკე საწოლი, `sofa` გასაშლელი დივანი, `bunk` ორსართულიანი საწოლი) but neither the wizard nor the Filament room form writes `beds`.

Room amenities use `AmenityScope::Room` (`room` / **ოთახი**). The hotel page reads them. The wizard and the Filament room form do not attach them.

### `properties`

Migration `database/migrations/2026_10_25_100004_create_properties_table.php`. Model `app/Models/Property.php`.

| Column | Notes |
| --- | --- |
| `deal` | `PropertyDeal`, required in practice (no DB default). |
| `type` | `PropertyType`. Index `(deal, type)`. |
| `price` decimal(14,2), check `price >= 0` | One number for sale, monthly rent and daily rent. |
| `currency` default `GEL` | `Currency`: `GEL` ლარი, `USD` აშშ დოლარი, `EUR` ევრო. |
| `price_gel` | Filled by `NormalizesPrice` on save, for sorting and filters. |
| `area_m2`, `land_area_m2` | |
| `price_per_m2` | Stored generated: `round(price / nullif(area_m2, 0), 2)`. Uses built area only, in the listing currency, not `land_area_m2` and not GEL. |
| `rooms`, `bedrooms`, `bathrooms`, `floor` (signed), `total_floors` | |
| `condition` | `PropertyCondition`, nullable. |
| `building_status` | `BuildingStatus`, nullable. |
| `cadastral_code` varchar(32) | |
| `listed_by` default `owner` | `ListedBy`. |
| `distance_to_sea_m` | Computed from the pin on public submit (`SeaDistance`). Admin types it by hand. |
| `contact_name`, `contact_phone` | |
| `owner_verified_at`, `owner_verified_by` | Added later by `database/migrations/2026_11_01_100000_create_phase9_tables.php` lines 29–31. Not part of the wizard. |

`PropertyDeal` (`app/Enums/PropertyDeal.php`), `lang/ka/enums.php` lines 198–202:

| Value | Label |
| --- | --- |
| `sale` | იყიდება |
| `rent` | ქირავდება |
| `daily_rent` | ქირავდება დღიურად |

`PropertyType` (`app/Enums/PropertyType.php` lines 12–16), `lang/ka/enums.php` lines 204–210:

| Value | Label |
| --- | --- |
| `apartment` | ბინა |
| `house` | სახლი |
| `land` | მიწის ნაკვეთი |
| `commercial` | კომერციული ფართი |
| `hotel_business` | სასტუმრო (ბიზნესი) |

`PropertyCondition`: `newly_renovated` ახალი გარემონტებული, `old_renovation` ძველი გარემონტებული, `needs_renovation` სარემონტო, `white_frame` თეთრი კარკასი, `black_frame` შავი კარკასი, `green_frame` მწვანე კარკასი.

`BuildingStatus`: `new` ახალი აშენებული, `old` ძველი აშენებული, `under_construction` მშენებარე.

`ListedBy`: `owner` მესაკუთრე, `agency` სააგენტო.

Relations: `user`, `businessProfile`, `features` (amenities where scope is `property`). No category, no rooms.

`lang/ka/listing.php` lines 28–32 has `buildable` სამშენებლო, `road_access` გზასთან, `commercial` კომერციული. Those strings are only used by the styleguide fake cards (`app/Services/StyleguideData.php`). They are not columns and not an attribute schema.

### `classifieds`

Migration `database/migrations/2026_10_25_100005_create_classifieds_table.php`. Model `app/Models/Classified.php`.

| Column | Notes |
| --- | --- |
| `category_id` | NOT NULL, `restrictOnDelete`. |
| `price` decimal nullable, `currency` default `GEL`, `price_gel` | Empty price displays as **ფასი შეთანხმებით** (`site.price.on_request` via `CardPresenter::classifiedPrice`). |
| `negotiable` bool default false | |
| `condition` | `ItemCondition`: `new` ახალი, `used` მეორადი. Nullable. |
| `attribute_values` jsonb default `{}` | GIN index. Shape comes from the category schema, not a separate EAV table. |
| `contact_name`, `contact_phone` | |

Relations: `user`, `businessProfile`, `category`. No amenities.

### `vacancies` and applications

Migration `database/migrations/2026_10_25_100006_create_vacancies_tables.php`. Model `app/Models/Vacancy.php`. The class is not named `Job` because `jobs` is the queue table.

| Column | Notes |
| --- | --- |
| `category_id` | NOT NULL, `restrictOnDelete`. |
| `employment_type` | `EmploymentType`. |
| `salary_min`, `salary_max` unsigned int, nullable | Check `salary_max >= salary_min` when both set. |
| `salary_currency` default `GEL` | |
| `salary_period` default `month` | `SalaryPeriod`. |
| `experience` nullable | `Experience`. |
| `languages` jsonb default `[]` | GIN. Wizard allows `ka`, `en`, `ru`, `tr`, `de` (`ListingForm::LANGUAGES`). |
| `benefits` jsonb default `[]` | GIN. `VacancyBenefit`. |
| `deadline` date nullable | |
| `apply_method` default `site` | `ApplyMethod`. |
| `apply_phone`, `apply_email` | |

`EmploymentType`: `full_time` სრული განაკვეთი, `part_time` ნახევარი განაკვეთი, `seasonal` სეზონური, `remote` დისტანციური.

`SalaryPeriod`: `hour` საათში, `day` დღეში, `month` თვეში, `season` სეზონში.

`Experience`: `none` გამოცდილების გარეშე, `under_1` 1 წლამდე, `1_3` 1–3 წელი, `3_5` 3–5 წელი, `over_5` 5 წელზე მეტი.

`ApplyMethod`: `site` საიტის მეშვეობით, `phone` ტელეფონით, `email` ელფოსტით.

`VacancyBenefit`: `accommodation` საცხოვრებელი, `meals` კვება, `transport` ტრანსპორტი, `uniform` ფორმა, `training` სწავლება.

There is no employer-name column. On submit, if `business_profile_id` is empty, it is set to the user’s oldest business profile (`SubmitListing.php` lines 63–65). The public card then shows that profile’s name, otherwise the location (`CardPresenter::job`).

`vacancy_applications`: `vacancy_id`, `user_id`, unique pair, `cover_note`, `contact_phone`, `status` default `new`. CV is a single media file on collection `cv` (`app/Models/VacancyApplication.php`). `ApplicationStatus`: `new` ახალი, `viewed` ნანახი, `shortlisted` შერჩეული, `rejected` უარყოფილი, `hired` აყვანილი.

### Categories (the only EAV)

Migration `database/migrations/2026_10_25_100001_create_locations_and_categories_tables.php` lines 24–38. Model `app/Models/Category.php`.

Columns: `parent_id`, `module`, `name` jsonb, `slug` unique per module, `icon`, `sort`, `attribute_schema` jsonb nullable, `is_active`.

`CategoryModule` (`app/Enums/CategoryModule.php`): `classifieds` განცხადებები, `vacancies` ვაკანსიები, `news` სიახლეები, `events` ღონისძიებები. Properties and hotels have no category rows.

`Category::schema()` walks up to the nearest ancestor that has an `attribute_schema` (`app/Models/Category.php` lines 71–80). Field shape is documented on `app/Support/AttributeSchema.php` lines 13–14: `{key, type: text|number|select|multiselect|boolean, label, options?, required?, unit?, min?, max?, filterable?}`. Values live in `classifieds.attribute_values`. There is no attributes table.

### Locations, amenities, business, media, drafts

| Table | Migration | Role |
| --- | --- | --- |
| `locations` | `2026_10_25_100001` | Tree. `LocationType`: `municipality` მუნიციპალიტეტი, `town` ქალაქი, `borough` დაბა, `village` სოფელი, `district` უბანი, `protected_area` დაცული ტერიტორია. Wizard lists active rows that have a parent. |
| `business_profiles` | `2026_10_25_100002` | `BusinessType`: `hotel` სასტუმრო, `agency` უძრავი ქონების სააგენტო, `employer` დამსაქმებელი, `service` მომსახურება, `restaurant` რესტორანი. Not created by the listing wizard. |
| `amenities`, `amenityables` | same | `AmenityScope`: `hotel`, `room`, `property`. Seed list in `database/seeders/data/amenities.php`. |
| `media` | `2026_10_25_084358_create_media_table.php` | Spatie. Listing collection `gallery`. Draft collection `photos`. |
| `listing_drafts` | `2026_10_29_100001_create_listing_drafts_table.php` | `user_id`, `listing_type` varchar(16) nullable, `listing_id` nullable, `step`, `data` jsonb. Unique `(user_id, listing_type, listing_id)` where `listing_id` is not null. Pruned after 30 days. |
| `admin_listings` | view in `2026_10_27_100001_create_admin_tables.php` lines 15–23 and 76–82 | Union of hotel, property, classified, vacancy, place, article, event. Read model `AdminListing`. |
| `favorites`, `reports`, `reviews`, `saved_searches` | `2026_10_25_100008_create_engagement_tables.php` | Polymorphic engagement. Reviews are on hotels (the model uses `Reviewable`). Saved-search hits were added in phase 9. |
| `promotions` | `2026_10_25_100009_create_messaging_and_payments_tables.php` | Paid promotion is a later account flow, not submit. |
| `price_plans` | `2026_10_27_100001_create_admin_tables.php` | |
| `orders`, `business_subscriptions` | `2026_10_30_100000_create_monetization_tables.php` | Listing limits and payments. Not filled in by the wizard. |

`ListingModule` labels (`lang/ka/enums.php` lines 124–128): `hotels` სასტუმროები, `properties` უძრავი ქონება, `classifieds` განცხადებები, `vacancies` ვაკანსიები.

Morph map is `AppServiceProvider::MORPH_MAP` (`app/Providers/AppServiceProvider.php` lines 71–86): `hotel`, `property`, `classified`, `vacancy`, `room_type`, `booking_request`, `vacancy_application`, and the rest.

### Hotels admin resource was not deleted on this branch

`app/Filament/Resources/Hotels/HotelResource.php` is present (class at line 30) with create, edit, view, list, room types and booking requests.

[PR #4](https://github.com/Murvanidz3/infokobuleti/pull/4) is **open** and targets `cursor/listing-wizard-submit-fixes-0521`, not `develop`. It is not in this tree. That PR deletes `HotelResource` and, in migration `2026_11_05_120000_retire_hotel_module_and_add_projects`, drops `hotels`, `room_types`, `room_type_price_rules`, `room_availability` and `booking_requests`, copying hotels into `properties` as `deal=daily_rent`, `type=hotel`. Its own description says these would remain: `AmenityScope::Hotel`, `BusinessType::Hotel`, the `featured_hotel` price plan, and business profiles of type hotel. It also adds a `projects` table with no Filament resource. None of that is on `develop`, so there is no leftover-from-deletion to inventory here. Merging it would collide with this audit’s hotel section.

The `featured-hotel-30` plan is seeded today (`database/seeders/PricePlanSeeder.php` line 29, Georgian name **რჩეული სასტუმრო მთავარზე, თვე**).

## 2. Type taxonomy

Wizard modules are a fixed list, not categories (`app/Support/Listings/ListingForm.php` line 46): `property`, `hotel`, `classified`, `vacancy`.

Button labels (`lang/ka/account.php` lines 214–218):

- `property` — უძრავი ქონება
- `hotel` — სასტუმრო ან სახლი დასასვენებლად
- `classified` — ნივთი ან მომსახურება
- `vacancy` — ვაკანსია

Account type labels are shorter (`lang/ka/account.php` lines 25–28): სასტუმრო, უძრავი ქონება, განცხადება, ვაკანსია.

```
property  უძრავი ქონება
├── deal: sale იყიდება | rent ქირავდება | daily_rent ქირავდება დღიურად
└── type:
    ├── apartment        ბინა
    ├── house            სახლი
    ├── land             მიწის ნაკვეთი
    ├── commercial       კომერციული ფართი
    └── hotel_business   სასტუმრო (ბიზნესი)

hotel  სასტუმრო ან სახლი დასასვენებლად
├── hotel        სასტუმრო
├── guesthouse   საოჯახო სასტუმრო
├── apartment    აპარტამენტი
├── cottage      კოტეჯი
└── hostel       ჰოსტელი
    └── one room type at create; more rooms, seasons and calendar after, at /account/hotels/{id}/rooms

classified  ნივთი ან მომსახურება     (module classifieds)
├── transport  ტრანსპორტი
│   ├── cars  მსუბუქი ავტომობილები          schema: make*, model*, year*, mileage, fuel, transmission, steering, customs_cleared
│   ├── motorcycles  მოტოციკლები და სკუტერები   schema: year*, engine_cc, mileage
│   ├── bicycles  ველოსიპედები              schema: bike_type
│   └── auto-parts  ავტონაწილები            no schema
├── electronics  ელექტრონიკა
│   ├── phones  მობილური ტელეფონები         schema: brand*, storage_gb
│   ├── computers  კომპიუტერები და ლეპტოპები
│   └── tv-audio  ტელევიზორები და აუდიო
├── home-garden  სახლი და ბაღი
│   ├── furniture  ავეჯი
│   ├── appliances  საყოფაცხოვრებო ტექნიკა
│   └── garden-harvest  ბაღი და მოსავალი
├── clothing  ტანსაცმელი და ფეხსაცმელი      leaf, no schema
├── kids  საბავშვო ნივთები                  leaf, no schema
├── services  მომსახურება
│   ├── repair-construction  რემონტი და მშენებლობა
│   ├── transfers  ტრანსფერი და ტაქსი
│   ├── cleaning  დალაგება
│   └── tours  ტურები და გართობა
├── hobby-sport  ჰობი და სპორტი             leaf, no schema
└── animals  ცხოველები                      leaf, no schema

vacancy  ვაკანსია     (module vacancies, flat, no attribute schema)
├── hospitality          სასტუმრო და ტურიზმი
├── food-service         რესტორანი და კაფე
├── retail               ვაჭრობა და გაყიდვები
├── construction         მშენებლობა და რემონტი
├── transport-logistics  ტრანსპორტი და ლოგისტიკა
├── education            განათლება
├── healthcare           ჯანდაცვა
├── office               ოფისი და ადმინისტრირება
├── it-marketing         IT და მარკეტინგი
├── beauty               სილამაზე და ჯანმრთელობა
└── other                სხვა
```

`*` means `required: true` inside `database/seeders/data/categories.php`. Select options and units are in that file (fuel ბენზინი/დიზელი/ჰიბრიდი/გაზი/ელექტრო, mileage unit `km`, engine unit `cc`, and so on). Only `cars`, `motorcycles`, `bicycles` and `phones` have a schema. A child with an empty schema does not inherit one, because `schema()` stops at the first non-blank schema and these children store nothing.

News and events categories in the same seeder are not listing types.

Three other “service” ideas are not this category:

- `BusinessType::Service` — **მომსახურება**, a business profile type.
- `PlaceType::Service` — **მომსახურება**, a guide place.
- `lang/ka/home.php` lines 61–76 — styleguide-only search options (`cars`, `furniture`, `electronics`, `services`, `sport`, `kids` and job keys `tourism`, `trade`, `construction`, `services`). The live hero does not render them (`resources/views/pages/styleguide.blade.php` is the only caller). Job keys do not match vacancy slugs (`hospitality`, `retail`, …). There is no vacancy slug `services`.

Homepage tiles hardcode slugs, not ids (`app/Services/HomepageData.php` lines 50–64): property types `apartment` and `house`, classified slugs `cars` and `services`. No tile for land, commercial, or `hotel_business`.

No numeric category id is hardcoded in the creation flow. The wizard stores the id the user picked.

## 3. Creation chain

### Public entry

| Piece | Where |
| --- | --- |
| Routes | `routes/web.php` lines 173–178. `GET /add` (`listings.create`), `GET /account/listings/{type}/{id}/edit` (`listings.edit`). Inside `auth` + `onboarded` + `phone.verified`. `{type}` must be a key of `OwnerListings::TYPES`. |
| Controller | `app/Http/Controllers/Account/ListingWizardController.php`. Renders `pages.listings.wizard` only. |
| Component | `app/Livewire/ListingWizard.php`. |
| Form rules | `app/Support/Listings/ListingForm.php`. The Livewire step check and `SubmitListing` both call it. There is no FormRequest. |
| Views | `resources/views/livewire/listing-wizard.blade.php` plus `listing-wizard/{type,details,location,photos,price,preview}.blade.php`. |

Steps (`ListingForm::STEPS` line 43), labels `lang/ka/account.php` lines 205–211:

| # | Key | Label | What is shown |
| --- | --- | --- | --- |
| 1 | `type` | რუბრიკა | Four module buttons. Then the type-specific choice below. Open drafts (up to 5) resume via `?draft=`. |
| 2 | `details` | აღწერა | Title ka (required, 5–120), description ka (required, 20–5000). en/ru behind a toggle, optional, max 120 / 5000. Then module fields. |
| 3 | `location` | მდებარეობა | `location_id` required (active locations with a parent). Address optional, max 190, stored as Georgian only. Map pin. |
| 4 | `photos` | ფოტოები | Up to 20. First is the cover. |
| 5 | `price` | ფასი და კონტაქტი | Price or room or salary, then contact. |
| 6 | `preview` | გადახედვა | Summary, then submit. |

Autosave writes `listing_drafts.data` on every `form.*` change (`ListingWizard.php` lines 145–163 and 399–414). The listing row is untouched until submit. Editing a live listing copies it into a draft and does not change the live row until submit, which then forces `pending` again.

#### Step 1 conditionals

`resources/views/livewire/listing-wizard/type.blade.php`.

| Module | Shown | Required |
| --- | --- | --- |
| hotel | `hotel_type` pills | required, `HotelType` |
| property | `deal` pills, then `property_type` pills | both required |
| classified | parent category radios. If that parent has active children, a subcategory pill list | `category_id` must be an active leaf of module `classifieds` (`ListingForm.php` lines 547–560). Picking a childless parent sets `category_id` to that parent (`ListingWizard.php` lines 152–155). Changing category clears `attribute_values`. |
| vacancy | category pills (roots only; the seeder has no children) and `employment_type` | both required |

The type cannot be changed while editing (`chooseType` returns early, line 135).

#### Step 2 conditionals

`resources/views/livewire/listing-wizard/details.blade.php`.

Hotel: stars (1–5), rooms count (1–1000), check-in default `14:00`, check-out default `12:00`, season start/end months, hotel-scoped amenity pills. All optional in rules (`ListingForm.php` lines 418–427) except that a season month requires its pair (`required_with` on lines 423–424). The number inputs do not pass `optional` into the field component, so stars and room count look the same as a required field (no **არასავალდებულო** badge). Check-in, check-out and season do show the badge.

Property: the same numeric grid for every property type — area, land area, rooms, bedrooms, bathrooms, floor, total floors — then condition, building status, listed-by, cadastral code, and every property amenity (elevator, balcony, and the rest of `database/seeders/data/amenities.php` `property`). Rules (`ListingForm.php` lines 428–441):

- `area_m2` required unless type is `land`; `land_area_m2` required only when type is `land`. Range 1–100000 and 1–10000000.
- Rooms, bedrooms, bathrooms, floor, condition, building status, cadastral code optional.
- `listed_by` required, default `owner`.
- `total_floors` must be ≥ floor. `salary_max` / this use `not_below`.
- Cadastral code optional, max 32, regex `^[\d.\/-]+$`.

The blade computes an `optional` flag for area vs land (lines 50–51) and then ignores it (line 65 does not pass `:optional`). So the required land area and the optional room count look identical. Nothing is hidden for land, commercial, or `hotel_business`.

Classified: item condition (optional), then schema fields for the chosen category. Schema `required` is both a validation rule (`AttributeSchema::rules`) and the field badge. Select/number/text/boolean/multiselect are rendered (lines 123–148).

Vacancy: experience (optional), languages (default `['ka']`), benefits, deadline (optional, today through one year).

#### Step 3

Pin is required for `hotel` and `property`, optional for `classified` and `vacancy` (`ListingForm::requiresPin`, lines 260–263). Bounds are Kobuleti municipality: south 41.60, north 41.96, west 41.62, east 42.15 (lines 53). Outside pins are rejected in the UI (`setPin`) and by `between` rules. Address search is the `Geocoder` contract, 20 searches per minute per user. If location is empty, the nearest location within 6 km is filled (`ListingWizard.php` lines 572–585). `distance_to_sea_m` is derived on submit for hotel and property only. It is not a form field.

#### Step 4

Upload: jpg, jpeg, png, webp, max 15360 KB, sanitized by `ImageSanitizer` before it is stored on the draft (`ListingWizard.php` lines 194–230). At least one photo is required for hotel and property (`requiresPhotos`, lines 255–258). The rule is `required|array|max:20`. It does not re-check mime types; those are checked at upload. Gallery conversions are queued WebP: thumb 400, card 800, large 1600, plus a blurred placeholder (`app/Models/Concerns/HasMediaGallery.php` lines 23–38). `config/media-library.php` queues conversions by default. The gallery collection also accepts heic/avif; the wizard does not offer them.

#### Step 5

| Module | Price | Contact |
| --- | --- | --- |
| hotel, no rooms yet | One room: name (2–80), nightly `base_price` 1–100000, currency fixed as ₾, adults 1–20 default 2, units 1–500 default 1. All required. | phone required, email optional, website optional URL |
| hotel that already has rooms (edit) | Room form hidden. Link to `/account/hotels/{id}/rooms` only when editing. | same |
| property | `price` required, 1–100000000000. Currency required, default **USD** (`ListingForm.php` line 89). Label gains `/ თვე` or `/ დღე` when deal is `rent` or `daily_rent`. `PriceInsight` shown for properties only. | name optional, phone required |
| classified | `price` optional, 0–1000000000. Currency required, default GEL. `negotiable` checkbox. Hint: empty means შეთანხმებით. | name optional, phone required |
| vacancy | `salary_min` / `salary_max` optional integers 0–1000000, max ≥ min. Currency required default GEL. Period required default `month`. | `apply_method` required default `site`. Phone input only if method is `phone` (then required). Email input only if method is `email` (then required). |

Phone rule is `/^[\d\s()+-]{9,24}$/` (`ListingForm.php` line 56), then `Contact::normalizePhone`. A Georgian mobile is normalized; anything else is digits, truncated to 16 characters (`app/Support/Listings/Contact.php` lines 53–63). The placeholder `+995 5XX XX XX XX` is not enforced.

Property currency pills show the code (`GEL`), not the Georgian label. `options()` maps `value => value` (`ListingWizard.php` line 490).

#### Submit

`SubmitListing::handle` (`app/Actions/Listings/SubmitListing.php`):

1. Re-validates every step.
2. New listings check `ListingLimits` (monthly free count, or the business package’s active-listing cap). Over the cap throws `ListingLimitReached`. Edits do not consume a slot. Staff are unlimited (`ListingLimits.php` lines 81–83).
3. Creates or updates the model, translations for title and description, address as `ka` only.
4. Vacancy: attaches the oldest business profile if none is set.
5. Hotel: syncs amenity ids; creates one `RoomType` (Georgian name only) when `has_rooms` is false. That save refreshes `price_from`.
6. Property: syncs feature amenity ids. Classified attributes are re-filtered to known schema keys.
7. Photos: listing gallery becomes the form list, in order. Draft photos move onto `gallery`. Removed gallery photos are deleted.
8. Status becomes `pending` unless it already is. Draft, published, rejected, paused and expired all go back to the queue (published → pending is allowed, `ListingStatus.php` line 32).
9. Draft row is deleted.
10. `AnnounceToModerators` is queued and posts to the staff Telegram bot if the row is still pending.

Not done on submit: payment, SMS, Scout index (only `published` rows are searchable, `IndexedForSearch.php` lines 18–21), slug change (slug is set once on create from the Georgian title and never updated, `HasSlug.php` lines 19–26). The owner is told to expect a notification (`lang/ka/account.php` line 230). That notification is `ListingModerated`, sent on approve/reject, not on submit (`app/Actions/Moderation/ModerateListing.php`). Channels are the user’s moderation preferences (mail, database, push, Telegram — not SMS). `listings:expire` later sends `ListingExpiry`.

Promotion is a separate page, `GET /account/promote/{type}/{id}` (`routes/web.php` lines 157–158), products VIP, Super VIP, bump, auto-bump, featured home (`PromotionType`).

After a hotel exists, the owner manages further rooms, seasonal prices and the availability calendar in `app/Livewire/Account/HotelRooms.php`. That page is not a wizard step. A brand-new hotel cannot open it until submit has created the hotel id, and the wizard only shows the link while editing.

### Admin entry

Each module is its own Filament resource under `app/Filament/Resources/{Hotels,Properties,Classifieds,Vacancies}/`. They extend `app/Filament/Resources/ListingResource.php`. Permission is `Permission::ManageContent`. Shared owner and location blocks are `ListingAdmin::ownerSection()` and `locationSection()` (`app/Filament/Support/ListingAdmin.php` lines 251–287).

Create/edit pages use `SavesTranslations`, which drops empty locales. They do not call `ListingForm`. There is no media upload on any of these four forms (no `FileUpload` / gallery field under `app/Filament/Resources` for them). An admin-created listing has no photos unless they are added elsewhere.

Location in admin: `location_id` is not required. `lat`/`lng` are optional and bounded 41–42.5 (`ListingAdmin.php` lines 282–283), a wider box than the wizard’s Kobuleti bounds. Address is translatable in all three locales; the wizard only stores Georgian.

| Resource | Form | Conditional fields |
| --- | --- | --- |
| `HotelResource` lines 44–80 | Title (required, max 160), description (no min/max in the form), type required, stars, distance to sea, rooms count, check-in/out, season months, phone, email, website, hotel amenities, owner required, location. | None. Rooms are a relation manager on the edit page (`RoomTypesRelationManager`), not on create. Bookings are view-only management. |
| `PropertyResource` lines 44–88 | Title max 160, deal required, type required, price required `minValue(0)`, currency required **default GEL**, area, land area, rooms, bedrooms, bathrooms, floor, floors, distance, condition, building status, listed-by required default owner, cadastral code max 32 with **no regex**, contacts, all property features. | None. Land sees rooms and floor. `hotel_business` sees the same. |
| `ClassifiedResource` lines 42–63 | Category select of every classified category including parents (`categorySelect` lines 97–103, not `->required()`), price min 0, currency default GEL not marked required, negotiable, condition, contacts, `attribute_values` as a free key/value grid. | None. The category schema is not applied. |
| `VacancyResource` lines 47–77 | Category select (same helper, not required), employment type required, experience, deadline, salary min/max (`gte` salary_min), currency default GEL, salary period **not required**, languages as free tags (suggestions only), benefits, apply method required, apply phone and email always both visible and not conditionally required. | None. |

Admin can Approve a `draft` straight to `published` (`ListingAdmin.php` line 164), skipping the public “pending” step. Expiry is set on that transition (`HasStatus.php` lines 60–66).

## 4. Per-type matrix

“Shown” is the public wizard. Admin shows the whole column set of that module with almost nothing required.

| Type | Subtypes | Fields the wizard shows | Required (wizard) | Price model | Location | Media | Special / nonsense |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Property · apartment, house | deals sale / rent / daily | area, land area, rooms, bedrooms, bathrooms, floor, floors, condition, building status, listed-by, cadastral, all property amenities | title ka, description ka, deal, type, area, listed-by, location, pin, ≥1 photo, price, currency, phone | One `price`. Suffix `/ თვე` or `/ დღე` from the deal. Default currency USD in the wizard, GEL in admin. `price_gel` for filters. `price_per_m2` from `area_m2` on sale only (detail page). | location required, pin required, sea distance computed | 1–20 images | Daily rent is a label on the same price column, not a nightly calendar. Amenities include lift and balcony, which fit an apartment and are odd on a house with no building. |
| Property · land (`მიწის ნაკვეთი`) | same three deals | **Same fields as an apartment**, including rooms, bedrooms, bathrooms, floor, building condition frames, and amenities (lift, balcony, sea view). | `land_area_m2` instead of `area_m2`. Cadastral still optional. | Same single price. `price_per_m2` stays null unless someone also fills `area_m2`, because the generated column ignores `land_area_m2`. | same | same | No area unit (only m²), no purpose (agricultural / buildable / commercial), no road access. The Georgian strings for those exist only in `lang/ka/listing.php` and the styleguide. Area filter and “largest area” sort use `area_m2`, so a land plot with only land area is invisible to them. |
| Property · commercial | same | Same apartment form. | `area_m2` required. | same | same | same | No use-class, frontage, or ceiling height. |
| Property · `hotel_business` (**სასტუმრო (ბიზნესი)**) | same deals, including sale of the business | Same apartment form. No rooms, no stars, no check-in. | `area_m2` required. | A property price, not a nightly `price_from`. | same | same | Overlaps the Hotel module. A hotel-as-property cannot take a booking request. |
| Hotel · all five `HotelType`s | no further split | type, stars, room count, check-in/out, season, hotel amenities, then exactly one room (name, GEL nightly price, adults, units) | type, title, description, location, pin, ≥1 photo, that room, phone | Nightly GEL on the room. Card price is min room price. No currency choice. | pin required, sea distance computed | hotel gallery required; the first room has no photo in the wizard | Check-in defaults 14:00 / 12:00 but is optional. Room children, beds, size, room amenities, seasonal rules and the calendar are absent here. They exist on the account rooms page and in admin. `HotelType::Apartment` and `PropertyType::Apartment` are different things with different labels (აპარტამენტი vs ბინა). |
| Classified · goods (everything except `services`) | tree in section 2 | condition ახალი/მეორადი, plus schema fields only for cars, motorcycles, bicycles, phones | title, description, leaf category, schema-required keys, phone. Price optional. Photos optional. Pin optional. | One optional price + negotiable flag. No hourly or per-unit model. Default GEL. | location required, pin optional | optional, max 20 | Categories without a schema (furniture, clothing, animals, …) are title + condition + price only. |
| Classified · services (`მომსახურება` and its four children) | რემონტი და მშენებლობა, ტრანსფერი და ტაქსი, დალაგება, ტურები და გართობა | **Same as goods, including ახალი/მეორადი.** No schema on these categories. | same as goods | Same optional item price. No hourly / per job / per day. | same, pin optional | optional | Condition “new vs used” does not describe a service. No service area, duration, or licence. Homepage tile `?category=services` is the only special case, and it is a slug, not a type. |
| Vacancy · all 11 categories | same fields for every category | employment, experience, languages, benefits, deadline, salary range, period, apply method | title, description, category, employment type, location, salary currency, salary period, apply method. Salary itself optional. Phone or email required only when that method is chosen. | `salary_min`–`salary_max` plus period hour/day/month/season. No separate “negotiable” flag; a blank range displays as negotiable (`CardPresenter::salary`). | location required, pin optional | optional in the wizard | Photos are stored on `gallery` and **never rendered** on `resources/views/pages/jobs/show.blade.php` (no gallery). No shift, hours-per-week, or on-site/remote split beyond `employment_type=remote`. Employer name is not a field. |

### Where validation and the UI disagree

| Topic | Public wizard | Admin | Effect |
| --- | --- | --- | --- |
| Property area required-ness | Rules depend on `land` (`ListingForm.php` 429–430). The badge is computed and not rendered (`details.blade.php` 50–51 vs 65). | Both areas optional, `minValue(0)`. | Land sellers are not told which area is required until they press Next. Admin can save a land row with neither area. |
| Property price | min 1 | min 0, and the DB check is `>= 0` | Admin can store 0. The owner’s wizard then rejects that price on edit. |
| Title length | max 120, description min 20 | title max 160, description unbounded | A long or short admin text cannot be resubmitted through the wizard without edits. |
| Currency default | property USD, classified/vacancy GEL, hotel implicit GEL | property and others default GEL | Same apartment can be entered in two currencies depending on who creates it. |
| Cadastral | Optional. Hint says it is for moderators only (`lang/ka/account.php` line 294: **ჩანს მხოლოდ მოდერატორისთვის, გადამოწმებისთვის.**) | Optional, no regex | The public detail page prints it (`PropertyController.php` line 52). |
| Classified / vacancy category | Must be an active leaf | Select includes parents and is not `required()`, but the column is NOT NULL | Admin submit of an empty category fails in the database rather than in the form. A parent category is allowed in admin and forbidden in the wizard. |
| Classified attributes | Schema rules, unknown keys dropped | Free key/value, no schema | Admin can store keys the public page will not label, and can skip `make` / `model` / `year` / `brand`. |
| Vacancy languages | Closed set `ka,en,ru,tr,de` | Free tags | Admin can save a code the public page will uppercase and show raw (`VacancyController.php` line 35). |
| Vacancy apply phone/email | Shown and required only for that method | Both always visible, neither required | Admin can set method `phone` with no phone. The public page then has nothing to call. |
| Hotel room on create | Required | Not on the create form. Relation manager is on edit. | Admin can publish a hotel with `price_from` null and no rooms. The dates filter keeps hotels that have no rooms (`HotelIndex.php` lines 134–137). |
| Photos | Required for hotel and property | Not collected | Admin-published hotels and properties can have an empty gallery. The public “with photos” filter hides them. |
| Pin bounds | Kobuleti box | 41–42.5 | Admin can pin outside the municipality. The public edit then fails `between` until the pin is moved. |
| Season months | Pairwise `required_with` | Independent selects | Admin can save a start month without an end. `Hotel::isOpenInMonth` treats a missing side as open all year (`app/Models/Hotel.php` lines 56–58), so a half-filled season does not filter. |
| Listed-by / salary period | Required, with defaults | listed-by required; salary period not required (DB default `month` still applies if the column is omitted) | Mild. |

Duplicated logic, not shared code: `ListingForm` is the public contract; each Filament resource rebuilds the fields. `ClassifiedResource::categorySelect` is the only cross-resource helper, and the vacancy form uses it. Room create rules are copied again in `HotelRooms::saveRoom` (name, capacity, price, units) with extra children and size fields the wizard does not have.

## 5. Display and filters

List pages filter in SQL. The `q` keyword goes to Scout (Meilisearch, or the Postgres engine) and then the SQL filters still apply (`ListingIndex.php` lines 347–384). Saved searches store that query array on `saved_searches.filters` keyed by `ListingModule`. Renaming a filter property breaks saved searches and shared URLs.

Meilisearch filterable attributes (`config/scout.php` lines 130–148) are a smaller set than the SQL filters: hotels `type, stars, price, distance_to_sea_m, rating`; properties `deal, type, price, area_m2, rooms`; classifieds `category_id, price`; vacancies `category_id, employment_type, salary_min`. Attribute schemas, condition, salary currency and land area are not Scout filters. Keyword search covers title and description in ka/en/ru only.

| Surface | Route | What it reads |
| --- | --- | --- |
| Hotels index | `/hotels` `HotelIndex` | Dates and guests against `room_types` and `room_availability`; season months; `price_from` (GEL, not converted); `HotelType`; stars; sea distance buckets 100/300/500/1000; amenity **slugs**; rating 7/8/9; location. Sort by price, sea, rating, newest, promotion. |
| Hotel page | `/hotels/{slug}` `HotelController` | Type, stars, sea, check-in/out, room count, season, amenities, room cards (name, base price as `/ ღამე`, capacity, size, up to 6 room amenities), booking form, 3-month calendar. |
| Real estate index | `/real-estate` `PropertyIndex` | `deal`, `type[]`, price min/max in GEL or USD via `price_gel`, **`area_m2` only**, rooms 1–4 (4 means ≥4), floor range, location, building `new` (includes `under_construction`) or `old`, photos only. Sort includes `area_m2` and sea distance. |
| Real estate page | `/real-estate/{slug}` `PropertyController` | Facts only when filled: deal, type, both areas, rooms, bedrooms, bathrooms, floor, building status, condition, sea, `price_per_m2` on **sale** only, cadastral code, feature amenities, map. Price suffix from the deal. `PriceInsight` for this property. |
| Classifieds index | `/classifieds` `ClassifiedIndex` | Category **slug** plus its children; filterable schema fields (`select`, `multiselect`, `boolean`, `number` min/max) against `attribute_values`; price in `price_gel`; condition; location; photos. |
| Classified page | `/classifieds/{slug}` | Schema values with labels from `attribute_schema`, condition first, price or on-request, negotiable line. Breadcrumbs use parent and child slugs. |
| Jobs index | `/jobs` `VacancyIndex` | Category slug, employment type, minimum salary compared to `coalesce(salary_max, salary_min)` **with no currency conversion** (comment in code: “GEL vacancies; others are rare”, lines 106–108), posted within 1/3/7/30 days, location. No map of photos. Sort by salary. |
| Job page | `/jobs/{slug}` | Employment, salary range and period, category, experience, languages, deadline, benefits, apply-on-site form or mailto. No gallery. |
| Site search | `/search` `SiteSearch` | Groups hotels, properties, classifieds, vacancies (and guide, events, news). Published only. |
| Home tiles | `HomepageData::categories` | Counts and links for hotels, apartments, houses, cars, services, jobs. Land is not a tile. |

Cards (`app/Support/Listings/CardPresenter.php`): property features show `area_m2`, then rooms, then floor, and only if floor is empty and the type is house or land does it show `land_area_m2` as **ეზო** (`listing.yard`, lines 110–122). A land plot that also has a floor value hides the plot size on the card. A land plot with only `land_area_m2` shows that yard line and no built area.

Slug is the public route key. It is generated once from the Georgian title (`HasSlug`). Changing the title later does not change the URL.

## 6. Problems, by severity

1. **Cadastral code is described as moderator-only and rendered on the public page.** Hint `lang/ka/account.php:294`. Output `app/Http/Controllers/Site/PropertyController.php:52`. A redesign that “keeps current display” would keep leaking a parcel id.

2. **Land, commercial, services and hotel-as-property are not systems.** They reuse another type’s fields. Land still shows rooms, floor, renovation frames and apartment amenities (`details.blade.php:49–57` and `93–115`). Services still show **ახალი / მეორადი** (`details.blade.php:117–121`) and have no schema (`database/seeders/data/categories.php:95–101`). `hotel_business` is a property (`PropertyType.php:16`) beside a full hotel module with rooms and bookings.

3. **Public and admin forms are two contracts.** `ListingForm` versus `PropertyResource.php:51–68`, `HotelResource.php:50–80`, `ClassifiedResource.php:49–58`, `VacancyResource.php:54–73`. Concrete traps: title 120 vs 160, description min 20 vs none, property price min 1 vs 0, currency default USD vs GEL, leaf category vs any category, schema vs free JSON, Kobuleti pin vs a 41–42.5 box, photos required vs no uploader. An admin-created row often cannot be re-saved in the wizard without the owner fixing it.

4. **Land area does not participate in price-per-m², the area filter, or the area sort.** Generated column `database/migrations/2026_10_25_100004_create_properties_table.php:22`. Filter `app/Livewire/Listings/PropertyIndex.php:141–147`. Sort lines 111–112. The required-field badge for land is computed and dropped (`details.blade.php:50–51` and `:65`).

5. **Vacancy photos are accepted and never shown.** Wizard allows them (`ListingForm::requiresPhotos` returns false, so they are optional, not forbidden). `resources/views/pages/jobs/show.blade.php` has no gallery. The jobs index does not load `media`.

6. **Hotel creation stops at one Georgian-named room.** Beds, child capacity, room amenities, size, seasonal prices and the calendar exist in the schema and on `HotelRooms`, and are missing from the wizard (`ListingForm.php` lines 82 and 483–488, `SubmitListing.php` lines 92–98). Admin can publish with zero rooms (`HotelResource` form has no room repeater). `HotelType` and `PropertyType` both contain an apartment, and only one of them is bookable.

7. **Salary filter ignores currency.** `VacancyIndex.php:106–108`. A USD salary is compared as if it were GEL. The wizard allows USD and EUR.

8. **Hardcoded slugs, not ids, but still brittle.** `HomepageData.php:50–64` (`apartment`, `house`, `cars`, `services`). Styleguide copy in `lang/ka/home.php:48–76` uses job keys (`tourism`, `trade`, `services`) that are not vacancy slugs. No numeric category id was found in the creation path.

9. **Unused or half-wired columns.** `embedding` and `risk_score` are never written by application code. `room_types.beds` and `BedType` have no form. `lang/ka/listing.php:28–32` land-purpose strings are styleguide-only. Room-scoped amenities are seeded and displayed, not collected on create.

10. **Edit always returns a published listing to `pending` and hides it** (`SubmitListing.php:72–74`, copy at `lang/ka/account.php:231`). That is intentional, and it is easy to break if a per-type flow starts writing the live row directly.

11. **PR #4 is an unmerged competing model** of hotels-as-properties plus a `projects` table. Building a type registry on `develop` without deciding that PR will duplicate the hotel redesign.

## 7. Proposed target (no code)

Keep four storage tables until a type has a field that cannot live as a nullable column or a category schema. The mess is the shared form, not the existence of `properties`.

### A definition per public type

One PHP class per type the owner actually wants, registered in a list the wizard, Filament, validation, cards and filters all read. A definition declares:

- stable key (`property.land`, `classified.service`, `hotel`, `vacancy`, …)
- which steps exist (a service does not need the apartment step; a vacancy does not need a photo step)
- fields: key, input, required rule, show-when, storage (column or `attribute_values`)
- price model: `sale`, `monthly`, `nightly`, `salary_range`, `optional_quote`, `hourly`
- location rules: pin required or not, sea distance or not
- media rules
- display facts and which index filters exist

Shared fields stay in one place: title, description, location, contact phone, photos, status, slug, promotion. Type classes only add their own fields.

Suggested keys, matching today’s data so the first phases do not move rows:

| Definition | Today’s rows |
| --- | --- |
| `property.apartment`, `property.house`, `property.commercial` | `properties.type` |
| `property.land` | `properties.type = land` |
| `hotel` | `hotels` (all `HotelType`s can be a field on this definition, not five classes) |
| `property.hotel_business` | keep only if the business-for-sale case is real; otherwise fold into `hotel` or drop the enum case |
| `classified.goods` | classifieds whose category is not under `services` |
| `classified.service` | category slug `services` and its children |
| `vacancy` | `vacancies` |

Do not introduce a fifth table for land or services in the first phases. Land needs a purpose enum and an area that filters understand. Services need the goods condition hidden and a price period (hour / job / quote). Both fit the current tables plus a show-when rule.

### Migration strategy

1. Add the registry and point the wizard at it, generating the same rules `ListingForm` has today. No column changes. Existing Pest wizard tests should stay green.
2. Turn on show-when: hide rooms, floor, renovation and apartment amenities when type is `land`; hide item condition when the category is under `services`; mark land area required in the UI. Still the same columns. Old rows that have rooms on a land plot keep the values but the form no longer asks.
3. Additive columns or schema only where a type is missing data: land purpose and area unit; service price period; vacancy schedule if the product wants it. Backfill nothing that you cannot know. Leave nulls.
4. Point Filament forms at the same definitions so admin cannot save a title of 160 characters or a parent category. One test per type that submits the admin form and the wizard with the same payload.
5. Only after that, decide hotels. Either keep the `hotels` module (and hide `hotel_business` from the property form) or adopt PR #4’s “hotel is a property, no bookings” model. Do not ship a third representation. If PR #4 lands, delete the hotel definition instead of merging field lists by hand.
6. Display and filters read the definition’s fact list. Change `price_per_m2` only in a new migration (the comment on `ListingSchema` says existing migrations depend on its exact output). For land, filter on `coalesce(land_area_m2, area_m2)` rather than rewriting the generated column in place. Reindex Scout after filterable attributes change. Saved-search JSON uses today’s query keys (`deal`, `type`, `area_min`, `category`, …); keep those keys or rewrite `saved_searches.filters` in the same deploy.

### Rollout, each step shippable

| PR | Ships | Risk if it stops here |
| --- | --- | --- |
| 1 | Registry that mirrors `ListingForm` exactly. Wizard calls it. No UI change. | None beyond a refactor bug. Cover with the existing wizard tests. |
| 2 | Show-when for land and services. UI and rules match. | Old land rows may still have room counts stored. Display already hides empty facts. |
| 3 | Admin forms use the registry. Same required rules, leaf categories, photo note or a real uploader. | Editors lose the ability to save “loose” rows. That is the point. |
| 4 | Land purpose + area filter uses land area. Service price period. Both nullable. | Filters get slightly richer. Saved searches that only set `area_min` start matching plots. Call that out in the PR. |
| 5 | Hotel decision, coordinated with PR #4. | Highest risk: bookings, `price_from`, room calendar, featured-hotel plan, homepage hotel block. |
| 6 | Vacancy: either show the gallery or stop collecting photos. Add employer display from the business profile in the wizard so the owner sees it before submit. | Low. |

Do not rewrite slugs. Do not make edit skip moderation unless that is an explicit product change. Do not drop `attribute_values`; goods schemas should stay data in `categories.attribute_schema` so a new phone brand is not a deploy. Services and land should not grow a second EAV if a column or a small enum will do.

### Risks

- PR #4 already deletes the hotel module on another branch. A registry that treats `Hotel` as permanent will conflict line-by-line.
- `price_per_m2` is a generated column. Changing the expression is a migration, and Postgres must rewrite the column.
- Saved searches and shared URLs store filter names. Renaming `area_min` or `type` silently empties people’s alerts.
- Scout documents use `type`, `deal`, `category_id`, `salary_min`. A new land purpose is invisible to keyword filters until `search:sync` runs.
- Admin and the wizard can both write the same row. Until PR 3, moderators can still bypass the new rules.
- Category slugs `cars` and `services` are hardcoded on the homepage. Renaming those categories in admin breaks the tiles even if the registry is perfect.
- This audit did not read a live database. If production categories were edited after seed, the tree in section 2 is the code’s intent, not a dump of production.

## 8. Hotels decision: PR #4 path

Owner decision, recorded here and not implemented: a hotel is a plain listing with no bookings. This section compares [PR #4](https://github.com/Murvanidz3/infokobuleti/pull/4) (`cursor/real-estate-ssge-model-cfab` at `b5feb7c`, base `cursor/listing-wizard-submit-fixes-0521` at `f05f45c`) with `develop` at `3033ca3`. Nothing was merged. No migration was run. The file list is `git diff --name-status f05f45c..b5feb7c` (the two commits GitHub shows on the PR): 164 files, +2458 / −3957, 42 deleted, 17 added, 105 modified.

Retargeting that branch onto `develop` is a different set. `develop` does not contain `f05f45c` (“Keep published listing edits on the site”, 18 files, adds `ListingPhotos` and rewrites `SubmitListing`). `git log origin/develop..origin/cursor/real-estate-ssge-model-cfab` is three commits, not two. A merge of the branch tip, or a GitHub retarget, replays that parent. A rebase of only the PR commits is `git rebase --onto develop f05f45c`.

### 8.1 Files the PR deletes

Hotel module, bookings, and the public hotel pages:

- `app/Actions/Hotels/AnswerBookingRequest.php`, `app/Actions/Hotels/UpdateAvailability.php`
- `app/Enums/BookingStatus.php`, `app/Enums/HotelType.php`
- `app/Filament/Resources/Hotels/HotelResource.php` and its four pages, plus `BookingRequestsRelationManager.php`, `RoomTypesRelationManager.php`
- `app/Http/Controllers/Account/HotelRoomsController.php`, `app/Http/Controllers/Site/BookingRequestController.php`, `app/Http/Controllers/Site/HotelController.php`
- `app/Livewire/Account/Bookings.php`, `app/Livewire/Account/HotelRooms.php`, `app/Livewire/Listings/HotelIndex.php`, `app/Livewire/Site/ReviewForm.php`
- `app/Models/BookingRequest.php`, `app/Models/Hotel.php`, `app/Models/RoomAvailability.php`, `app/Models/RoomType.php`, `app/Models/RoomTypePriceRule.php`
- `app/Notifications/BookingAnswered.php`, `app/Support/Listings/AvailabilityCalendar.php`
- Factories: `BookingRequestFactory`, `HotelFactory`, `RoomAvailabilityFactory`, `RoomTypeFactory`, `RoomTypePriceRuleFactory`
- `database/seeders/HotelSeeder.php`, `database/seeders/data/hotels.php`
- Views: `components/card/hotel.blade.php`, `livewire/account/bookings.blade.php`, `livewire/account/hotel-rooms.blade.php`, `livewire/listings/hotels.blade.php`, `livewire/site/review-form.blade.php`, `pages/account/bookings.blade.php`, `pages/account/hotel-rooms.blade.php`, `pages/home/hotels.blade.php`, `pages/hotels/index.blade.php`, `pages/hotels/show.blade.php`

The admin restyle on `develop` never edited `HotelResource`. Last touch of `app/Models/Hotel.php` on `develop` is the Phase 3 import. Deleting those files does not collide with PR #18 (admin sidebar) or the dark-admin commits.

### 8.2 Files the PR adds

- `app/Http/Controllers/Site/ProjectController.php`, `app/Livewire/Listings/ProjectIndex.php`, `app/Models/Project.php`
- `app/Support/Listings/PropertyLegacyValues.php` (the enum rewrite the first migration calls)
- `database/factories/ProjectFactory.php`, `database/seeders/ProjectSeeder.php`, `database/seeders/data/property_internet_previous.php`
- `database/migrations/2026_11_05_090000_reshape_property_listings.php`
- `database/migrations/2026_11_05_120000_retire_hotel_module_and_add_projects.php`
- `resources/views/livewire/listings/projects.blade.php`, `resources/views/pages/real-estate/projects/index.blade.php`, `resources/views/pages/real-estate/projects/show.blade.php`
- Tests: `ProjectBrowseTest`, `PropertyBrowseTest`, `PropertyIndexTest`, `ListingFormPropertyTest`, `PropertyLegacyValuesTest`

Projects, a `pledge` deal, and the condition/type rename are in this PR. The owner decision in this section is only “hotel is a listing, no bookings”.

### 8.3 Files the PR changes

Shared listing code: `SubmitListing`, `DeleteAccount`, `ExportUserData`, `LaunchCheck`, `BackupRestoreTest`, `ListingModule`, `PropertyType`, `PropertyDeal`, `PropertyCondition`, `HomepageCuration`, `ModerationQueue`, `ModerationReview`, `SiteSettings`, `AdminStats`, `HomeController`, `BusinessProfileController`, `EventController`, `GuideController`, `OgImageController`, `PropertyController`, `Favorites`, `ListingWizard`, `ListingIndex`, `PropertyIndex`, `Amenity`, `BusinessProfile`, `HasSlug`, `Property`, `SavedSearch`, `User`, `AppServiceProvider`, `SiteSearch`, `AccountMenu`, `OwnerListings`, `SavedSearchMatcher`, `Catalog`, `HomepageData`, `PriceInsight`, `SitemapWriter`, `StructuredData`, `ReviewEligibility`, `CardPresenter`, `DetailPresenter`, `ListingForm`, `ListingQuery`, `ListingTypes`, `PublicUrl`, `NotificationPresenter`.

Config: `config/listings.php` (drops `hotel` expiry, adds `project` = 90), `config/scout.php`, `config/seo.php`, `config/site.php`.

Data: `SeedsDemoData`, `DemoSeeder`, `DemoUserSeeder`, `EngagementSeeder`, `PropertySeeder`, `data/amenities.php`, `FavoriteFactory`, `PropertyFactory`, `ReviewFactory`.

Lang: `account`, `admin`, `enums`, `home`, `nav`, `site` in `ka`, `en`, and `ru`.

Front: `resources/js/ui/listing.js`, `resources/js/ui/map.js`, `components/detail/map.blade.php`, `components/hero/search.blade.php`, `errors/layout.blade.php`, `livewire/account/favorites.blade.php`, wizard `details`, `location`, `price`, `type`, `livewire/listings/properties.blade.php`, `pages/business/show.blade.php`, `pages/events/show.blade.php`, `pages/guide/show.blade.php`, `pages/real-estate/index.blade.php`, `pages/real-estate/show.blade.php`, `pages/styleguide.blade.php`, `routes/web.php`.

Tests: `DatabaseSeederTest`, `HomepageTest`, `PricingTest`, `RelationsTest`, `SchemaTest`, `SlugTest`, `StatusTransitionsTest`, `TranslatableContentTest`.

`PropertyType` on the PR is `apartment`, `house`, `land`, `commercial`, `hotel` (`hotel_business` is gone). `PropertyDeal` gains `pledge`. `PropertyCondition` drops `needs_renovation` and adds `renovated`. `routes/web.php` replaces `hotels.index` / `hotels.show` / `hotels.booking` with a 301 from `/hotels/{slug?}` to `real-estate.type` for `type=hotel`, and adds `/real-estate/projects`. Every old hotel URL lands on the type index, not on the copied property.

### 8.4 Migrations, and what they drop

Both files are new. Neither exists on `develop`. `develop` already has every migration through `2026_11_04_090000_create_sms_manager_tables.php`, and so does the PR branch, so these two would run after phase 9, redirects, Pulse, and the SMS manager.

**`2026_11_05_090000_reshape_property_listings`** does not touch hotels, rooms, or bookings.

- Adds `properties.nearby` (jsonb, default `[]`) and an index on `properties.condition`.
- Drops and recreates the generated column `properties.price_per_m2` as `round(price / nullif(coalesce(area_m2, land_area_m2), 0), 2)`. The inputs stay. Land rows that only have `land_area_m2` gain a value. `down()` puts back the old expression (`area_m2` only).
- Calls `PropertyLegacyValues::apply()`: `hotel_business` → `hotel`, `needs_renovation` → `old_renovation`. Inserts extra amenity rows.
- `down()` runs `PropertyLegacyValues::rollback()`. That maps every `type=hotel` back to `hotel_business` (including hotels created after the migration) and `pledge` → `sale`. It does not map `old_renovation` back to `needs_renovation`. The class comment says the two cannot be told apart. `needs_renovation` is gone even after rollback. `country_house` is in the rollback map but is not a value on `develop`’s `PropertyType`.

**`2026_11_05_120000_retire_hotel_module_and_add_projects`** is the hotel move. `up()` order:

1. Creates `hotel_module_snapshots` (`hotel_id`, `payload` jsonb). For each `hotels` row, the payload stores the hotel row, its `room_types`, `room_type_price_rules`, `room_availability`, and `booking_requests`.
2. Inserts a `properties` row per hotel: `deal=daily_rent`, `type=hotel`, `currency=GEL`, `price` = `price_from`, else `min(room_types.base_price)`, else `0`, and `price_gel` set to that same number (no exchange conversion). Copies user, business, status, title, description, location, lat/lng, address, views, publish/expiry, moderation, risk, promotion level and expiry, timestamps, `deleted_at`, `rooms` from `rooms_count`, `distance_to_sea_m`, `contact_phone`, `listed_by=owner`. Slug gets a `-2` suffix when `properties` already has it. Does not copy `stars`, `type` (hotel vs guesthouse vs hostel), `check_in_from`, `check_out_until`, season months, `contact_email`, `website`, `rating_avg`, `reviews_count`, or `embedding`.
3. Repoints `media` (model type `hotel` only), `favorites`, `reports`, `daily_stats`, `promotions`, and `conversations` onto the new property id. Room photos stay `model_type=room_type` and do not join the property gallery.
4. Deletes `reviews` where `reviewable_type=hotel`. They are not in the snapshot.
5. Deletes `amenityables` where `amenityable_type=hotel`. They are not copied onto the property and not snapshotted. Room amenity rows (`room_type`) are left in place.
6. Stores ids of `properties.type=country_house`, then sets those rows to `house`. On `develop` that enum value does not exist, so this updates nothing unless stray rows do.
7. Drops `properties.nearby` (the column the previous migration just added). Any checklist written between the two migrations is discarded. `develop` has no `nearby` column today, so there is no existing checklist to lose.
8. Drops, in order, `booking_requests`, `room_availability`, `room_type_price_rules`, `room_types`, `hotels`.
9. Creates `projects` (listing columns plus `developer`, `completion_on`, `price_per_m2`, `currency`, `price_gel`, contact, `distance_to_sea_m`).
10. Recreates view `admin_listings` without `hotels` and with `projects`.
11. Inserts 301 rows for `/hotels`, `/en/hotels`, `/ru/hotels` only.
12. Rewrites `saved_searches` where `module=hotels` to `module=properties` and sets `filters.type` to `["hotel"]`, replacing any type filter that was already there.
13. Deletes `listing_drafts` where `listing_type=hotel`. Not snapshotted.

Not updated: `saved_search_hits.listing_type` (phase 9, present on both trees), `activity_log` subjects, and the settings key `homepage.featured_hotels` (those ids are hotel ids, not the new property ids).

**What is gone from the live site after `up()`, even though some of it sits in `hotel_module_snapshots`:**

| Data | Live row after `up()` | In snapshot | `down()` puts it back |
| --- | --- | --- | --- |
| Hotel row, including stars, check-in/out, season, email, website, rating | Only the property columns listed above. Stars, season, email, website, rating, hotel-vs-guesthouse type are not on the property. | Yes | Yes, into a recreated `hotels` row. `embedding` is unset on insert. |
| Room types, seasonal price rules, availability, booking requests | Tables dropped. Not copied to any live table. | Yes | Yes, original ids. |
| Hotel gallery | Moved onto the property. | Media rows stay in `media`. | Yes, media is pointed back at the hotel, then the property row is deleted. |
| Room photos | Stay on `room_type` and leave the public gallery. | No, but media rows remain. | Reconnect if room ids match. Not moved onto the property meanwhile. |
| Favorites, reports, daily stats, promotions, conversations | Pointed at the new property. | No | No. `down()` deletes the property and does not point these back at the hotel. They dangle. |
| Reviews of hotels | Deleted. | No | No |
| Hotel amenity links | Deleted. | No | No |
| Hotel wizard drafts | Deleted. | No | No |
| Saved searches for module `hotels` | Rewritten to properties / type hotel. | No | No |
| `homepage.featured_hotels` | Setting left as hotel ids. Homepage block removed. | No | No |
| `needs_renovation` | Stored as `old_renovation`. | No | No |
| `/hotels/{slug}` | 301 to the hotel-type index, not the new property URL. | n/a | Route and the three index redirects are removed. Per-slug redirects were never rows. |
| Featured-hotel price plan | Row, enum, and seeder stay. `Catalog` no longer offers the product; `PromoteListing` still lists `featured_hotel`. Homepage no longer has the hotels block. | n/a | Plan was never dropped. |

`down()` of the retire migration does recreate the five dropped tables from `ListingSchema`, reinserts snapshot rows, restores media, deletes the copied property rows, restores `country_house` by the stored ids, re-adds an empty `nearby` column, and drops `hotel_module_snapshots` and `projects`. Projects created after the migration are dropped with the table. A property of type `hotel` created after the migration is not turned back into a hotel. `down()` is therefore a partial restore of hotels that existed at migration time, not a working undo of reviews, amenities, drafts, saved searches, curated picks, embeddings, or the non-media morphs.

The featured-hotel plan (`price_plans` slug `featured-hotel-30`, `PricePlanProduct::FeaturedHotel`) is not in either migration. `AmenityScope::Hotel` and `BusinessType::Hotel` stay so old amenity rows still load. The PR body says the homepage no longer offers that slot.

### 8.5 Rebase against current develop

`git merge-base origin/develop origin/cursor/real-estate-ssge-model-cfab` is `dccf319`. `develop` has 45 commits after that. The PR branch has 3. Files both sides changed since that base: 11 (`AdminStats`, `HomeController`, `HomepageData`, `lang/{en,ka,ru}/admin.php`, `hero/search.blade.php`, `styleguide.blade.php`, `HomepageTest`, `StatusTransitionsTest`, `Pest.php`). No file that `develop` edited is a file the PR deletes. The admin redesign did not touch the hotel resource.

A throwaway rebase of the two PR commits onto `origin/develop` (`git rebase --onto develop f05f45c`) stopped on commit 1 of 2:

- `app/Livewire/ListingWizard.php`: one conflict, the import block.
- `resources/views/components/hero/search.blade.php`: modify/delete. `develop` already removed that partial (the hero is `components/hero.blade.php` plus `compact-search`). The PR edits the deleted file. Resolution is to keep it deleted.

Commit 2 was not replayed. It overlaps `develop` on 10 paths. `git merge-tree` of the whole branch tip (parent included) conflicts on five files: `AdminStats.php`, `HomepageTest.php`, `StatusTransitionsTest.php`, `tests/Pest.php` (that one is the parent commit), and the same hero modify/delete. `HomeController`, `HomepageData`, the three `admin.php` files, and `styleguide.blade.php` auto-merged.

Git conflict size is small. The branch is still a bad rebase target because of what applies cleanly: property taxonomy (`pledge`, `hotel` replacing `hotel_business`, `needs_renovation` collapsed), a new `projects` module, Scout settings moved from the `algolia` key to the `meilisearch` key, real-estate routes rewritten to `/{type}` and `/{type}/{deal}`, and, if the parent comes along, a change that keeps published edits live. `develop`’s `SubmitListing` still forces `pending` on edit (section 6).

### 8.6 Hotel references still on develop, and whether PR #4 covers them

| On `develop` | Where | PR #4 |
| --- | --- | --- |
| Homepage counts, category tile, featured carousel | `HomepageData.php` lines 49, 60, 87–88 (`Hotel::query()`, `hotels.index`, `homepage.featured_hotels`) | Removed. Properties carousel uses `homepage.featured_properties`. Old featured-hotel ids are not remapped. |
| Featured-hotel plan and purchase | `PricePlanSeeder` slug `featured-hotel-30`; `Catalog.php` line 82 adds `FeaturedHotel` for hotels; `PricePlanProduct::FeaturedHotel`; `HomepageCuration` field `featured_hotels` | Curation field and catalog entry go away. Enum, seeder row, billing copy, and the promote-listing icon stay. No migration deactivates the plan. |
| `price_from` | `hotels.price_from` in `2026_10_25_100003`; admin label `ფასი -დან` | Copied into `properties.price`, then the column drops with `hotels`. Event `price_from` is a different column and stays. |
| Public routes | `routes/web.php` lines 80–82 and account rooms at line 169 | Replaced by the 301 described above. Account room manager is deleted. |
| Scout | `config/scout.php` line 130, `Hotel::class` filterable `status`, `type`, `stars`, `price`, `distance_to_sea_m`, `rating`, `_geo` | Hotel index removed. Project index added. The settings block moves to the `meilisearch` key. |
| Sitemap | `SitemapWriter.php` lines 30 and 46, `hotels.index` and `hotels.show` | `hotels` removed, `projects` added. |
| Wizard type `hotel` | `ListingForm::TYPES`, hotel rules, one default room in `SubmitListing` | Type removed. Hotel fields become `PropertyType::Hotel`. |
| Morph map | `AppServiceProvider` aliases `hotel`, `room_type`, `booking_request` | Removed. |
| Search, owner list, expiry | `SiteSearch` key `hotels`; `OwnerListings` type `hotel`; `config/listings.php` expiry `hotel` => 365 | Removed. The migration copies each row’s `expires_at`. A later renewal goes through `Settings::expiryDays()` for morph `property` (60 in config, or the admin setting), not 365. |
| Tests and seed | `HomepageTest` builds `Hotel` factories; `HotelSeeder` | Hotel factory and seeder deleted. Homepage test is one of the rebase conflicts. |
| Reviews | `ReviewForm` requires a booking for a hotel | Component deleted. Existing hotel reviews are deleted by the migration, not archived. |

About 83 PHP/Blade files on `develop` still mention the hotel module. After the PR, `App\Models\Hotel` is gone. Leftovers are the featured-hotel product, `AmenityScope::Hotel`, `BusinessType::Hotel`, and `price_from` as a display string and as the events column.

### 8.7 Recommendation

Do not rebase PR #4 as-is, and do not merge it.

Redo the hotel decision as a new PR on `develop`, phase 0 of the registry plan in section 7. Scope is only “a hotel is a property listing, no bookings”:

- Copy each `hotels` row into `properties` first (`deal=daily_rent`, `type=hotel`, price from `price_from` or the minimum room price, slug preserved or suffixed). Keep the copy step. Also copy `contact_email` if the property row has somewhere to put it; otherwise archive it.
- Snapshot rooms, price rules, availability, and bookings, as PR #4 does.
- Also snapshot, or copy, what PR #4 deletes: hotel reviews, hotel amenity links, hotel drafts, `saved_searches` rows (keep the old JSON), and `homepage.featured_hotels` (rewrite ids to the new property ids or archive the setting).
- Rename the five tables (`hotels`, `room_types`, `room_type_price_rules`, `room_availability`, `booking_requests`) to an `archive_` prefix. Do not `drop` them in this PR. Point `media`, favorites, reports, stats, promotions, and conversations at the new property, and teach `down()` to point every one of those back, not only `media`.
- Add 301s from `/hotels/{slug}` to the new property URL, not only to the type index.
- Leave `projects`, `pledge`, the `needs_renovation` collapse, the nearby add-then-drop, and the Scout key move out of this PR. The `price_per_m2` expression that uses `coalesce(area_m2, land_area_m2)` is worth its own migration; it is the land-area bug in section 6, and it is reversible.
- Deactivate or stop selling `featured-hotel-30` in the same PR, since the homepage block goes away. Do not leave the plan purchasable with a stale Georgian description.
- Do not bring `f05f45c`. Keeping a published edit live is a separate product decision, and `develop` currently returns edits to `pending`.

The registry rollout in section 7 should treat this fresh PR as step 5, and should not grow a third hotel representation while it is open.
