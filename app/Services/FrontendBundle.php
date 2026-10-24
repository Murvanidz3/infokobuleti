<?php

namespace App\Services;

use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;
use Livewire\LivewireManager;

/**
 * Chooses the JavaScript entry for the current page. Pages that render Livewire components get the
 * Livewire bundle; every other page gets plain Alpine, which is far cheaper to start on mobile.
 *
 * Ask only after the page content has rendered (the layout does so at the end of <body>).
 */
class FrontendBundle
{
    public const ALPINE = 'resources/js/app.js';

    public const LIVEWIRE = 'resources/js/livewire.js';

    public function __construct(private LivewireManager $livewire) {}

    public function usesLivewire(): bool
    {
        return $this->livewire->componentHasBeenRendered() || SupportAutoInjectedAssets::$forceAssetInjection;
    }

    public function script(): string
    {
        return $this->usesLivewire() ? self::LIVEWIRE : self::ALPINE;
    }
}
