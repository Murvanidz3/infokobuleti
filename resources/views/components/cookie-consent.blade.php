{{-- Cookie choice: necessary cookies only, or also analytics (Google Analytics loads only after "accept all"). --}}
@php($privacy = \App\Support\Listings\PublicCache::remember('privacy-page', fn (): bool => \App\Models\Page::query()->where('slug', 'privacy')->where('is_published', true)->exists()))
<div x-data="cookieConsent(@js(site_setting('seo.ga4_id')))" x-show="open" x-cloak x-transition.opacity
     class="fixed inset-x-3 bottom-3 z-50 mx-auto max-w-[640px] rounded-panel border border-line bg-white p-4 shadow-overlay md:p-5"
     role="dialog" aria-modal="false" aria-labelledby="cookie-title" aria-describedby="cookie-text">
    <p id="cookie-title" class="text-[15px] font-semibold text-navy">{{ __('privacy.cookies.title') }}</p>
    <p id="cookie-text" class="mt-1 text-[13.5px] leading-normal text-secondary">
        {{ __('privacy.cookies.text') }}
        @if ($privacy)
            <a href="{{ localized_route('pages.show', ['page' => 'privacy']) }}" class="font-medium text-navy underline underline-offset-2 hover:text-coral-pressed">{{ __('privacy.cookies.policy') }}</a>
        @endif
    </p>
    <div class="mt-3 flex flex-wrap gap-2">
        <x-button size="sm" x-on:click="choose('all')">{{ __('privacy.cookies.accept') }}</x-button>
        <x-button size="sm" variant="secondary" x-on:click="choose('necessary')">{{ __('privacy.cookies.necessary') }}</x-button>
    </div>
</div>
