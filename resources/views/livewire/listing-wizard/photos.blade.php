{{-- Step 4: photos. Resized in the browser, uploaded one by one, reordered by drag or arrows; the first is the cover. --}}
@php
    $max = \App\Support\Listings\ListingForm::MAX_PHOTOS;
    $photos = $form['photos'] ?? [];
    $required = \App\Support\Listings\ListingForm::requiresPhotos($type);
@endphp

<div x-data="photoUploader({{ $max }})" class="flex flex-col gap-5">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-[15px] font-semibold text-navy">
            {{ __('account.wizard.photos.title') }}
            @unless ($required)<span class="text-xs font-medium text-secondary">({{ __('components.optional') }})</span>@endunless
        </h2>
        <p class="text-[13px] text-secondary">{{ __('account.wizard.photos.count', ['count' => count($photos), 'max' => $max]) }}</p>
    </div>

    @if (count($photos) < $max)
        <div x-on:dragover.prevent="dragging = true"
             x-on:dragleave.prevent="dragging = false"
             x-on:drop.prevent="drop($event)"
             x-bind:class="dragging ? 'border-coral bg-coral-tint' : 'border-line-strong bg-surface-alt'"
             class="flex flex-col items-center gap-3 rounded-card border-[1.5px] border-dashed border-line-strong bg-surface-alt px-6 py-9 text-center transition-colors duration-200">
            <span class="grid size-14 place-items-center rounded-full bg-white text-navy shadow-card"><x-icon name="upload" :size="24" /></span>
            <p class="text-base font-semibold text-navy max-md:hidden">{{ __('components.dropzone.title') }}</p>
            <p class="text-[13px] text-secondary max-md:hidden">{{ __('components.dropzone.or') }}</p>
            <input type="file" id="wizard-photos" accept="image/jpeg,image/png,image/webp" multiple x-on:change="pick($event)" class="peer sr-only">
            <label for="wizard-photos" class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-button bg-coral-pressed px-5 text-[14.5px] font-semibold text-white transition-colors duration-200 hover:bg-coral peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral">
                <x-icon name="image" :size="18" />{{ __('account.wizard.photos.choose') }}
            </label>
            <p class="text-xs text-secondary">{{ __('account.wizard.photos.hint', ['max' => $max]) }}</p>
        </div>
    @endif

    <div x-show="busy" x-cloak class="flex items-center gap-3 rounded-tile bg-surface-alt px-4 py-3 text-[14px] text-navy" role="status">
        <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-line"><span class="block h-full rounded-full bg-navy transition-[width]" x-bind:style="`width: ${progress}%`"></span></span>
        <span>{{ __('account.common.uploading') }} <span x-show="queue.length" x-text="'+' + queue.length"></span></span>
    </div>

    @error('upload')<p class="text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
    @error('form.photos')<p class="text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror

    @if ($photos !== [])
        <ul class="grid grid-cols-2 gap-3 xs:grid-cols-3 md:grid-cols-4">
            @foreach ($photos as $index => $photo)
                <li wire:key="photo-{{ $photo['source'] }}-{{ $photo['id'] }}"
                    draggable="true"
                    x-on:dragstart="dragStart({{ $index }})"
                    x-on:dragover.prevent
                    x-on:drop.prevent="dropOn({{ $index }})"
                    class="group relative aspect-4/3 overflow-hidden rounded-tile border border-line bg-surface-alt">
                    <img src="{{ $photo['url'] }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover" draggable="false">
                    @if ($index === 0)
                        <span class="absolute top-2 left-2 rounded-full bg-navy px-2.5 py-1 text-[11.5px] font-semibold text-white">{{ __('account.wizard.photos.cover') }}</span>
                    @endif
                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-1 bg-navy/70 px-1.5 py-1.5 backdrop-blur-[6px]">
                        <div class="flex gap-1">
                            @if ($index > 0)
                                <button type="button" wire:click="movePhoto({{ $index }}, {{ $index - 1 }})" class="grid size-9 place-items-center rounded-full text-white hover:bg-white/15" aria-label="{{ __('account.wizard.photos.left') }}"><x-icon name="chevron-left" :size="17" /></button>
                            @endif
                            @if ($index < count($photos) - 1)
                                <button type="button" wire:click="movePhoto({{ $index }}, {{ $index + 1 }})" class="grid size-9 place-items-center rounded-full text-white hover:bg-white/15" aria-label="{{ __('account.wizard.photos.right') }}"><x-icon name="chevron-right" :size="17" /></button>
                            @endif
                        </div>
                        <div class="flex gap-1">
                            @if ($index > 0)
                                <button type="button" wire:click="movePhoto({{ $index }}, 0)" class="grid size-9 place-items-center rounded-full text-white hover:bg-white/15" aria-label="{{ __('account.wizard.photos.make_cover') }}" title="{{ __('account.wizard.photos.make_cover') }}"><x-icon name="star" :size="16" /></button>
                            @endif
                            <button type="button" wire:click="removePhoto({{ $index }})" class="grid size-9 place-items-center rounded-full text-white hover:bg-white/15" aria-label="{{ __('account.wizard.photos.remove') }}" title="{{ __('account.wizard.photos.remove') }}"><x-icon name="close" :size="17" /></button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="text-[13px] text-secondary">{{ __('account.wizard.photos.order_hint') }}</p>
    @endif
</div>
