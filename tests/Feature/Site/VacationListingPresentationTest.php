<?php

use App\Enums\ListingStatus;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\VacationType;
use App\Models\Property;
use App\Models\User;

it('files a guesthouse under vacation and does not show the daily-rent pill', function () {
    $guesthouse = vacationStay(VacationType::Guesthouse, 'ეზო ზღვის პირას');
    $similar = vacationStay(VacationType::Apartment, 'ბინა დღიურად');

    $page = $this->get(localized_route('real-estate.show', [$guesthouse]));

    $page->assertOk()
        ->assertSeeInOrder([
            '"name":"'.__('nav.home').'"',
            '"name":"'.__('nav.hotels').'"',
            '"name":"'.VacationType::Guesthouse->getLabel().'"',
            '"name":"ეზო ზღვის პირას"',
        ], false)
        ->assertSee(localized_route('real-estate.index', ['rubric' => 'vacation']))
        ->assertSee(localized_route('real-estate.index', [
            'rubric' => 'vacation',
            'type' => [VacationType::Guesthouse->value],
        ]))
        ->assertSee($similar->title)
        ->assertSee(VacationType::Guesthouse->getLabel())
        ->assertSee(VacationType::Apartment->getLabel())
        ->assertDontSee('"name":"'.__('nav.real_estate').'"', false)
        ->assertDontSee('"name":"'.PropertyType::Hotel->getLabel().'"', false)
        ->assertDontSee(localized_route('real-estate.index', ['type' => [PropertyType::Hotel->value]]))
        ->assertDontSee(PropertyDeal::DailyRent->getLabel());
});

it('keeps a sale listing under real estate with its deal pill', function () {
    $sale = Property::factory()->create([
        'deal' => PropertyDeal::Sale,
        'type' => PropertyType::Apartment,
        'vacation_type' => null,
        'title' => ['ka' => 'ბინა გასაყიდი', 'en' => 'Apartment for sale', 'ru' => 'Квартира на продажу'],
    ]);

    $this->get(localized_route('real-estate.show', [$sale]))
        ->assertOk()
        ->assertSee('"name":"'.__('nav.real_estate').'"', false)
        ->assertSee('"name":"'.PropertyType::Apartment->getLabel().'"', false)
        ->assertSee(localized_route('real-estate.index', ['type' => [PropertyType::Apartment->value]]))
        ->assertSee(PropertyDeal::Sale->getLabel())
        ->assertDontSee(localized_route('real-estate.index', ['rubric' => 'vacation', 'type' => [PropertyType::Apartment->value]]))
        ->assertDontSee(PropertyDeal::DailyRent->getLabel());
});

it('uses the vacation type on listing cards', function () {
    $guesthouse = vacationStay(VacationType::Guesthouse, 'ეზო ზღვის პირას');
    vacationStay(VacationType::Apartment, 'ბინა დღიურად');
    $sale = Property::factory()->create([
        'deal' => PropertyDeal::Rent,
        'type' => PropertyType::House,
        'vacation_type' => null,
        'title' => ['ka' => 'სახლი თვიურად', 'en' => 'House by the month', 'ru' => 'Дом помесячно'],
    ]);

    $this->get(localized_route('real-estate.index', ['rubric' => 'vacation']))
        ->assertOk()
        ->assertSee($guesthouse->title)
        ->assertSee('ბინა დღიურად')
        ->assertSee(VacationType::Guesthouse->getLabel())
        ->assertDontSee(PropertyDeal::DailyRent->getLabel())
        ->assertDontSee($sale->title);

    $this->get(localized_route('real-estate.index'))
        ->assertOk()
        ->assertSee($sale->title)
        ->assertSee(PropertyDeal::Rent->getLabel())
        ->assertDontSee($guesthouse->title)
        ->assertDontSee(PropertyDeal::DailyRent->getLabel());
});

it('shows the same vacation breadcrumb and no daily-rent pill on the owner preview', function () {
    $owner = User::factory()->create();
    $guesthouse = vacationStay(VacationType::Guesthouse, 'ეზო ზღვის პირას', $owner, pending: true);
    $url = localized_route('real-estate.show', [$guesthouse]);

    $this->get($url)->assertNotFound();

    $this->actingAs($owner)->get($url)
        ->assertOk()
        ->assertSee(__('site.preview', ['status' => ListingStatus::Pending->getLabel()]))
        ->assertSee('"name":"'.__('nav.hotels').'"', false)
        ->assertSee('"name":"'.VacationType::Guesthouse->getLabel().'"', false)
        ->assertSee(localized_route('real-estate.index', ['rubric' => 'vacation']))
        ->assertDontSee('"name":"'.__('nav.real_estate').'"', false)
        ->assertDontSee(PropertyDeal::DailyRent->getLabel());
});

it('uses the vacation type in English too', function () {
    $guesthouse = vacationStay(VacationType::Guesthouse, 'ეზო ზღვის პირას');

    $this->get(localized_route('real-estate.show', [$guesthouse], 'en'))
        ->assertOk()
        ->assertSee('"name":"'.__('nav.hotels').'"', false)
        ->assertSee('"name":"Guesthouse"', false)
        ->assertDontSee('Daily rent')
        ->assertDontSee('"name":"Hotel"', false);
});

function vacationStay(VacationType $type, string $title, ?User $owner = null, bool $pending = false): Property
{
    $factory = Property::factory();

    if ($pending) {
        $factory = $factory->pending();
    }

    if ($owner !== null) {
        $factory = $factory->for($owner);
    }

    return $factory->create([
        'deal' => PropertyDeal::DailyRent,
        'type' => $type->storedPropertyType(),
        'vacation_type' => $type,
        'title' => ['ka' => $title, 'en' => $title === 'ეზო ზღვის პირას' ? 'Yard by the sea' : $title, 'ru' => $title],
    ]);
}
