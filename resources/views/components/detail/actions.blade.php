@props([
    'type',
    'id',
    'title',
    'saved' => false,
    'favorite' => true,
    'report' => true,
])

@php
    $button = 'inline-flex min-h-10 items-center gap-2 rounded-full border border-line bg-white px-3.5 text-[13.5px] font-semibold text-navy transition-colors duration-200 hover:border-line-strong';
@endphp

<div {{ $attributes->class(['flex flex-wrap items-center gap-2']) }}>
    @if ($favorite)
        <button type="button" data-favorite="{{ $type }}:{{ $id }}" aria-pressed="{{ $saved ? 'true' : 'false' }}" class="group/favorite {{ $button }}">
            <x-icon name="heart" :size="17" class="text-coral group-aria-pressed/favorite:fill-current" />
            <span class="group-aria-pressed/favorite:hidden">{{ __('ui.save') }}</span>
            <span class="hidden group-aria-pressed/favorite:inline">{{ __('site.favorites.saved_short') }}</span>
        </button>
    @endif
    <button type="button" x-data="shareButton(@js($title), @js(__('site.share.copied')))" x-on:click="share()" class="{{ $button }}">
        <x-icon name="share" :size="17" />{{ __('site.share.button') }}
    </button>
    @if ($report)
        <span x-data="authAction(@js(auth()->check()), 'report')">
            <button type="button" x-on:click="run()" class="{{ $button }} text-secondary">
                <x-icon name="flag" :size="17" />{{ __('site.report.button') }}
            </button>
        </span>
    @endif
</div>

@if ($report)
    @auth
        <x-modal name="report" :title="__('site.report.title')" size="sm">
            <form method="post" action="{{ localized_route('report.store', ['type' => $type, 'id' => $id]) }}" class="flex flex-col gap-4">
                @csrf
                <fieldset>
                    <legend class="mb-2.5 text-[14.5px] font-semibold text-navy">{{ __('site.report.reason') }}</legend>
                    <div class="flex flex-col gap-1">
                        @foreach (\App\Enums\ReportReason::cases() as $reason)
                            <label class="flex min-h-10 cursor-pointer items-center gap-3 text-[15px] text-navy">
                                <input type="radio" name="reason" value="{{ $reason->value }}" required @checked(old('reason') === $reason->value) class="size-5 accent-coral">
                                {{ $reason->getLabel() }}
                            </label>
                        @endforeach
                    </div>
                    @if ($errors->report->has('reason'))
                        <p class="mt-1 text-[13.5px] text-coral-pressed">{{ $errors->report->first('reason') }}</p>
                    @endif
                </fieldset>
                <x-form.field :label="__('site.report.comment')" for="report-comment" name="comment" optional>
                    <textarea id="report-comment" name="comment" rows="3" maxlength="1000" class="block w-full rounded-field border border-line bg-white px-4 py-3 text-[15px] text-navy hover:border-line-strong focus:border-navy">{{ old('comment') }}</textarea>
                </x-form.field>
                <div class="flex justify-end gap-3">
                    <x-button variant="ghost" x-on:click="$dispatch('close-dialog', 'report')">{{ __('site.form.cancel') }}</x-button>
                    <x-button type="submit">{{ __('site.report.send') }}</x-button>
                </div>
            </form>
        </x-modal>
        @if ($errors->report->any())
            <div x-data x-init="$nextTick(() => $dispatch('open-dialog', 'report'))"></div>
        @endif
    @endauth
@endif
