<?php

use App\Services\FrontendBundle;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Livewire\Component;
use Livewire\Livewire;

class FrontendBundleProbe extends Component
{
    public function render(): string
    {
        return '<div>frontend bundle probe</div>';
    }
}

beforeEach(function () {
    // A hot file makes @vite print entry names instead of hashed build files.
    $this->hotFile = tempnam(sys_get_temp_dir(), 'vite-hot');
    file_put_contents($this->hotFile, 'http://vite.test');
    Vite::useHotFile($this->hotFile);
});

afterEach(function () {
    @unlink($this->hotFile);
    Livewire::flushState();
});

it('serves the Alpine-only bundle on pages without Livewire components', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('http://vite.test/'.FrontendBundle::ALPINE, false)
        ->assertDontSee(FrontendBundle::LIVEWIRE, false)
        ->assertDontSee('livewireScriptConfig', false);
});

it('serves the Livewire bundle once a Livewire component renders', function () {
    Livewire::component('frontend-bundle-probe', FrontendBundleProbe::class);
    Route::get('/_test/livewire-page', fn () => Blade::render('<x-layout.app><livewire:frontend-bundle-probe /></x-layout.app>'));

    $this->get('/_test/livewire-page')
        ->assertOk()
        ->assertSee('frontend bundle probe')
        ->assertSee('http://vite.test/'.FrontendBundle::LIVEWIRE, false)
        ->assertDontSee(FrontendBundle::ALPINE, false)
        ->assertSee('livewireScriptConfig', false);
});
