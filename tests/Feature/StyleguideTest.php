<?php

it('renders the styleguide outside production', function () {
    $this->get('/_styleguide')
        ->assertOk()
        ->assertSee(__('styleguide.title'))
        ->assertSee('noindex, nofollow', false);
});

it('renders the styleguide in every locale', function (string $url) {
    $this->get($url)->assertOk();
})->with(['/_styleguide', '/en/_styleguide', '/ru/_styleguide']);

it('hides the styleguide in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('/_styleguide')->assertNotFound();
});
