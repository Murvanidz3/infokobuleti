<?php

use App\Enums\AmenityScope;
use App\Enums\CommercialSubtype;
use App\Enums\LandPurpose;
use App\Enums\LandUtility;
use App\Enums\ListedBy;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Enums\VacationType;
use App\Livewire\ListingWizard;
use App\Models\Amenity;
use App\Models\Location;
use App\Models\User;
use Database\Factories\Support\Kobuleti;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('ka');
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
    $this->user = User::factory()->create([
        'name' => 'ნინო ბერიძე',
        'phone' => '+995599123456',
        'phone_verified_at' => now(),
    ]);
    $parent = Location::factory()->create(['parent_id' => null]);
    $this->location = Location::factory()->create([
        'parent_id' => $parent->id,
        'lat' => 41.8180,
        'lng' => 41.7765,
        'name' => Kobuleti::same('ბულვარი'),
    ]);
});

it('lists every filled house field on the review step, including bedrooms', function () {
    $wizard = Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseType', 'property')
        ->set('form.deal', PropertyDeal::Rent->value)
        ->set('form.property_type', PropertyType::House->value)
        ->call('next')
        ->assertHasNoErrors()
        ->set('form.title.ka', 'ზღვის სახლი')
        ->set('form.description.ka', 'დეტალური აღწერა, რომელიც აკმაყოფილებს მინიმალურ სიგრძეს.')
        ->set('form.area_m2', '120')
        ->set('form.land_area_m2', '400')
        ->set('form.rooms', '5')
        ->set('form.bedrooms', '3')
        ->set('form.total_floors', '2')
        ->set('form.floor', '2');

    $wizard = listingReviewPreview($wizard);

    expect(summaryLabels($wizard))->toBe([
        __('account.wizard.fields.deal'),
        __('account.wizard.fields.property_type'),
        __('account.wizard.fields.area_m2'),
        __('account.wizard.fields.land_area_m2'),
        __('account.wizard.fields.rooms'),
        __('account.wizard.fields.bedrooms'),
        __('account.wizard.fields.total_floors'),
        __('account.wizard.fields.listed_by'),
        __('account.wizard.fields.location_id'),
        __('account.wizard.fields.price'),
        __('account.wizard.fields.currency'),
        __('account.wizard.fields.contact_name'),
        __('account.wizard.fields.contact_phone'),
    ])
        ->and(summaryValue($wizard, __('account.wizard.fields.bedrooms')))->toBe('3')
        ->and(summaryValue($wizard, __('account.wizard.fields.deal')))->toBe(PropertyDeal::Rent->getLabel())
        ->and(summaryValue($wizard, __('account.wizard.fields.property_type')))->toBe(PropertyType::House->getLabel())
        ->and(summaryValue($wizard, __('account.wizard.fields.area_m2')))->toBe('120 m²')
        ->and(summaryValue($wizard, __('account.wizard.fields.land_area_m2')))->toBe('400 m²')
        ->and(summaryValue($wizard, __('account.wizard.fields.total_floors')))->toBe('2')
        ->and(summaryValue($wizard, __('account.wizard.fields.listed_by')))->toBe(ListedBy::Owner->getLabel())
        ->and(summaryValue($wizard, __('account.wizard.fields.location_id')))->toBe('ბულვარი')
        ->and(summaryValue($wizard, __('account.wizard.fields.contact_name')))->toBe('ნინო ბერიძე')
        ->and(summaryValue($wizard, __('account.wizard.fields.contact_phone')))->toBe('+995 599 12 34 56')
        ->and(summaryLabels($wizard))->not->toContain(__('account.wizard.fields.floor'));

    $wizard->assertSeeHtml('საძინებლები')->assertSeeHtml('3');
});

