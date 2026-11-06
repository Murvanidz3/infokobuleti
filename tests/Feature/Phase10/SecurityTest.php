<?php

use App\Enums\Role;
use App\Http\Controllers\Site\RobotsController;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

it('sends the security headers with a nonce that matches the inline scripts', function () {
    $response = $this->get('/')->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    $policy = (string) $response->headers->get('Content-Security-Policy');
    preg_match("/'nonce-([^']+)'/", $policy, $nonce);

    expect($policy)->toContain("default-src 'self'")->toContain("object-src 'none'")->toContain("frame-ancestors 'self'")
        ->and($nonce)->toHaveCount(2);

    preg_match_all('/<script(?![^>]*type="application\/ld\+json")[^>]*>/', (string) $response->getContent(), $scripts);

    foreach ($scripts[0] as $tag) {
        expect(str_contains($tag, ' src="') || str_contains($tag, 'nonce="'.$nonce[1].'"'))->toBeTrue("Inline script without nonce: {$tag}");
    }

    expect($response->getContent())->not->toMatch('/\son(click|load|error|submit)="/');
});

it('adds HSTS on HTTPS only', function () {
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('rate-limits every endpoint that changes something', function () {
    $unlimited = collect(Route::getRoutes()->getRoutes())
        ->reject(fn (RoutingRoute $route): bool => $route->methods() === ['GET', 'HEAD'])
        ->reject(fn (RoutingRoute $route): bool => str_starts_with($route->uri(), 'admin') || str_starts_with($route->uri(), 'horizon') || str_starts_with($route->uri(), '_') || str_starts_with($route->uri(), 'storage/'))
        ->reject(fn (RoutingRoute $route): bool => collect($route->gatherMiddleware())->contains(fn ($middleware): bool => is_string($middleware) && str_starts_with($middleware, 'throttle')))
        ->reject(fn (RoutingRoute $route): bool => $route->getName() === 'default-livewire.update')
        ->map(fn (RoutingRoute $route): string => implode('|', $route->methods()).' '.$route->uri())
        ->values()
        ->all();

    expect($unlimited)->toBe([]);
});

it('sends guests to sign in from every account page', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => in_array('GET', $route->methods(), true)
            && in_array('auth', $route->gatherMiddleware(), true)
            && $route->parameterNames() === []
            && ! str_starts_with($route->uri(), 'admin'));

    expect($routes->count())->toBeGreaterThan(20);

    foreach ($routes as $route) {
        $this->get('/'.ltrim($route->uri(), '/'))->assertRedirect();
        expect(auth()->check())->toBeFalse();
    }
});

it('keeps the admin closed to ordinary users', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create();

    foreach (['/admin', '/admin/redirects', '/admin/settings', '/admin/features', '/admin/users'] as $url) {
        expect($this->actingAs($user)->get($url)->status())->toBeIn([403, 404]);
    }
});

it('logs failed staff sign-ins for fail2ban', function () {
    staffMember(Role::Admin);
    $channel = Mockery::mock();
    $channel->shouldReceive('warning')->once()->with(Mockery::pattern('/^auth-failed staff-password ip=127\.0\.0\.1$/'));
    Log::shouldReceive('channel')->with('auth')->andReturn($channel);
    Log::shouldReceive('channel')->with(Mockery::not('auth'))->andReturn(Log::getFacadeRoot());

    $this->post(localized_route('staff.login.store'), ['email' => 'staff@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
});

it('lists the private areas in robots.txt', function () {
    expect(RobotsController::PRIVATE_PATHS)->toContain('/account', '/admin', '/livewire');
});

it('protects the admin panel with the same headers, without the CSP', function () {
    [$admin] = staffMember(Role::SuperAdmin);

    $this->actingAs($admin)->get('/admin/redirects')->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeaderMissing('Content-Security-Policy');
});
