<?php

use App\Enums\CategoryModule;
use App\Enums\ListingStatus;
use App\Livewire\ListingWizard;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\Classified;
use App\Models\Hotel;
use App\Models\ListingDraft;
use App\Models\Location;
use App\Models\Property;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Settings;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
    $this->user = User::factory()->create(['phone' => '+995599123456', 'phone_verified_at' => now()]);
    $parent = Location::factory()->create(['parent_id' => null]);
    $this->location = Location::factory()->create(['parent_id' => $parent->id, 'lat' => 41.8180, 'lng' => 41.7765]);
});

function jpeg(string $name = 'photo.jpg'): UploadedFile
{
    return UploadedFile::fake()->image($name, 1200, 900);
}

/**
 * Fills a property listing up to the preview step.
 */
function propertyWizard(User $user, Location $location): Testable
{
    return Livewire::actingAs($user)->test(ListingWizard::class)
        ->call('chooseType', 'property')
        ->set('form.deal', 'sale')
        ->set('form.property_type', 'apartment')
        ->call('next')
        ->set('form.title.ka', 'ოროთახიანი ბინა ზღვასთან')
        ->set('form.description.ka', 'ნათელი ბინა ახალ კორპუსში, ზღვიდან რამდენიმე ნაბიჯში.')
        ->set('form.area_m2', '64')
        ->set('form.rooms', '2')
        ->call('next')
        ->set('form.location_id', (string) $location->id)
        ->call('setPin', 41.8193, 41.7769)
        ->call('next')
        ->set('upload', jpeg())
        ->set('upload', jpeg('second.jpg'))
        ->call('next')
        ->set('form.price', '72000')
        ->set('form.currency', 'USD')
        ->call('next');
}

it('needs a verified phone to open the wizard', function () {
    $this->get('/add')->assertRedirect('/login');

    $unverified = User::factory()->create(['phone' => null, 'phone_verified_at' => null]);
    $this->actingAs($unverified)->get('/add')->assertRedirect();

    $this->actingAs($this->user)->get('/add')->assertOk()->assertSeeLivewire(ListingWizard::class);
});

it('creates a property listing through all steps and sends it to moderation', function () {
    $wizard = propertyWizard($this->user, $this->location)->assertSet('step', 6)->assertHasNoErrors();

    $wizard->call('submit')->assertRedirect(localized_route('account.listings', ['type' => 'property', 'group' => 'pending']));

    $property = Property::query()->where('user_id', $this->user->id)->sole();

    expect($property)
        ->status->toBe(ListingStatus::Pending)
        ->deal->value->toBe('sale')
        ->area_m2->toBe(64.0)
        ->price->toBe(72000.0)
        ->currency->value->toBe('USD')
        ->contact_phone->toBe('+995599123456')
        ->location_id->toBe($this->location->id)
        ->and($property->distance_to_sea_m)->toBeGreaterThan(0)->toBeLessThan(1500)
        ->and($property->getTranslation('title', 'ka'))->toBe('ოროთახიანი ბინა ზღვასთან')
        ->and($property->getMedia('gallery'))->toHaveCount(2)
        ->and(ListingDraft::query()->count())->toBe(0);
});

it('validates each step before moving on', function () {
    Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('next')
        ->assertHasErrors('type')
        ->call('chooseType', 'property')
        ->call('next')
        ->assertHasErrors(['form.deal', 'form.property_type'])
        ->assertSet('step', 1)
        ->set('form.deal', 'rent')
        ->set('form.property_type', 'land')
        ->call('next')
        ->assertSet('step', 2)
        ->set('form.title.ka', 'ab')
        ->call('next')
        ->assertHasErrors(['form.title.ka', 'form.description.ka', 'form.land_area_m2'])
        ->assertHasNoErrors('form.area_m2')
        ->assertSet('step', 2);
});

it('autosaves a draft that survives a page refresh', function () {
    $wizard = Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseType', 'classified')
        ->set('form.title.ka', 'ველოსიპედი');

    $draft = ListingDraft::query()->sole();
    expect($draft->listing_type)->toBe('classified')
        ->and($draft->data['title']['ka'])->toBe('ველოსიპედი');

    Livewire::withQueryParams(['draft' => $draft->id])->actingAs($this->user)->test(ListingWizard::class)
        ->assertSet('type', 'classified')
        ->assertSet('form.title.ka', 'ველოსიპედი');

    // Someone else's draft id is ignored.
    Livewire::withQueryParams(['draft' => $draft->id])->actingAs(User::factory()->create())->test(ListingWizard::class)
        ->assertSet('type', '')
        ->assertSet('draftId', 0);
});

