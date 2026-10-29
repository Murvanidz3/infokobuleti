<?php

use App\Enums\Currency;
use App\Enums\ListingStatus;
use App\Enums\PropertyDeal;
use App\Enums\PropertyType;
use App\Models\ListingDraft;
use App\Models\Location;
use App\Models\Property;
use App\Models\User;
use App\Support\Listings\ListingForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * A saved property whose wizard form round-trips, plus the draft that edits it.
 *
 * @return array{0: Property, 1: ListingDraft, 2: array<string, mixed>}
 */
function editablePropertyDraft(User $user, ListingStatus $status = ListingStatus::Published): array
{
    $location = Location::factory()->create();
    $property = Property::factory()
        ->withStatus($status, $status === ListingStatus::Published
            ? ['published_at' => now()->subDay(), 'expires_at' => now()->addDays(30)]
            : ['published_at' => null, 'expires_at' => null])
        ->type(PropertyType::Apartment, PropertyDeal::Sale)
        ->for($user)
        ->create([
            'location_id' => $location->id,
            'price' => 50000,
            'currency' => Currency::USD,
        ]);

    $photo = wizardJpeg($property, 'gallery');
    $form = ListingForm::fromListing($property);
    $form['photos'] = [[
        'source' => 'listing',
        'id' => $photo->id,
        'url' => $photo->getUrl(),
    ]];

    $draft = ListingDraft::factory()->create([
        'user_id' => $user->id,
        'listing_type' => 'property',
        'listing_id' => $property->id,
        'step' => 6,
        'data' => $form,
    ]);

    return [$property, $draft, $form];
}

function wizardJpeg(HasMedia $model, string $collection): Media
{
    $path = tempnam(sys_get_temp_dir(), 'ik');
    $image = imagecreatetruecolor(8, 8);
    imagejpeg($image, $path, 90);

    return $model->addMedia($path)->usingFileName(basename($path).'.jpg')->toMediaCollection($collection);
}
