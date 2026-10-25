@php
    $input = 'block w-full rounded-field border border-line bg-white px-4 text-[15px] text-navy hover:border-line-strong focus:border-navy';
    $applyErrors = $errors->apply;
    $user = auth()->user();
@endphp

<x-layout.app :title="$vacancy->title" :description="$description">
    @if ($preview)
        <x-detail.preview-banner :status="$vacancy->status->getLabel()" />
    @endif

    <div class="container-page pt-5 pb-[clamp(56px,6vw,96px)]">
        <x-breadcrumbs :items="[
            ['label' => __('nav.home'), 'url' => localized_route('home')],
            ['label' => __('nav.jobs'), 'url' => localized_route('jobs.index')],
            ['label' => $vacancy->title],
        ]" />

        <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1fr)_380px]">
            <div class="flex min-w-0 flex-col gap-8">
                <div>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <x-badge :variant="$vacancy->employment_type->value === 'seasonal' ? 'outline' : 'outline-tint'">{{ $vacancy->employment_type->getLabel() }}</x-badge>
                        @if ($vacancy->category)
                            <x-badge variant="tint">{{ $vacancy->category->name }}</x-badge>
                        @endif
                    </div>
                    <h1 class="font-serif text-[clamp(26px,2.8vw,38px)] leading-[1.22] font-semibold text-balance text-navy">{{ $vacancy->title }}</h1>
                    <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[14.5px] text-secondary">
                        <span class="flex items-center gap-1.5"><x-icon name="building" :size="16" class="text-navy" />{{ $owner['name'] }}</span>
                        <span class="flex items-center gap-1.5"><x-icon name="pin" :size="16" class="text-navy" />{{ $address }}</span>
                    </p>
                    <div class="mt-4"><x-detail.actions type="vacancy" :id="$vacancy->id" :title="$vacancy->title" :saved="$saved" /></div>
                </div>

                <x-detail.facts :facts="$facts" />

                @if (filled($vacancy->description))
                    <x-detail.section :title="__('site.jobs.about')">
                        <x-detail.text :text="$vacancy->description" />
                    </x-detail.section>
                @endif

                @if ($benefits)
                    <x-detail.section :title="__('site.jobs.benefits')">
                        <ul class="flex flex-wrap gap-2.5">
                            @foreach ($benefits as $benefit)
                                <li class="inline-flex min-h-10 items-center gap-2 rounded-full bg-surface-alt px-4 text-[14.5px] font-medium text-navy"><x-icon name="check" :size="16" class="text-sea" />{{ $benefit }}</li>
                            @endforeach
                        </ul>
                    </x-detail.section>
                @endif

                @if ($appliesOnSite)
                    <x-detail.section :title="__('site.apply.title')" id="apply">
                        @if ($applied)
                            <p class="flex items-center gap-2.5 rounded-tile bg-surface-alt p-4 text-[15px] text-navy"><x-icon name="check-circle" :size="20" class="text-sea" />{{ __('site.apply.already') }}</p>
                        @elseif (! $user)
                            <div class="rounded-card border border-line bg-surface-alt p-6 text-center">
                                <p class="text-[15px] text-secondary">{{ __('site.apply.sign_in') }}</p>
                                <x-button class="mt-4" :href="localized_route('login')" x-data x-on:click.prevent="window.dispatchEvent(new CustomEvent('require-login'))">{{ __('auth.login.title') }}</x-button>
                            </div>
                        @else
                            <form method="post" action="{{ localized_route('jobs.apply', [$vacancy]) }}" enctype="multipart/form-data" class="flex flex-col gap-4 rounded-card border border-line bg-white p-5 xs:p-6">
                                @csrf
                                <x-form.field :label="__('site.apply.fields.contact_phone')" for="apply-phone" name="contact_phone" :error="$applyErrors->first('contact_phone')">
                                    <input id="apply-phone" type="tel" name="contact_phone" required autocomplete="tel" value="{{ old('contact_phone', $user->phone ? \App\Support\GeorgianPhone::format($user->phone) : '') }}" class="{{ $input }} min-h-12">
                                </x-form.field>
                                <x-form.field :label="__('site.apply.fields.cover_note')" for="apply-note" name="cover_note" optional :error="$applyErrors->first('cover_note')">
                                    <textarea id="apply-note" name="cover_note" rows="5" maxlength="3000" class="{{ $input }} py-3">{{ old('cover_note') }}</textarea>
                                </x-form.field>
                                <x-form.field :label="__('site.apply.fields.cv')" for="apply-cv" name="cv" optional :hint="__('site.apply.cv_hint')" :error="$applyErrors->first('cv')">
                                    <input id="apply-cv" type="file" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                           class="block w-full text-[14px] text-secondary file:mr-3 file:min-h-11 file:cursor-pointer file:rounded-button file:border-0 file:bg-navy file:px-4 file:font-semibold file:text-white">
                                </x-form.field>
                                <div><x-button type="submit" icon="send">{{ __('site.apply.submit') }}</x-button></div>
                            </form>
                        @endif
                    </x-detail.section>
                @endif
            </div>

            <aside class="flex flex-col gap-5 lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-card border border-line bg-white p-5 shadow-card-soft">
                    <p class="text-[13px] text-secondary">{{ __('site.jobs.salary') }}</p>
                    <p class="mt-1 text-[26px] leading-tight font-bold text-price">{{ $salary }}</p>
                    @if ($vacancy->salary_min !== null || $vacancy->salary_max !== null)
                        <p class="text-[14px] text-secondary">{{ __('site.salary_period.'.$vacancy->salary_period->value) }}</p>
                    @endif
                    @if ($appliesOnSite && ! $applied)
                        <x-button href="#apply" class="mt-4 w-full">{{ __('site.apply.title') }}</x-button>
                    @elseif ($vacancy->apply_method?->value === 'email' && $vacancy->apply_email)
                        <x-button :href="'mailto:'.$vacancy->apply_email" icon="mail" class="mt-4 w-full">{{ __('site.apply.by_email') }}</x-button>
                    @endif
                </div>
                <x-detail.owner :owner="$owner" type="vacancy" :id="$vacancy->id" :has-phone="$hasPhone" :title="$vacancy->title" />
            </aside>
        </div>

        @if ($similar->isNotEmpty())
            <x-detail.section :title="__('site.jobs.similar')" class="mt-14 border-t pt-10">
                <x-list-panel>
                    @foreach ($similar as $job)
                        <x-card.job :title="$job['title']" :employer="$job['employer']" :salary="$job['salary']" :period="$job['period']" :type="$job['type']" :seasonal="$job['seasonal']" :posted="$job['posted']" :url="$job['url']" />
                    @endforeach
                </x-list-panel>
            </x-detail.section>
        @endif
    </div>
</x-layout.app>
