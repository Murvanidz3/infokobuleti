<?php

use App\Enums\Role;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Classified;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Property;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    config(['demo.password' => 'test-password']);
    $this->seed(DatabaseSeeder::class);
    [$this->admin] = staffMember(Role::SuperAdmin);
});

dataset('listing resources', [
    'hotels' => ['hotels', Hotel::class],
    'properties' => ['properties', Property::class],
    'classifieds' => ['classifieds', Classified::class],
    'vacancies' => ['vacancies', Vacancy::class],
    'places' => ['places', Place::class],
    'articles' => ['articles', Article::class],
    'events' => ['events', Event::class],
]);

it('renders the list, create, view and edit pages of every listing module', function (string $slug, string $model) {
    $record = $model::query()->firstOrFail();

    $this->withoutExceptionHandling()->actingAs($this->admin);

    $this->get("/admin/{$slug}")->assertOk();
    $this->get("/admin/{$slug}/create")->assertOk();
    $this->get("/admin/{$slug}/{$record->getRouteKey()}")->assertOk();
    $this->get("/admin/{$slug}/{$record->getRouteKey()}/edit")->assertOk();
})->with('listing resources');

it('renders every other admin page', function (string $url) {
    $this->withoutExceptionHandling()->actingAs($this->admin)->get($url)->assertOk();
})->with([
    '/admin',
    '/admin/moderation',
    '/admin/settings',
    '/admin/homepage',
    '/admin/users',
    '/admin/users/create',
    '/admin/business-profiles',
    '/admin/business-profiles/create',
    '/admin/categories',
    '/admin/categories/create',
    '/admin/locations',
    '/admin/amenities',
    '/admin/reviews',
    '/admin/reports',
    '/admin/conversations',
    '/admin/promotions',
    '/admin/payments',
    '/admin/price-plans',
    '/admin/pages',
    '/admin/banners',
    '/admin/activities',
]);

it('renders record pages of users, businesses, categories and conversations', function () {
    $this->withoutExceptionHandling()->actingAs($this->admin);

    $user = User::query()->whereKeyNot($this->admin->id)->firstOrFail();
    $business = BusinessProfile::query()->firstOrFail();
    $category = Category::query()->whereNotNull('attribute_schema')->firstOrFail();
    $conversation = Conversation::query()->firstOrFail();

    $this->get("/admin/users/{$user->id}")->assertOk();
    $this->get("/admin/users/{$user->id}/edit")->assertOk();
    $this->get("/admin/business-profiles/{$business->getRouteKey()}")->assertOk();
    $this->get("/admin/business-profiles/{$business->getRouteKey()}/edit")->assertOk();
    $this->get("/admin/categories/{$category->getRouteKey()}/edit")->assertOk();
    $this->get("/admin/conversations/{$conversation->id}")->assertOk();
});
