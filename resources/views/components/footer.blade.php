@php
    $home = localized_route('home');
    $socials = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'telegram' => 'Telegram'];
    $infoLinks = ['about', 'advertising', 'terms', 'privacy', 'help'];
@endphp

<footer class="border-t border-white/8 bg-navy pt-[clamp(56px,5vw,72px)] pb-8 text-line-strong">
    <div class="container-page">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,190px),1fr))] gap-x-8 gap-y-10">
            <div class="max-w-[300px]">
                <x-logo size="footer" />
                <p class="mt-4 text-sm leading-[1.7]">{{ __('footer.about') }}</p>
                <ul class="mt-5 flex gap-2.5" aria-label="{{ __('footer.social') }}">
                    @foreach ($socials as $network => $label)
                        <li>
                            <a href="{{ config('site.social.'.$network) }}" aria-label="{{ $label }}" class="grid size-10 place-items-center rounded-full border border-white/18 text-white transition-colors duration-200 hover:border-coral hover:bg-coral hover:text-white">
                                <x-icon :name="$network" :size="18" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <nav aria-labelledby="footer-portal">
                <h2 id="footer-portal" class="mb-4 text-[13px] font-semibold tracking-[0.02em] text-white">{{ __('footer.portal') }}</h2>
                <ul class="flex flex-col gap-[11px] text-sm">
                    @foreach (config('site.nav') as $key => $anchor)
                        <li><a href="{{ $home }}#{{ $anchor }}" class="text-line-strong transition-colors hover:text-white">{{ __('nav.'.$key) }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <nav aria-labelledby="footer-information">
                <h2 id="footer-information" class="mb-4 text-[13px] font-semibold tracking-[0.02em] text-white">{{ __('footer.information') }}</h2>
                <ul class="flex flex-col gap-[11px] text-sm">
                    @foreach ($infoLinks as $link)
                        <li><a href="#" class="text-line-strong transition-colors hover:text-white">{{ __('footer.links.'.$link) }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <div>
                <h2 class="mb-4 text-[13px] font-semibold tracking-[0.02em] text-white">{{ __('footer.contact') }}</h2>
                <address class="flex flex-col gap-3 text-sm not-italic">
                    <span class="flex items-start gap-2.5"><x-icon name="pin" :size="16" class="mt-[3px]" />{{ __('footer.address') }}</span>
                    <a href="tel:{{ str_replace(' ', '', config('site.contact.phone')) }}" class="flex items-center gap-2.5 text-line-strong transition-colors hover:text-white"><x-icon name="phone" :size="16" />{{ config('site.contact.phone') }}</a>
                    <a href="mailto:{{ config('site.contact.email') }}" class="flex items-center gap-2.5 text-line-strong transition-colors hover:text-white"><x-icon name="mail" :size="16" />{{ config('site.contact.email') }}</a>
                </address>
            </div>

            <div>
                <h2 class="mb-4 text-[13px] font-semibold tracking-[0.02em] text-white">{{ __('footer.app') }}</h2>
                <div class="flex flex-col items-start gap-2.5">
                    @foreach (['app_store' => 'App Store', 'google_play' => 'Google Play'] as $store => $label)
                        <a href="{{ config('site.apps.'.$store) }}" class="flex min-w-40 flex-col rounded-field border border-white/24 px-4 py-[9px] text-white transition-colors duration-200 hover:bg-white/8 hover:text-white">
                            <span class="text-[11px] text-line-strong">{{ __('footer.download') }}</span>
                            <span class="mt-px text-[15px] font-semibold">{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-14 flex flex-wrap justify-between gap-4 border-t border-white/10 pt-6 text-[13px]">
            <span>{{ __('footer.copyright', ['year' => now()->year]) }}</span>
            <span>{{ __('footer.made_in') }}</span>
        </div>
    </div>
</footer>