it('lists check-in, check-out and amenities for a guesthouse and hides hotel-only stars', function () {
    $breakfast = Amenity::factory()->create([
        'scope' => AmenityScope::Hotel,
        'name' => Kobuleti::same('საუზმე'),
        'slug' => 'breakfast',
    ]);

    $wizard = Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseVacation')
        ->set('form.vacation_type', VacationType::Guesthouse->value)
        ->call('next')
        ->assertHasNoErrors()
        ->set('form.title.ka', 'საოჯახო სახლი')
        ->set('form.description.ka', 'დეტალური აღწერა, რომელიც აკმაყოფილებს მინიმალურ სიგრძეს.')
        ->set('form.guests', '4')
        ->set('form.bedrooms', '2')
        ->set('form.distance_to_sea_m', '80')
        ->set('form.check_in', '15:00')
        ->set('form.check_out', '11:00')
        ->set('form.stars', '4')
        ->set('form.rooms_count', '6')
        ->set('form.features', [(string) $breakfast->id]);

    $wizard = listingReviewPreview($wizard, '150', 'https://stay.example');

    expect(summaryLabels($wizard))->toBe([
        __('account.wizard.fields.vacation_type'),
        __('account.wizard.fields.guests'),
        __('account.wizard.fields.bedrooms'),
        __('account.wizard.fields.distance_to_sea_m'),
        __('account.wizard.fields.check_in'),
        __('account.wizard.fields.check_out'),
        __('account.wizard.fields.rooms_count'),
        __('account.wizard.fields.features'),
        __('account.wizard.fields.location_id'),
        __('account.wizard.fields.price'),
        __('account.wizard.fields.currency'),
        __('account.wizard.fields.contact_name'),
        __('account.wizard.fields.contact_phone'),
        __('account.wizard.fields.website'),
    ])
        ->and(summaryValue($wizard, __('account.wizard.fields.vacation_type')))->toBe(VacationType::Guesthouse->getLabel())
        ->and(summaryValue($wizard, __('account.wizard.fields.guests')))->toBe('4')
        ->and(summaryValue($wizard, __('account.wizard.fields.distance_to_sea_m')))->toBe('80 m')
        ->and(summaryValue($wizard, __('account.wizard.fields.check_in')))->toBe('15:00')
        ->and(summaryValue($wizard, __('account.wizard.fields.check_out')))->toBe('11:00')
        ->and(summaryValue($wizard, __('account.wizard.fields.rooms_count')))->toBe('6')
        ->and(summaryValue($wizard, __('account.wizard.fields.features')))->toBe('საუზმე')
        ->and(summaryValue($wizard, __('account.wizard.fields.website')))->toBe('https://stay.example')
        ->and(summaryValue($wizard, __('account.wizard.fields.currency')))->toBe('GEL')
        ->and(summaryLabels($wizard))->not->toContain(__('account.wizard.fields.stars'))
        ->and(summaryLabels($wizard))->not->toContain(__('account.wizard.fields.deal'));

    $wizard->assertSeeHtml('შესვლის დრო')
        ->assertSeeHtml('15:00')
        ->assertSeeHtml('გასვლის დრო')
        ->assertSeeHtml('11:00')
        ->assertSeeHtml('საუზმე')
        ->assertDontSeeHtml(PropertyDeal::DailyRent->getLabel());
});

it('lists stars and room count for a hotel stay', function () {
    $wizard = Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseVacation')
        ->set('form.vacation_type', VacationType::Hotel->value)
        ->call('next')
        ->assertHasNoErrors()
        ->set('form.title.ka', 'სასტუმრო ზღვასთან')
        ->set('form.description.ka', 'დეტალური აღწერა, რომელიც აკმაყოფილებს მინიმალურ სიგრძეს.')
        ->set('form.guests', '2')
        ->set('form.stars', '4')
        ->set('form.rooms_count', '12');

    $wizard = listingReviewPreview($wizard, '200');

    expect(summaryValue($wizard, __('account.wizard.fields.stars')))->toBe('4')
        ->and(summaryValue($wizard, __('account.wizard.fields.rooms_count')))->toBe('12')
        ->and(summaryValue($wizard, __('account.wizard.fields.vacation_type')))->toBe(VacationType::Hotel->getLabel())
        ->and(summaryValue($wizard, __('account.wizard.fields.check_in')))->toBe('14:00')
        ->and(summaryValue($wizard, __('account.wizard.fields.check_out')))->toBe('12:00');
});

