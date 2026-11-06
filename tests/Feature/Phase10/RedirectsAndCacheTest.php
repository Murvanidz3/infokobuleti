<?php

use App\Enums\Role;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Models\Hotel;
use App\Models\Redirect;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('redirects old addresses kept in the admin, with the query string', function () {
    Redirect::query()->create(['from_path' => '/Old-Hotels/', 'to_url' => '/hotels', 'status_code' => 301]);
    Redirect::query()->create(['from_path' => 'https://old.example.com/promo', 'to_url' => 'https://infokobuleti.com/real-estate', 'status_code' => 302]);

    $this->get('/old-hotels?page=2')->assertStatus(301)->assertRedirect(url('/hotels').'?page=2');
    $this->get('/promo')->assertStatus(302)->assertRedirect('https://infokobuleti.com/real-estate');
    $this->post('/old-hotels')->assertStatus(404);

    expect(Redirect::query()->where('from_path', '/old-hotels')->value('hits'))->toBe(1);
});

it('lets super admins manage redirects and refuses duplicates', function () {
    [$admin] = staffMember(Role::SuperAdmin);
    Redirect::query()->create(['from_path' => '/taken', 'to_url' => '/hotels']);

    $this->actingAs($admin);

    Livewire::test(ManageRedirects::class)
        ->callAction('create', data: ['from_path' => '/old-page', 'to_url' => '/news', 'status_code' => 301])
        ->assertHasNoActionErrors();

    Livewire::test(ManageRedirects::class)
        ->callAction('create', data: ['from_path' => '/Taken/', 'to_url' => '/news', 'status_code' => 301])
        ->assertHasActionErrors(['from_path']);

    Livewire::test(ManageRedirects::class)
        ->callAction('create', data: ['from_path' => '/admin/users', 'to_url' => '/news', 'status_code' => 301])
        ->assertHasActionErrors(['from_path']);

    expect(Redirect::query()->where('from_path', '/old-page')->value('created_by'))->toBe($admin->id);
});

describe('full-page cache', function () {
    beforeEach(fn () => config(['seo.page_cache.enabled' => true]));

    it('serves guests from the cache with their own CSRF token and nonce', function () {
        $hotel = Hotel::factory()->published()->create();
        $url = localized_route('hotels.show', [$hotel]);

        $this->get($url)->assertOk()->assertHeader('X-Page-Cache', 'MISS');

        $this->flushSession();
        $second = $this->get($url)->assertOk()->assertHeader('X-Page-Cache', 'HIT');
        $html = (string) $second->getContent();

        preg_match('/name="csrf-token" content="([^"]+)"/', $html, $token);
        preg_match("/'nonce-([^']+)'/", (string) $second->headers->get('Content-Security-Policy'), $nonce);

        expect($token[1])->toBe(session()->token())
            ->and($html)->toContain('nonce="'.$nonce[1].'"')
            ->not->toContain('__IK_');
    });

    it('counts the views of cached detail pages once per visitor', function () {
        $hotel = Hotel::factory()->published()->create();
        $url = localized_route('hotels.show', [$hotel]);

        $this->get($url);
        $this->get($url);
        $this->flushSession();
        $this->get($url)->assertHeader('X-Page-Cache', 'HIT');

        expect((int) DB::table('daily_stats')->where('statable_type', 'hotel')->where('statable_id', $hotel->id)->sum('views'))->toBe(2);
    });

    it('shows changes at once and never caches signed-in pages', function () {
        $hotel = Hotel::factory()->published()->create();
        $url = localized_route('hotels.show', [$hotel]);
        $this->get($url)->assertHeader('X-Page-Cache', 'MISS');

        $hotel->update(['title' => ['ka' => 'ახალი სახელი']]);
        $this->get($url)->assertHeader('X-Page-Cache', 'MISS')->assertSee('ახალი სახელი');

        $this->actingAs(User::factory()->create())->get($url)->assertOk()->assertHeaderMissing('X-Page-Cache');
    });
});