it('stores photos on the draft, strips metadata, reorders and removes them', function () {
    $wizard = Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseType', 'property')
        ->set('upload', jpeg('a.jpg'))
        ->set('upload', jpeg('b.jpg'))
        ->set('upload', jpeg('c.jpg'));

    $photos = $wizard->get('form.photos');
    expect($photos)->toHaveCount(3)->and($photos[0]['source'])->toBe('draft');

    $wizard->call('movePhoto', 2, 0);
    expect($wizard->get('form.photos')[0]['id'])->toBe($photos[2]['id']);

    $wizard->call('removePhoto', 1);
    expect($wizard->get('form.photos'))->toHaveCount(2)
        ->and(ListingDraft::query()->sole()->getMedia('photos'))->toHaveCount(2);

    $wizard->set('upload', UploadedFile::fake()->create('document.pdf', 20, 'application/pdf'))->assertHasErrors('upload');
});

it('searches addresses through the geocoder and sets the pin and area', function () {
    Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseType', 'hotel')
        ->set('addressQuery', 'Rustaveli')
        ->call('searchAddress')
        ->assertCount('addressResults', 1)
        ->call('pickAddress', 0)
        ->assertSet('form.lat', 41.8193)
        ->assertSet('form.location_id', (string) $this->location->id)
        ->assertSet('form.address', 'Rustaveli St 120, Kobuleti')
        ->assertDispatched('pin-moved')
        ->call('setPin', 40.0, 44.0)
        ->assertHasErrors('form.lat');
});

it('creates a classified with its category attributes', function () {
    $parent = Category::factory()->create(['module' => CategoryModule::Classifieds, 'parent_id' => null]);
    $cars = Category::factory()->create(['module' => CategoryModule::Classifieds, 'parent_id' => $parent->id, 'attribute_schema' => [
        ['key' => 'make', 'type' => 'select', 'label' => ['ka' => 'მარკა'], 'required' => true, 'options' => [['value' => 'toyota', 'label' => ['ka' => 'Toyota']]]],
        ['key' => 'year', 'type' => 'number', 'label' => ['ka' => 'წელი'], 'min' => 1970, 'max' => 2027],
    ]]);

    Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseType', 'classified')
        ->set('form.parent_category_id', (string) $parent->id)
        ->call('next')
        ->assertHasErrors('form.category_id')
        ->set('form.category_id', (string) $cars->id)
        ->call('next')
        ->set('form.title.ka', 'Toyota Prius 2015')
        ->set('form.description.ka', 'კარგ მდგომარეობაში, ერთი მფლობელი, სერვისის ისტორიით.')
        ->set('form.attribute_values.year', '1950')
        ->call('next')
        ->assertHasErrors(['form.attribute_values.make', 'form.attribute_values.year'])
        ->set('form.attribute_values.make', 'toyota')
        ->set('form.attribute_values.year', '2015')
        ->call('next')
        ->set('form.location_id', (string) $this->location->id)
        ->call('next')
        ->call('next')
        ->set('form.price', '')
        ->call('next')
        ->call('submit')
        ->assertHasNoErrors();

    $classified = Classified::query()->sole();
    expect($classified->attribute_values)->toBe(['make' => 'toyota', 'year' => 2015])
        ->and($classified->price)->toBeNull()
        ->and($classified->category_id)->toBe($cars->id);
});

