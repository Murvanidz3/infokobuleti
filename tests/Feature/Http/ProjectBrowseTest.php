<?php

use App\Models\Project;
use App\Models\Property;
use App\Support\Listings\ListingForm;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

it('redirects old hotel urls to the hotel property type', function (string $from, string $to) {
    $this->get($from)->assertStatus(301)->assertRedirect($to);
})->with([
    'list' => ['/hotels', '/real-estate/hotel'],
    'slug' => ['/hotels/sea-breeze', '/real-estate/hotel'],
    'english' => ['/en/hotels', '/en/real-estate/hotel'],
    'russian' => ['/ru/hotels/any-slug', '/ru/real-estate/hotel'],
]);

it('lists construction projects without a property type filter', function () {
    $project = Project::factory()->create([
        'title' => ['ka' => 'საცხოვრებელი კომპლექსი მზიური ტესტი', 'en' => 'Mziuri test project', 'ru' => 'Тест Мзиури'],
        'developer' => 'მზიური დეველოპმენტი',
    ]);

    $this->get('/real-estate/projects')
        ->assertOk()
        ->assertSee(__('site.projects.title'))
        ->assertSee(__('nav.projects'))
        ->assertSee('საცხოვრებელი კომპლექსი მზიური ტესტი')
        ->assertDontSee(__('site.booking.title'));

    $this->get('/en/real-estate/projects')
        ->assertOk()
        ->assertSee('Mziuri test project');

    $this->get('/real-estate/projects/'.$project->slug)
        ->assertOk()
        ->assertSee('მზიური დეველოპმენტი')
        ->assertSee(__('site.owner.show_phone'))
        ->assertSee(__('site.projects.price_from_label'))
        ->assertDontSee(__('site.booking.submit'));
});

it('requires a developer, a starting price and a phone for a project', function () {
    $form = [
        'title' => ['ka' => 'საცხოვრებელი კომპლექსი ტესტი', 'en' => '', 'ru' => ''],
        'description' => ['ka' => 'აღწერა რომელიც ოც სიმბოლოზე მეტია აუცილებლად აქ.', 'en' => '', 'ru' => ''],
        'developer' => 'აჭარა ბილდი',
        'completion_on' => now()->addYear()->toDateString(),
        'price_per_m2' => '950',
        'currency' => 'USD',
        'contact_name' => '',
        'contact_phone' => '+995555112233',
    ];

    $passes = fn (array $overrides, string $step): bool => Validator::make(
        ['form' => array_replace($form, $overrides)],
        ListingForm::rules('project', $step, array_replace($form, $overrides)),
    )->passes();

    expect($passes([], 'details'))->toBeTrue()
        ->and($passes(['developer' => ''], 'details'))->toBeFalse()
        ->and($passes([], 'price'))->toBeTrue()
        ->and($passes(['price_per_m2' => ''], 'price'))->toBeFalse()
        ->and($passes(['contact_phone' => ''], 'price'))->toBeFalse();
});

it('keeps a projects slug from shadowing the projects index', function () {
    $property = Property::factory()->create([
        'title' => ['ka' => 'projects', 'en' => 'projects', 'ru' => 'projects'],
    ]);

    expect($property->slug)->not->toBe('projects')
        ->and(Schema::hasColumn('properties', 'nearby'))->toBeFalse();

    $this->get('/real-estate/projects')->assertOk();
});
