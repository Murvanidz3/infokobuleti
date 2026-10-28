@php
    $input = 'block w-full rounded-field border border-line bg-white px-4 text-[15px] text-navy hover:border-line-strong focus:border-navy';
@endphp

<x-layout.app :title="__('site.contact.title')" :description="__('site.contact.subtitle')">
    <x-page-header :title="__('site.contact.title')" :subtitle="__('site.contact.subtitle')"
                   :breadcrumbs="[['label' => __('nav.home'), 'url' => localized_route('home')], ['label' => __('site.contact.title')]]" />

    <div class="container-page grid gap-10 py-10 pb-[clamp(56px,6vw,96px)] lg:grid-cols-[minmax(0,1fr)_340px]">
        <div>
            @if (session('contact_sent'))
                <div class="flex flex-col items-center gap-3 rounded-card border border-line bg-white px-6 py-14 text-center" role="status">
                    <span class="grid size-16 place-items-center rounded-full bg-coral-tint text-coral-pressed"><x-icon name="check" :size="30" /></span>
                    <h2 class="font-serif text-[22px] font-semibold text-navy">{{ __('site.contact.sent_title') }}</h2>
                    <p class="max-w-[420px] text-[15px] text-secondary">{{ __('site.contact.sent_text') }}</p>
                </div>
            @else
                <form method="post" action="{{ localized_route('contact.store') }}" class="flex flex-col gap-5 rounded-card border border-line bg-white p-5 xs:p-7" novalidate>
                    @csrf
                    <x-form.field :label="__('site.contact.fields.topic')" for="topic" name="topic">
                        <x-form.select name="topic" :options="$topics" :selected="$topic" :placeholder="false" />
                    </x-form.field>
                    <div class="grid gap-5 xs:grid-cols-2">
                        <x-form.field :label="__('site.contact.fields.name')" for="name" name="name">
                            <x-form.input name="name" :value="$user?->name" autocomplete="name" required maxlength="120" />
                        </x-form.field>
                        <x-form.field :label="__('site.contact.fields.email')" for="email" name="email" :hint="__('site.contact.email_or_phone')">
                            <x-form.input name="email" type="email" :value="$user?->email" autocomplete="email" maxlength="190" />
                        </x-form.field>
                    </div>
                    <x-form.field :label="__('site.contact.fields.phone')" for="phone" name="phone" optional>
                        <x-form.input name="phone" type="tel" :value="$user?->phone ? \App\Support\GeorgianPhone::format($user->phone) : null" autocomplete="tel" />
                    </x-form.field>
                    <x-form.field :label="__('site.contact.fields.message')" for="message" name="message">
                        <textarea id="message" name="message" rows="6" required minlength="10" maxlength="5000" @error('message') aria-invalid="true" @enderror class="{{ $input }} py-3">{{ old('message') }}</textarea>
                    </x-form.field>
                    <x-turnstile />
                    <div><x-button type="submit" size="lg" icon="send">{{ __('site.contact.submit') }}</x-button></div>
                </form>
            @endif
        </div>

        <aside class="flex flex-col gap-4">
            @php($phone = site_setting('contacts.phone', config('site.contact.phone')))
            @php($email = site_setting('contacts.email', config('site.contact.email')))
            <div class="flex flex-col gap-4 rounded-card border border-line bg-surface-alt p-6">
                <a href="tel:{{ str_replace(' ', '', (string) $phone) }}" class="flex items-center gap-3 text-[15px] font-semibold text-navy hover:text-coral-pressed"><x-icon name="phone" :size="20" class="text-coral" />{{ $phone }}</a>
                <a href="mailto:{{ $email }}" class="flex items-center gap-3 text-[15px] font-semibold text-navy hover:text-coral-pressed"><x-icon name="mail" :size="20" class="text-coral" />{{ $email }}</a>
                <p class="flex items-start gap-3 text-[15px] text-navy"><x-icon name="pin" :size="20" class="mt-0.5 shrink-0 text-coral" />{{ site_setting('contacts.address') ?: __('footer.address') }}</p>
            </div>
            <p class="text-[13.5px] leading-[1.6] text-secondary">{{ __('site.contact.response_time') }}</p>
        </aside>
    </div>
</x-layout.app>