it('creates a hotel with its first room type', function () {
    $wifi = Amenity::factory()->create(['scope' => 'hotel']);

    Livewire::actingAs($this->user)->test(ListingWizard::class)
        ->call('chooseType', 'hotel')
        ->set('form.hotel_type', 'guesthouse')
        ->call('next')
        ->set('form.title.ka', 'სასტუმრო ზღვის ნიავი')
        ->set('form.description.ka', 'ოჯახური სასტუმრო ბულვართან ახლოს, საუზმით.')
        ->set('form.amenities', [(string) $wifi->id])
        ->call('next')
        ->set('form.location_id', (string) $this->location->id)
        ->call('setPin', 41.8180, 41.7722)
        ->call('next')
        ->set('upload', jpeg())
        ->call('next')
        ->set('form.room.name', 'ორადგილიანი ნომერი')
        ->set('form.room.base_price', '120')
        ->call('next')
        ->call('submit')
        ->assertHasNoErrors();

    $hotel = Hotel::query()->with('roomTypes', 'amenities')->sole();
    expect($hotel->status)->toBe(ListingStatus::Pending)
        ->and($hotel->roomTypes)->toHaveCount(1)
        ->and($hotel->fresh()->price_from)->toBe(120.0)
        ->and($hotel->amenities->pluck('id')->all())->toBe([$wifi->id]);
});

it('creates a vacancy linked to the business profile', function () {
    $business = User::factory()->business()->create(['phone' => '+995599000111', 'phone_verified_at' => now()]);
    $profile = $business->businessProfiles()->create(['type' => 'employer', 'name' => 'Sea Cafe']);
    $category = Category::factory()->create(['module' => CategoryModule::Vacancies, 'parent_id' => null]);

    Livewire::actingAs($business)->test(ListingWizard::class)
        ->call('chooseType', 'vacancy')
        ->set('form.category_id', (string) $category->id)
        ->set('form.employment_type', 'seasonal')
        ->call('next')
        ->set('form.title.ka', 'მიმტანი სეზონზე')
        ->set('form.description.ka', 'ვეძებთ მიმტანს ზაფხულის სეზონისთვის, სამუშაო გრაფიკი 2/2.')
        ->call('next')
        ->set('form.location_id', (string) $this->location->id)
        ->call('next')
        ->call('next')
        ->set('form.salary_min', '1500')
        ->set('form.salary_max', '1000')
        ->call('next')
        ->assertHasErrors('form.salary_max')
        ->set('form.salary_max', '2000')
        ->set('form.apply_method', 'email')
        ->set('form.apply_email', '')
        ->call('next')
        ->assertHasErrors('form.apply_email')
        ->set('form.apply_email', 'jobs@seacafe.ge')
        ->call('next')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Vacancy::query()->sole())
        ->business_profile_id->toBe($profile->id)
        ->salary_min->toBe(1500)
        ->apply_email->toBe('jobs@seacafe.ge');
});

it('edits a published listing without touching it until submit', function () {
    $property = Property::factory()->published()->for($this->user)->create(['title' => ['ka' => 'ძველი სათაური', 'en' => 'Old title'], 'location_id' => $this->location->id, 'lat' => 41.82, 'lng' => 41.78]);
    $property->addMedia(jpeg())->toMediaCollection('gallery');

    $this->actingAs($this->user)->get("/account/listings/property/{$property->id}/edit")->assertOk();

    $wizard = Livewire::actingAs($this->user)->test(ListingWizard::class, ['editType' => 'property', 'editId' => $property->id])
        ->assertSet('step', 2)
        ->assertSet('form.title.ka', 'ძველი სათაური')
        ->set('form.title.ka', 'ახალი სათაური ზღვის ხედით');

    expect($property->fresh()->getTranslation('title', 'ka'))->toBe('ძველი სათაური')
        ->and($wizard->get('form.photos'))->toHaveCount(1);

    $wizard->call('goTo', 6)->call('submit')->assertHasNoErrors();

    expect($property->fresh())
        ->status->toBe(ListingStatus::Pending)
        ->and($property->fresh()->getTranslation('title', 'ka'))->toBe('ახალი სათაური ზღვის ხედით')
        ->and($property->fresh()->getTranslation('title', 'en'))->toBe('Old title')
        ->and($property->fresh()->getMedia('gallery'))->toHaveCount(1);
});

it('does not let anyone edit another owner’s listing', function () {
    $property = Property::factory()->published()->create();

    $this->actingAs($this->user)->get("/account/listings/property/{$property->id}/edit")->assertNotFound();
});

it('enforces the monthly free listing limit', function () {
    app(Settings::class)->put(['listings.free_limit.individual' => 1]);
    Property::factory()->for($this->user)->create();

    propertyWizard($this->user, $this->location)
        ->assertSee(__('account.wizard.steps.preview'))
        ->call('submit')
        ->assertHasErrors('limit');

    expect(Property::query()->where('user_id', $this->user->id)->count())->toBe(1);
});
