@php
    $control = 'block min-h-12 w-full rounded-field border bg-white px-4 text-[15px] text-navy transition-colors duration-200 placeholder:text-secondary/70 hover:border-line-strong focus:border-navy';
    $border = fn (string $key): string => $errors->has($key) ? 'border-coral-pressed' : 'border-line';
    $total = count($steps);
@endphp

<div class="mx-auto w-full max-w-[880px]" x-data x-on:wizard-step.window="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })">
    {{-- Heading --}}
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-[13px] font-semibold text-secondary">{{ __('account.wizard.step_of', ['step' => $step, 'total' => $total]) }} · {{ __('account.wizard.steps.'.$current) }}</p>
            <h1 class="mt-1 font-serif text-[26px] leading-tight font-semibold text-navy md:text-[32px]">{{ $editing ? __('account.wizard.edit_title') : __('account.wizard.title') }}</h1>
        </div>
        <a href="{{ $cancelUrl }}" class="hit-44 relative mt-1 inline-flex shrink-0 items-center gap-1.5 text-[14px] font-semibold text-secondary hover:text-navy">
            <x-icon name="close" :size="18" /><span class="max-xs:sr-only">{{ __('account.wizard.close') }}</span>
        </a>
    </div>

    {{-- Progress --}}
    <ol class="mt-5 grid grid-cols-6 gap-1.5" aria-label="{{ __('account.wizard.progress') }}">
        @foreach ($steps as $index => $name)
            @php($number = $index + 1)
            <li>
                <button type="button"
                        @if ($number <= $reached && $type !== '') wire:click="goTo({{ $number }})" @else disabled @endif
                        @if ($number === $step) aria-current="step" @endif
                        class="group flex w-full flex-col gap-2 text-left disabled:cursor-default">
                    <span @class([
                        'h-1.5 w-full rounded-full transition-colors duration-300',
                        'bg-coral' => $number === $step,
                        'bg-navy' => $number < $step || ($number <= $reached && $number !== $step),
                        'bg-line' => $number > $reached && $number !== $step,
                    ])></span>
                    <span @class([
                        'hidden truncate text-[12.5px] font-semibold md:block',
                        'text-navy' => $number <= $reached,
                        'text-secondary' => $number > $reached,
                        'group-enabled:group-hover:text-coral-pressed' => $number !== $step,
                    ])>{{ __('account.wizard.steps.'.$name) }}</span>
                </button>
            </li>
        @endforeach
    </ol>

    @error('limit')
        <p role="alert" class="mt-5 flex items-start gap-2.5 rounded-tile bg-coral-tint px-4 py-3 text-[14px] leading-normal font-medium text-coral-pressed">
            <x-icon name="alert" :size="18" class="mt-px shrink-0" />{{ $message }}
        </p>
    @enderror

    {{-- Step --}}
    <div class="mt-6 rounded-panel border border-line bg-white p-5 shadow-card md:p-8" wire:key="step-{{ $current }}">
        @include('livewire.listing-wizard.'.$current)
    </div>

    {{-- Navigation: a sticky bar on phones --}}
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-white/95 px-4 pt-3 pb-[max(12px,env(safe-area-inset-bottom))] backdrop-blur-[10px] md:static md:mt-6 md:border-0 md:bg-transparent md:p-0 md:backdrop-blur-none">
        <div class="mx-auto flex w-full max-w-[880px] items-center gap-3">
            @if ($step > 1)
                <x-button variant="secondary" icon="chevron-left" wire:click="back" wire:loading.attr="disabled" class="max-xs:px-3.5"><span class="max-xs:sr-only">{{ __('account.common.back') }}</span></x-button>
            @endif

            <p @class(['min-w-0 truncate text-[13px] text-secondary', 'max-md:hidden' => $current === 'preview']) aria-live="polite">
                <span wire:loading.remove>
                    @if ($type !== '')
                        <x-icon name="check" :size="14" class="mr-1 inline align-[-2px]" />{{ __('account.wizard.autosaved') }}
                    @endif
                </span>
                <span wire:loading>{{ __('account.wizard.saving') }}</span>
            </p>

            <div class="ml-auto flex items-center gap-2">
                @if ($current === 'preview')
                    <x-button icon="send" wire:click="submit" wire:loading.attr="disabled" wire:target="submit">{{ $editing ? __('account.wizard.resubmit') : __('account.wizard.submit') }}</x-button>
                @else
                    <x-button icon-right="arrow-right" wire:click="next" wire:loading.attr="disabled" :disabled="$type === ''">{{ __('account.wizard.next') }}</x-button>
                @endif
            </div>
        </div>
    </div>
</div>
