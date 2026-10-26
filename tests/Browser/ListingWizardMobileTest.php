<?php

use App\Models\ListingDraft;
use App\Models\Location;
use App\Models\Property;
use App\Models\User;
use Composer\InstalledVersions;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

/*
 * Real-browser checks of the add-listing wizard on a phone-sized screen (Pest 4 browser plugin).
 * Needs: composer require --dev pestphp/pest-plugin-browser, npm install -D playwright,
 * npx playwright install chromium. Skipped when the plugin is not installed.
 */

beforeEach(function () {
    if (! InstalledVersions::isInstalled('pestphp/pest-plugin-browser')) {
        $this->markTestSkipped('pestphp/pest-plugin-browser is not installed.');
    }

    $this->seed(RoleSeeder::class);
    Storage::fake('public');

    $parent = Location::factory()->create(['parent_id' => null]);
    $this->location = Location::factory()->create(['parent_id' => $parent->id, 'lat' => 41.8180, 'lng' => 41.7765, 'name' => ['ka' => 'ცენტრი', 'en' => 'Centre', 'ru' => 'Центр']]);
    $this->user = User::factory()->create(['phone' => '+995599123456', 'phone_verified_at' => now()]);
});

it('walks through the wizard on a phone and keeps the draft after a refresh', function () {
    $this->actingAs($this->user);

    $page = visit('/add')->on()->mobile();

    $page->assertSee(__('account.wizard.what'))
        ->click(__('account.wizard.modules.property'))
        ->click(__('enums.property_deal.sale'))
        ->click(__('enums.property_type.apartment'))
        ->click(__('account.wizard.next'))
        ->assertSee(__('account.wizard.fields.description'))
        ->fill('#wizard-title', 'ოროთახიანი ბინა ზღვასთან')
        ->fill('#wizard-description', 'ნათელი ბინა ახალ კორპუსში, ზღვიდან რამდენიმე ნაბიჯში.')
        ->fill('#wizard-area_m2', '64')
        ->click(__('account.wizard.next'))
        ->assertSee(__('account.wizard.location.pin'))
        ->assertNoJavaScriptErrors();

    $draft = ListingDraft::query()->sole();
    expect($draft->data['title']['ka'])->toBe('ოროთახიანი ბინა ზღვასთან');

    // A refresh (the URL carries ?draft=) comes back to the same step with the same data.
    visit('/add?draft='.$draft->id)->on()->mobile()
        ->assertSee(__('account.wizard.location.pin'))
        ->click(__('account.wizard.steps.details'))
        ->assertValue('#wizard-title', 'ოროთახიანი ბინა ზღვასთან');
});

it('shows the owner the moderation result in the account', function () {
    Property::factory()->for($this->user)->create(['status' => 'rejected', 'rejection_reason' => 'Photos are missing', 'title' => ['ka' => 'ბინა ცენტრში']]);

    $this->actingAs($this->user);

    visit('/account/listings?type=property')->on()->mobile()
        ->assertSee('ბინა ცენტრში')
        ->assertSee('Photos are missing')
        ->assertNoJavaScriptErrors();
});
