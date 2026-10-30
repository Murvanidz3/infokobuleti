{{-- Step 1: module and category. --}}
@php
    $modules = [
        'property' => 'building',
        'project' => 'layers',
        'classified' => 'tag',
        'vacancy' => 'briefcase',
    ];
@endphp

<div class="flex flex-col gap-8">
    @if ($drafts !== [])
        <section aria-labelledby="drafts-title" class="rounded-tile bg-surface-alt p-4 md:p-5">
            <h2 id="drafts-title" class="text-[15px] font-semibold text-navy">{{ __('account.wizard.continue_draft') }}</h2>
            <ul class="mt-3 flex flex-col gap-2">
                @foreach ($drafts as $draft)
                    <li>
                        <a href="{{ localized_route('listings.create', ['draft' => $draft['id']]) }}" class="flex min-h-12 items-center gap-3 rounded-button border border-line bg-white px-4 py-2.5 transition-colors hover:border-coral">
                            <x-icon name="file" :size="18" class="shrink-0 text-secondary" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[14.5px] font-semibold text-navy">{{ $draft['title'] }}</span>
                                <span class="block text-[12.5px] text-secondary">{{ $draft['type'] }} · {{ $draft['updated'] }}</span>
                            </span>
                            <x-icon name="arrow-right" :size="18" class="shrink-0 text-navy" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @unless ($editing)
        <section aria-labelledby="module-title">
            <h2 id="module-title" class="font-serif text-[21px] font-semibold text-navy">{{ __('account.wizard.what') }}</h2>
            @if ($remaining !== null)
                <p class="mt-1.5 text-[14px] text-secondary">{{ trans_choice('account.wizard.remaining', $remaining, ['count' => $remaining]) }}</p>
            @endif
            @error('type')<p class="mt-2 text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror

            <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4" role="radiogroup" aria-labelledby="module-title">
                @foreach ($modules as $value => $icon)
                    <button type="button" role="radio" aria-checked="{{ $type === $value ? 'true' : 'false' }}" wire:click="chooseType('{{ $value }}')"
                            class="group flex min-h-[132px] flex-col items-center justify-center gap-3 rounded-tile border border-line bg-white px-3 py-5 text-center text-navy transition-[background-color,border-color,color,box-shadow] duration-250 hover:border-coral aria-checked:border-navy aria-checked:bg-navy aria-checked:text-white">
                        <span class="grid size-12 place-items-center rounded-full border border-current"><x-icon :name="$icon" :size="22" /></span>
                        <span class="text-[14.5px] leading-snug font-semibold">{{ __('account.wizard.modules.'.$value) }}</span>
                    </button>
                @endforeach
            </div>
        </section>
    @endunless

    @if ($type === 'project')
        <p class="text-[14.5px] leading-relaxed text-secondary">{{ __('account.wizard.project_hint') }}</p>
    @elseif ($type === 'property')
        <section class="flex flex-col gap-6">
            <div>
                <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.deal') }}</h2>
                <x-account.choice-pills class="mt-3" model="form.deal" :options="$options['deals']" :label="__('account.wizard.fields.deal')" />
                @error('form.deal')<p class="mt-2 text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
            </div>
            <div>
                <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.property_type') }}</h2>
                <x-account.choice-pills class="mt-3" model="form.property_type" :options="$options['propertyTypes']" :label="__('account.wizard.fields.property_type')" />
                @error('form.property_type')<p class="mt-2 text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
            </div>
        </section>
    @elseif ($type === 'classified')
        <section class="flex flex-col gap-6">
            <div>
                <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.category_id') }}</h2>
                <div class="mt-3 grid grid-cols-2 gap-2 xs:grid-cols-3 md:grid-cols-4" role="radiogroup" aria-label="{{ __('account.wizard.fields.category_id') }}">
                    @foreach ($options['parents'] as $id => $category)
                        <label class="cursor-pointer" wire:key="parent-{{ $id }}">
                            <input type="radio" value="{{ $id }}" wire:model.live="form.parent_category_id" class="peer sr-only">
                            <span class="flex min-h-[64px] items-center gap-2.5 rounded-tile border border-line bg-white px-3 py-2.5 text-[13.5px] leading-snug font-semibold text-navy transition-colors hover:border-line-strong peer-checked:border-navy peer-checked:bg-navy peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral">
                                <x-icon :name="\App\View\Components\Icon::has($category['icon']) ? $category['icon'] : 'tag'" :size="18" class="shrink-0" />{{ $category['name'] }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
            @if ($options['children'] !== [])
                <div>
                    <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.subcategory') }}</h2>
                    <x-account.choice-pills class="mt-3" model="form.category_id" :options="collect($options['children'])->map(fn ($category) => $category['name'])->all()" :label="__('account.wizard.subcategory')" />
                </div>
            @endif
            @error('form.category_id')<p class="text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
        </section>
    @elseif ($type === 'vacancy')
        <section class="flex flex-col gap-6">
            <div>
                <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.category_id') }}</h2>
                <x-account.choice-pills class="mt-3" model="form.category_id" :options="collect($options['categories'])->map(fn ($category) => $category['name'])->all()" :label="__('account.wizard.fields.category_id')" />
                @error('form.category_id')<p class="mt-2 text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
            </div>
            <div>
                <h2 class="text-[15px] font-semibold text-navy">{{ __('account.wizard.fields.employment_type') }}</h2>
                <x-account.choice-pills class="mt-3" model="form.employment_type" :options="$options['employmentTypes']" :label="__('account.wizard.fields.employment_type')" />
                @error('form.employment_type')<p class="mt-2 text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
            </div>
        </section>
    @endif
</div>