it('lists land purpose and utilities and leaves out house fields', function () {
    $wizard = Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseType', 'property')
        ->set('form.deal', PropertyDeal::Sale->value)
        ->set('form.property_type', PropertyType::Land->value)
        ->set('form.purpose', LandPurpose::Agricultural->value)
        ->set('form.utilities', [LandUtility::Electricity->value, LandUtility::Water->value])
        ->call('next')
        ->assertHasNoErrors()
        ->set('form.title.ka', 'ნაკვეთი ბულვართან')
        ->set('form.description.ka', 'დეტალური აღწერა, რომელიც აკმაყოფილებს მინიმალურ სიგრძეს.')
        ->set('form.land_area_m2', '500')
        ->set('form.bedrooms', '4');

    $wizard = listingReviewPreview($wizard);

    expect(summaryLabels($wizard))->toBe([
        __('account.wizard.fields.deal'),
        __('account.wizard.fields.property_type'),
        __('account.wizard.fields.land_area_m2'),
        __('account.wizard.fields.purpose'),
        __('account.wizard.fields.utilities'),
        __('account.wizard.fields.location_id'),
        __('account.wizard.fields.price'),
        __('account.wizard.fields.currency'),
        __('account.wizard.fields.contact_name'),
        __('account.wizard.fields.contact_phone'),
    ])
        ->and(summaryValue($wizard, __('account.wizard.fields.purpose')))->toBe(LandPurpose::Agricultural->getLabel())
        ->and(summaryValue($wizard, __('account.wizard.fields.utilities')))->toBe(LandUtility::Water->getLabel().', '.LandUtility::Electricity->getLabel())
        ->and(summaryValue($wizard, __('account.wizard.fields.land_area_m2')))->toBe('500 m²')
        ->and(summaryLabels($wizard))->not->toContain(__('account.wizard.fields.bedrooms'));
});

it('lists the commercial subtype on the review step', function () {
    $wizard = Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseType', 'property')
        ->set('form.deal', PropertyDeal::Sale->value)
        ->set('form.property_type', PropertyType::Commercial->value)
        ->set('form.commercial_subtype', CommercialSubtype::Other->value)
        ->call('next')
        ->assertHasNoErrors()
        ->set('form.title.ka', 'ფართი ცენტრში')
        ->set('form.description.ka', 'დეტალური აღწერა, რომელიც აკმაყოფილებს მინიმალურ სიგრძეს.')
        ->set('form.area_m2', '90')
        ->set('form.floor', '1')
        ->set('form.total_floors', '4')
        ->set('form.bedrooms', '2')
        ->set('form.rooms', '3');

    $wizard = listingReviewPreview($wizard);

    expect(summaryValue($wizard, __('account.wizard.fields.commercial_subtype')))->toBe('სხვა')
        ->and(summaryValue($wizard, __('account.wizard.fields.floor')))->toBe('1')
        ->and(summaryValue($wizard, __('account.wizard.fields.total_floors')))->toBe('4')
        ->and(summaryValue($wizard, __('account.wizard.fields.area_m2')))->toBe('90 m²')
        ->and(summaryLabels($wizard))->not->toContain(__('account.wizard.fields.bedrooms'))
        ->and(summaryLabels($wizard))->not->toContain(__('account.wizard.fields.rooms'));
});

function listingReviewPreview(Testable $wizard, string $price = '1000', ?string $website = null): Testable
{
    $wizard->call('next')
        ->assertHasNoErrors()
        ->set('form.location_id', (string) test()->location->id)
        ->call('setPin', 41.8193, 41.7769)
        ->call('next')
        ->assertHasNoErrors()
        ->set('upload', UploadedFile::fake()->image('photo.jpg', 1200, 900))
        ->call('next')
        ->assertHasNoErrors()
        ->set('form.price', $price);

    if ($website !== null) {
        $wizard->set('form.website', $website);
    }

    return $wizard->call('next')->assertHasNoErrors()->assertSet('step', 6);
}

/**
 * @return list<string>
 */
function summaryLabels(Testable $wizard): array
{
    return array_column($wizard->viewData('summary'), 'label');
}

function summaryValue(Testable $wizard, string $label): string
{
    $row = collect($wizard->viewData('summary'))->firstWhere('label', $label);

    expect($row)->toBeArray();

    return $row['value'];
}
